<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now()->toDateTimeString();
        $attendancesToInsert = [];

        // ====================================================
        // 1. user1 の確定勤怠データ作成（2026年4月〜9月）
        // ====================================================
        $user1 = User::where('email', 'user1@example.com')->first();

        if ($user1) {
            // 【過去5ヶ月分】2026年4月〜8月（各月15日＝75日分）
            $targetMonths = [4, 5, 6, 7, 8];

            foreach ($targetMonths as $month) {
                for ($day = 1; $day <= 15; $day++) {
                    $dateStr = sprintf('2026-%02d-%02d', $month, $day);

                    $attendancesToInsert[] = [
                        'user_id' => $user1->id,
                        'date' => $dateStr,
                        'clock_in_at' => "{$dateStr} 09:00:00",
                        'clock_out_at' => "{$dateStr} 18:00:00",
                        'status' => 4, // 退勤済
                        'remarks' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            // 【当月分】2026年9月（17日分）の指定パターン
            $patterns = array_merge(
                array_fill(0, 10, ['in' => '09:00:00', 'out' => '18:00:00']), // 通常 10日
                array_fill(0, 3, ['in' => '09:00:00', 'out' => '20:00:00']), // 残業 3日
                array_fill(0, 2, ['in' => '09:30:00', 'out' => '18:00:00']), // 遅刻 2日
                array_fill(0, 1, ['in' => '09:00:00', 'out' => '17:00:00']), // 早退 1日
                array_fill(0, 1, ['in' => '08:00:00', 'out' => '21:00:00'])  // 長時間 1日
            );

            foreach ($patterns as $index => $pattern) {
                $day = $index + 1;
                $dateStr = sprintf('2026-09-%02d', $day);

                $attendancesToInsert[] = [
                    'user_id' => $user1->id,
                    'date' => $dateStr,
                    'clock_in_at' => "{$dateStr} {$pattern['in']}",
                    'clock_out_at' => "{$dateStr} {$pattern['out']}",
                    'status' => 4,
                    'remarks' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        // ====================================================
        // 2. その他のユーザーのデータ作成
        // ====================================================
        $otherUsers = User::where('email', '!=', 'user1@example.com')->get();

        foreach ($otherUsers as $user) {
            for ($month = 4; $month <= 9; $month++) {
                $workDaysCount = fake()->numberBetween(15, 20);

                $daysInMonth = Carbon::create(2026, $month, 1)->daysInMonth;
                $availableDays = range(1, $daysInMonth);
                shuffle($availableDays);
                $selectedDays = array_slice($availableDays, 0, $workDaysCount);
                sort($selectedDays);

                foreach ($selectedDays as $day) {
                    $dateStr = sprintf('2026-%02d-%02d', $month, $day);

                    $startHour = fake()->numberBetween(8, 10);
                    $startMinute = fake()->randomElement([0, 15, 30, 45]);
                    $clockIn = Carbon::parse("{$dateStr} {$startHour}:{$startMinute}:00");

                    $workHours = fake()->numberBetween(8, 12);
                    $clockOut = (clone $clockIn)->addHours($workHours);

                    $isApproved = fake()->boolean(20);
                    $status = $isApproved ? 6 : 4;
                    $remarks = $isApproved ? fake()->realText(20) : null;

                    $attendancesToInsert[] = [
                        'user_id' => $user->id,
                        'date' => $dateStr,
                        'clock_in_at' => $clockIn->toDateTimeString(),
                        'clock_out_at' => $clockOut->toDateTimeString(),
                        'status' => $status,
                        'remarks' => $remarks,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        // 一括挿入（1,000件ずつ分割）
        foreach (array_chunk($attendancesToInsert, 1000) as $chunk) {
            Attendance::insert($chunk);
        }
    }
}