<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * 勤怠一覧画面（PG04）を表示
     */
    public function index(Request $request): View|RedirectResponse
    {
        // 管理者の場合は管理者用日次勤怠一覧（PG08）へリダイレクト
        if (Auth::check() && Auth::user()->role === 2) {
            return redirect()->route('admin.attendance.list');
        }

        // 1. クエリパラメータ 'date' を取得（指定がなければ当月1日）
        $dateParam = $request->query('date');

        try {
            $date = $dateParam ? Carbon::parse($dateParam)->firstOfMonth() : Carbon::now()->firstOfMonth();
        } catch (\Exception $e) {
            $date = Carbon::now()->firstOfMonth();
        }

        // 2. 前月と翌月のパラメータ（Y-m形式）
        $previousMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');

        // 3. 対象月の開始日と終了日
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        $daysInMonth = $date->daysInMonth;

        // 4. ログインユーザーの対象月勤怠データを取得（Eager Loading で N+1 防止 & keyBy 保持）
        $user = Auth::user();
        $attendances = $user->attendances()
            ->with('rests')
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->date)->toDateString();
            });

        // 5. 1日〜末日までの日付データを構築（Blade側が求める配列形式に整形）
        $formattedAttendanceRecords = [];
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $currentDate = $date->copy()->day($day);
            $dateKey = $currentDate->toDateString();

            // 該当日の勤怠モデルを取得
            $attendance = $attendances->get($dateKey);

            $formattedAttendanceRecords[] = [
                'id' => $attendance?->id,
                'date' => sprintf('%02d/%02d(%s)', $currentDate->month, $currentDate->day, $weekdays[$currentDate->dayOfWeek]),
                'clock_in' => $attendance?->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '',
                'clock_out' => $attendance?->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '',
                'total_break_time' => $attendance?->total_break_time,
                'total_time' => $attendance?->total_time,
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
