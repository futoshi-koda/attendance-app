<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Http\Requests\AttendanceUpdateRequest; // 共通バリデーションリクエスト
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceDetailController extends Controller
{
    /**
     * 管理者：勤怠詳細画面表示 (PG09)
     */
    public function show($id)
    {
        $attendance = Attendance::with(['user', 'rests'])->findOrFail($id);
        $user = $attendance->user;

        // Carbon インスタンスへ変換
        $date = Carbon::parse($attendance->date);

        // 休憩データの整形
        $breaks = $attendance->rests->map(function ($rest) {
            return [
                'break_in' => $rest->break_in ? Carbon::parse($rest->break_in)->format('H:i') : '',
                'break_out' => $rest->break_out ? Carbon::parse($rest->break_out)->format('H:i') : '',
            ];
        })->toArray();

        $attendanceRecord = [
            'id' => $attendance->id,
            'year' => $date->format('Y年'),
            'date' => $date->format('m月d日'),
            'clock_in' => $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '',
            'clock_out' => $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '',
            'breaks' => $breaks,
            'comment' => $attendance->comment ?? '',
        ];

        return view('admin.admin-detail', compact('user', 'attendanceRecord'));
    }

    /**
     * 管理者：勤怠データ直接修正処理
     */
    public function update(AttendanceUpdateRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        // 管理者による直接更新処理（勤怠本体および Rest レコードの更新ロジック）
        // ...

        return redirect()->route('admin.attendance.list')->with('success', '勤怠データを修正しました。');
    }
}