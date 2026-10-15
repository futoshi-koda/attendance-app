<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAttendanceRecordRequest;
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

        if (! $attendanceRecord) {
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
            'clock_in_at' => $request->input('date').' '.$request->input('clock_in'),
            'clock_out_at' => $request->filled('clock_out') ? $request->input('date').' '.$request->input('clock_out') : null,
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
}
