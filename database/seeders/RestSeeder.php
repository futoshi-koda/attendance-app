<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Rest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class RestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user1 = User::where('email', 'user1@example.com')->first();
        $attendances = Attendance::with('user')->get();

        $now = now()->toDateTimeString();
        $restsToInsert = [];

        foreach ($attendances as $attendance) {
            $dateStr = $attendance->date;

            // 全勤怠データに固定休憩 (12:00〜13:00) をセット
            $fixedRestStart = Carbon::parse("{$dateStr} 12:00:00");
            $fixedRestEnd = Carbon::parse("{$dateStr} 13:00:00");

            $restsToInsert[] = [
                'attendance_id' => $attendance->id,
                'start_at' => $fixedRestStart->toDateTimeString(),
                'end_at' => $fixedRestEnd->toDateTimeString(),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // user1 の場合は固定休憩のみで終了
            if ($user1 && $attendance->user_id === $user1->id) {
                continue;
            }

            // user1 以外のユーザーには追加のランダム休憩 (0〜3回) を付与
            $existingRests = [
                ['start' => $fixedRestStart, 'end' => $fixedRestEnd]
            ];

            $additionalRestCount = fake()->numberBetween(0, 3);

            for ($i = 0; $i < $additionalRestCount; $i++) {
                $attempts = 0;
                while ($attempts < 5) { // 重複回避リトライの上限を5回
                    $attempts++;

                    $restStartHour = fake()->numberBetween(11, 14);
                    $restStartMin = fake()->numberBetween(0, 59);
                    $newRestStart = Carbon::parse("{$dateStr} {$restStartHour}:{$restStartMin}:00");

                    $duration = fake()->numberBetween(5, 15); // 5〜15分
                    $newRestEnd = (clone $newRestStart)->addMinutes($duration);

                    // 時間帯重複チェック
                    $isOverlapped = false;
                    foreach ($existingRests as $existing) {
                        if ($newRestStart < $existing['end'] && $newRestEnd > $existing['start']) {
                            $isOverlapped = true;
                            break;
                        }
                    }

                    if (!$isOverlapped) {
                        $restsToInsert[] = [
                            'attendance_id' => $attendance->id,
                            'start_at' => $newRestStart->toDateTimeString(),
                            'end_at' => $newRestEnd->toDateTimeString(),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        $existingRests[] = ['start' => $newRestStart, 'end' => $newRestEnd];
                        break;
                    }
                }
            }
        }

        // 蓄積したすべての休憩データを一括挿入 (バルクインサート)
        // 1000件ずつに分割して挿入（SQL制限対策）
        foreach (array_chunk($restsToInsert, 1000) as $chunk) {
            Rest::insert($chunk);
        }
    }
}