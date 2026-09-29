<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StaffAttendanceController extends Controller
{
    /**
     * スタッフ別月次勤怠一覧画面表示（PG11）
     */
    public function show(Request $request, $id)
    {
        // 対象のユーザーを取得（存在しない場合は404）
        $user = User::findOrFail($id);

        // クエリパラメータ 'date' を取得（指定がなければ当月）
        $dateParam = $request->query('date');

        try {
            $date = $dateParam ? Carbon::parse($dateParam)->firstOfMonth() : Carbon::now()->firstOfMonth();
        } catch (\Exception $e) {
            $date = Carbon::now()->firstOfMonth();
        }

        // 前月・翌月の YYYY-MM フォーマット文字列
        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        // 月の開始日と終了日を取得
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        // 該当ユーザーの当月の勤怠データを取得して日付（Y-m-d）をキーにした連想配列を作成
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });

        // 1日から末日までの全日付ループを作成
        $formattedAttendanceRecords = [];
        $currentDate = $startOfMonth->copy();

        while ($currentDate->lte($endOfMonth)) {
            $dateStr = $currentDate->format('Y-m-d');
            $attendance = $attendances->get($dateStr);

            if ($attendance) {
                $formattedAttendanceRecords[] = [
                    'id' => $attendance->id,
                    'date' => $currentDate->isoFormat('MM/DD(ddd)'),
                    'clock_in' => $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '',
                    'clock_out' => $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '',
                    'total_break_time' => $attendance->total_break_time ?? '',
                    'total_time' => $attendance->total_time ?? '',
                ];
            } else {
                // 勤怠データが存在しない日の初期値
                $formattedAttendanceRecords[] = [
                    'id' => null,
                    'date' => $currentDate->isoFormat('MM/DD(ddd)'),
                    'clock_in' => '',
                    'clock_out' => '',
                    'total_break_time' => '',
                    'total_time' => '',
                ];
            }

            $currentDate->addDay();
        }

        return view('admin.staff-attendance-list', compact(
            'user',
            'date',
            'previousMonth',
            'nextMonth',
            'formattedAttendanceRecords'
        ));
    }
}