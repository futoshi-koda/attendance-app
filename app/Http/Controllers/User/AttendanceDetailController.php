<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\AttendanceUpdateRequest;
use App\Models\Attendance;
use App\Models\Rest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceDetailController extends Controller
{
    /**
     * 勤怠詳細画面（PG05）の表示
     */
    public function show($id)
    {
        $attendance = Attendance::with(['rests', 'user'])->findOrFail($id);
        $user = $attendance->user;

        // ステータスが申請中の場合に閲覧専用表示フラグをセット
        $isPending = ($attendance->status === 'pending' || $attendance->status === 5);
        $pendingApplication = $isPending ? $attendance : null;

        $breaks = [];
        foreach ($attendance->rests as $rest) {
            $breaks[] = [
                'break_in' => $rest->break_in ? Carbon::parse($rest->break_in)->format('H:i') : '',
                'break_out' => $rest->break_out ? Carbon::parse($rest->break_out)->format('H:i') : '',
            ];
        }

        $attendanceDate = Carbon::parse($attendance->date);

        $data = [
            'id' => $attendance->id,
            'year' => $attendanceDate->format('Y年'),
            'date' => $attendanceDate->format('n月j日'),
            'clock_in' => $attendance->clock_in_at ? Carbon::parse($attendance->clock_in_at)->format('H:i') : '',
            'clock_out' => $attendance->clock_out_at ? Carbon::parse($attendance->clock_out_at)->format('H:i') : '',
            'comment' => $attendance->remarks ?? '',
            'application' => $pendingApplication,
            'breaks' => $breaks,
        ];

        return view('user.user-detail', compact('user', 'data'));
    }

    /**
     * 勤怠修正申請の送信処理（POST）
     */
    public function update(AttendanceUpdateRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        $breakIns = $request->input('new_break_in', []);
        $breakOuts = $request->input('new_break_out', []);

        DB::transaction(function () use ($attendance, $request, $breakIns, $breakOuts) {
            $dateStr = $attendance->date;

            // 出勤・退勤日時を更新 & ステータスを「修正申請中 (5)」に変更
            $attendance->clock_in_at = Carbon::parse("{$dateStr} {$request->new_clock_in}")->toDateTimeString();
            $attendance->clock_out_at = Carbon::parse("{$dateStr} {$request->new_clock_out}")->toDateTimeString();
            $attendance->remarks = $request->comment;
            $attendance->status = 5;
            $attendance->save();

            // 既存の休憩レコードを洗い替え
            $attendance->rests()->delete();

            foreach ($breakIns as $index => $breakIn) {
                $breakOut = $breakOuts[$index] ?? null;

                if ($breakIn && $breakOut) {
                    Rest::create([
                        'attendance_id' => $attendance->id,
                        'break_in' => Carbon::parse("{$dateStr} {$breakIn}")->toDateTimeString(),
                        'break_out' => Carbon::parse("{$dateStr} {$breakOut}")->toDateTimeString(),
                    ]);
                }
            }
        });

        return redirect()->route('attendance.detail', ['id' => $attendance->id])
            ->with('success', '修正申請を送信しました');
    }
}