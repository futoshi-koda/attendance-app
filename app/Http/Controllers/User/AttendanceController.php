<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * 勤怠一覧画面（PG04）を表示
     */
    public function index(Request $request)
    {
        // 1. クエリパラメータ 'date' を取得（なければ当月1日）
        $dateParam = $request->query('date');

        try {
            $date = $dateParam ? Carbon::parse($dateParam)->firstOfMonth() : Carbon::now()->firstOfMonth();
        } catch (\Exception $e) {
            $date = Carbon::now()->firstOfMonth();
        }

        // 2. 前月と翌月のパラメータ（Y-m形式）
        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        // 3. 対象月の開始日・終了日・日数
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        $daysInMonth = $date->daysInMonth;

        // 4. ログインユーザーの対象月勤怠データを取得
        $user = Auth::user();
        $attendances = $user->attendances()
            ->with('rests')
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->date)->toDateString();
            });

        // 5. 1日〜末日までの全日付データを構築
        $formattedAttendanceRecords = [];
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = $date->copy()->day($day);
            $dateKey = $currentDate->toDateString();

            // 該当日の勤怠データを取得
            $attendance = $attendances->get($dateKey);

            $clockIn = null;
            $clockOut = null;
            $totalBreakTime = null;
            $totalTime = null;
            $attendanceId = null;

            if ($attendance) {
                $attendanceId = $attendance->id;

                // 出勤・退勤時刻（H:i 形式）
                $clockIn = $attendance->clock_in_at ? Carbon::parse($attendance->clock_in_at)->format('H:i') : '';
                $clockOut = $attendance->clock_out_at ? Carbon::parse($attendance->clock_out_at)->format('H:i') : '';

                // --- 休憩合計時間の計算 ---
                $totalBreakMinutes = 0;
                foreach ($attendance->rests as $rest) {
                    $start = $rest->break_in;
                    $end = $rest->break_out;

                    if ($start && $end) {
                        $totalBreakMinutes += Carbon::parse($start)->diffInMinutes(Carbon::parse($end));
                    }
                }

                if ($totalBreakMinutes > 0) {
                    $breakHours = floor($totalBreakMinutes / 60);
                    $breakMins = $totalBreakMinutes % 60;
                    // Blade 側の Carbon::parse($total_break_time)->format('G:i') に対応させるため H:i 形式で保持
                    $totalBreakTime = sprintf('%02d:%02d', $breakHours, $breakMins);
                }

                // --- 勤務合計時間の計算 ---
                if ($attendance->clock_in_at && $attendance->clock_out_at) {
                    $workingMinutes = Carbon::parse($attendance->clock_in_at)->diffInMinutes(Carbon::parse($attendance->clock_out_at));
                    $actualWorkingMinutes = max(0, $workingMinutes - $totalBreakMinutes);

                    $workHours = floor($actualWorkingMinutes / 60);
                    $workMins = $actualWorkingMinutes % 60;
                    $totalTime = sprintf('%02d:%02d', $workHours, $workMins);
                }
            }

            // Blade に渡す配列フォーマットを定義
            $formattedAttendanceRecords[] = [
                'id' => $attendanceId,
                'date' => sprintf('%02d/%02d(%s)', $currentDate->month, $currentDate->day, $weekdays[$currentDate->dayOfWeek]),
                'clock_in' => $clockIn ?? '',
                'clock_out' => $clockOut ?? '',
                'total_break_time' => $totalBreakTime,
                'total_time' => $totalTime,
            ];
        }

        // 6. View に描画
        return view('user.user-attendance-list', compact(
            'date',
            'previousMonth',
            'nextMonth',
            'formattedAttendanceRecords'
        ));
    }
}