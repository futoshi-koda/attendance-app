<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAttendanceRecordRequest;
use App\Http\Requests\Api\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AttendanceRecordController extends Controller
{
    /**
     * 勤怠一覧を取得する（検索・絞り込み・ページネーション対応）
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Attendance::query();

        // 1. user_id による絞り込み
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        // 2. date による絞り込み（YYYY-MM-DD）
        if ($request->filled('date')) {
            $query->where('date', $request->input('date'));
        }

        // 3. month による絞り込み（YYYY-MM）
        if ($request->filled('month')) {
            $month = $request->input('month'); // 例: "2026-09"
            $query->whereYear('date', '=', substr($month, 0, 4))
                ->whereMonth('date', '=', substr($month, 5, 2));
        }

        // 4. ページネーション（デフォルト 20、最大 100）
        $perPage = min($request->integer('per_page', 20), 100);
        $perPage = max($perPage, 1);

        $attendances = $query->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        // ResourceCollection を用いて返すことで、自動的に meta (current_page, last_page, per_page, total 等) を付与
        return AttendanceResource::collection($attendances);
    }

    /**
     * 指定した勤怠の詳細を取得する
     *
     * @param  int|string  $id
     * @return AttendanceResource|JsonResponse
     */
    public function show($id)
    {
        // ユーザー、休憩（rests）、修正申請等の関連を Eager Load
        $attendanceRecord = Attendance::with(['user', 'rests'])->find($id);

        if (!$attendanceRecord) {
            return response()->json([
                'error' => '勤怠情報が見つかりませんでした。',
            ], 404);
        }

        return new AttendanceResource($attendanceRecord);
    }

    /**
     * 勤怠を新規登録する
     */
    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        // 認証ユーザーから user_id を自動付与して作成
        $attendance = $request->user()->attendances()->create([
            'date' => $request->input('date'),
            'clock_in_at' => $request->input('date') . ' ' . $request->input('clock_in'),
            'clock_out_at' => $request->filled('clock_out') ? $request->input('date') . ' ' . $request->input('clock_out') : null,
            'remarks' => $request->input('comment'),
            'status' => 1, // デフォルトのステータス等
        ]);

        // 関連データをロードしてリソースでラップ
        $attendance->load(['user', 'rests']);

        // ステータス 201 で返す
        return (new AttendanceResource($attendance))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 勤怠を更新する (AP04)
     * 
     * @param UpdateAttendanceRecordRequest $request
     * @param Attendance $attendanceRecord
     * @return AttendanceResource
     */
    public function update(UpdateAttendanceRecordRequest $request, Attendance $attendanceRecord): AttendanceResource
    {
        // 認可チェック（Policy の update メソッドを呼び出し、本人または管理者か判定）
        $this->authorize('update', $attendanceRecord);

        // 送信されたデータのみを部分更新
        $validated = $request->validated();

        // 日付が変更された場合、それに伴って日時（clock_in / clock_out）の日付部分も更新する必要があるため考慮
        $data = [];

        if (isset($validated['date'])) {
            $data['date'] = $validated['date'];
        }

        // 既存の日付または新しく指定された日付ベースで日時を組み立て
        $targetDate = $validated['date'] ?? $attendanceRecord->date;

        if (isset($validated['clock_in'])) {
            $data['clock_in_at'] = $targetDate . ' ' . $validated['clock_in'];
        }

        if (array_key_exists('clock_out', $validated)) {
            $data['clock_out_at'] = $validated['clock_out'] ? $targetDate . ' ' . $validated['clock_out'] : null;
        }

        if (array_key_exists('comment', $validated)) {
            $data['remarks'] = $validated['comment'];
        }

        $attendanceRecord->update($data);

        // リレーションをロードしてレスポンス化
        $attendanceRecord->load(['user', 'rests']);

        return new AttendanceResource($attendanceRecord);
    }

    /**
     * 勤怠を削除する (AP05)
     * 
     * @param Attendance $attendanceRecord
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Attendance $attendanceRecord): \Illuminate\Http\JsonResponse
    {
        // 認可チェック（Policy の delete メソッドを呼び出し）
        $this->authorize('delete', $attendanceRecord);

        $attendanceRecord->delete();

        // 正常削除時は 204 No Content を返す
        return response()->json(null, 204);
    }
}