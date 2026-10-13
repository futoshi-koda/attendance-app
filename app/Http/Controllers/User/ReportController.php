<?php
namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * マイ勤怠レポート画面を表示する
     * 過去6ヶ月分の勤怠データ集計（基本サマリー・月次推移・異常検知）を行う
     *
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        $userId = auth()->id();

        // 基準日を「直近の締まった月（当月の前月、例：10月なら9月）」に設定
        $now = Carbon::now()->subMonth();
        $sixMonthsAgo = $now->copy()->subMonths(5)->startOfMonth();

        // データベースから過去6ヶ月間の勤怠データと休憩データを一括取得（Eager LoadingでN+1問題対策）
        $attendances = Attendance::with('rests')
            ->where('user_id', $userId)
            ->where('date', '>=', $sixMonthsAgo->format('Y-m-d'))
            ->where('date', '<=', $now->copy()->endOfMonth()->format('Y-m-d'))
            ->get();

        // -----------------------------------------------------------------
        // Collection の map を用いて、各レコードの労働時間と残業時間を算出
        // -----------------------------------------------------------------
        $processedAttendances = $attendances->map(function (Attendance $item): Attendance {
            $workMinutes = 0;
            $overtimeMinutes = 0;

            if ($item->clock_in_at && $item->clock_out_at) {
                $in = Carbon::parse($item->clock_in_at);
                $out = Carbon::parse($item->clock_out_at);

                // 総拘束時間（分）
                $diffMinutes = $in->diffInMinutes($out);

                // Collection の sum を用いて紐づく休憩時間の合計（分）を算出
                $totalBreakMinutes = $item->rests->sum(function ($rest) {
                    if ($rest->break_in && $rest->break_out) {
                        return Carbon::parse($rest->break_in)->diffInMinutes(Carbon::parse($rest->break_out));
                    }
                    return 0;
                });

                // 実際の労働時間 ＝ 総拘束時間 － 休憩時間の合計
                $workMinutes = max(0, $diffMinutes - $totalBreakMinutes);

                // 残業時間（8時間 = 480分を超える分）
                if ($workMinutes > 480) {
                    $overtimeMinutes = $workMinutes - 480;
                }
            }

            $item->calculated_work_minutes = $workMinutes;
            $item->calculated_overtime_minutes = $overtimeMinutes;
            return $item;
        });

        // -----------------------------------------------------------------
        // 1. 基本サマリーの集計（FN053）
        // -----------------------------------------------------------------
        $totalWorkMinutes = $processedAttendances->sum('calculated_work_minutes');
        $totalOvertimeMinutes = $processedAttendances->sum('calculated_overtime_minutes');
        $workingDaysCount = $processedAttendances->where('calculated_work_minutes', '>', 0)->count();

        $avgWorkMinutes = $workingDaysCount > 0 ? intval($totalWorkMinutes / $workingDaysCount) : 0;

        $summary = [
            'total_work_minutes' => $totalWorkMinutes,
            'total_overtime_minutes' => $totalOvertimeMinutes,
            'avg_work_minutes' => $avgWorkMinutes,
        ];

        // -----------------------------------------------------------------
        // 2. 月次推移の集計（FN054: 過去6ヶ月分）
        // Collection の map を用いて、月ごとの配列を構築
        // -----------------------------------------------------------------
        $monthlyRange = collect(range(5, 0))->map(fn($i) => $now->copy()->subMonths($i));

        $monthlyTrend = $monthlyRange->map(function (Carbon $targetMonth) use ($processedAttendances): array {
            $yearMonthStr = $targetMonth->format('Y年m月');

            // Collection の filter を用いて対象月のデータを抽出
            $monthAttendances = $processedAttendances->filter(function (Attendance $item) use ($targetMonth): bool {
                return Carbon::parse($item->date)->format('Y-m') === $targetMonth->format('Y-m');
            });

            return [
                'month' => $yearMonthStr,
                'work_minutes' => $monthAttendances->sum('calculated_work_minutes'),
                'overtime_minutes' => $monthAttendances->sum('calculated_overtime_minutes'),
            ];
        })->all();

        // -----------------------------------------------------------------
        // 3. 今月の異常検知（FN056: 直近月＝9月）
        // Collection の filter と count を用いて手続的なループを排除
        // -----------------------------------------------------------------
        $currentMonthAttendances = $processedAttendances->filter(function (Attendance $item) use ($now): bool {
            return Carbon::parse($item->date)->format('Y-m') === $now->format('Y-m');
        });

        $lateCount = $currentMonthAttendances->filter(function (Attendance $item): bool {
            return $item->clock_in_at && Carbon::parse($item->clock_in_at)->format('H:i') > '09:00';
        })->count();

        $earlyLeaveCount = $currentMonthAttendances->filter(function (Attendance $item): bool {
            return $item->clock_out_at && Carbon::parse($item->clock_out_at)->format('H:i') < '18:00';
        })->count();

        $longWorkCount = $currentMonthAttendances->filter(function (Attendance $item): bool {
            return $item->calculated_work_minutes > 600;
        })->count();

        $anomalies = [
            'late_count' => $lateCount,
            'early_leave_count' => $earlyLeaveCount,
            'long_work_count' => $longWorkCount,
        ];

        return view('reports.index', compact('summary', 'monthlyTrend', 'anomalies'));
    }
}