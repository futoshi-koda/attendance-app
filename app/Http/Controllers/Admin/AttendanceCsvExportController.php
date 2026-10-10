<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceCsvExportController extends Controller
{
    /**
     * 指定ユーザーの月次勤怠データをCSV形式でエクスポートする
     *
     * @param  Request  $request
     * @return StreamedResponse
     */
    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'year_month' => ['required', 'date_format:Y-m'],
        ]);

        $userId = $request->input('user_id');
        $yearMonth = $request->input('year_month');

        $user = User::findOrFail($userId);

        $date = Carbon::parse($yearMonth . '-01');
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        $daysInMonth = $date->daysInMonth;

        // 対象月の勤怠データを取得（休憩データも同時にEager Loading）
        $attendances = $user->attendances()
            ->with('rests')
            ->whereBetween('date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->date)->toDateString();
            });

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $fileName = sprintf('attendance_%s_%s.csv', $user->id, $date->format('Ym'));

        // ストリーミングレスポンスを使用してメモリ効率良くCSVを出力
        $callback = function () use ($date, $daysInMonth, $attendances, $weekdays) {
            $file = fopen('php://output', 'w');

            // ヘッダー行（Excel文字化け防止のため Shift-JIS に変換）
            $header = ['日付', '出勤', '退勤', '休憩', '合計'];
            fputcsv($file, array_map(function ($value) {
                return mb_convert_encoding($value, 'SJIS-win', 'UTF-8');
            }, $header));

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = $date->copy()->day($day);
                $dateKey = $currentDate->toDateString();
                $attendance = $attendances->get($dateKey);

                // 画面表示と同様のフォーマットに整形
                $dateStr = sprintf('%02d/%02d(%s)', $currentDate->month, $currentDate->day, $weekdays[$currentDate->dayOfWeek]);
                $clockIn = $attendance?->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '';
                $clockOut = $attendance?->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '';
                $totalBreakTime = $attendance?->total_break_time ? Carbon::parse($attendance->total_break_time)->format('G:i') : '';
                $totalTime = $attendance?->total_time ? Carbon::parse($attendance->total_time)->format('G:i') : '';

                $row = [
                    $dateStr,
                    $clockIn,
                    $clockOut,
                    $totalBreakTime,
                    $totalTime,
                ];

                // 行データの各セルも Shift-JIS に変換
                fputcsv($file, array_map(function ($value) {
                    return mb_convert_encoding($value, 'SJIS-win', 'UTF-8');
                }, $row));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}