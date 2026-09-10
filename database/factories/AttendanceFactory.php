<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        // 確率による勤務パターンの分岐 (1〜100の乱数)
        $chance = $this->faker->numberBetween(1, 100);

        if ($chance <= 80) {
            // 【80%】通常勤務
            $timeIn = '09:00:00';
            $timeOut = '18:00:00';
        } elseif ($chance <= 95) {
            // 【15%】残業1時間
            $timeIn = '09:00:00';
            $timeOut = '19:00:00';
        } else {
            // 【5%】遅刻または早退
            if ($this->faker->boolean()) {
                $timeIn = '09:30:00';
                $timeOut = '18:00:00';
            } else {
                $timeIn = '09:00:00';
                $timeOut = '17:00:00';
            }
        }

        return [
            'user_id' => User::factory(),
            'date' => now()->format('Y-m-d'),
            'clock_in_at' => function (array $attributes) use ($timeIn) {
                // 上書きされた 'date' を取得して時刻文字列を結合
                $date = $attributes['date'];
                return "{$date} {$timeIn}";
            },
            'clock_out_at' => function (array $attributes) use ($timeOut) {
                // 上書きされた 'date' を取得して時刻文字列を結合
                $date = $attributes['date'];
                return "{$date} {$timeOut}";
            },
            'status' => 4, // 退勤済
            'remarks' => $this->faker->optional(0.2)->realText(20),
        ];
    }

    /**
     * 【State】出勤中のデータを作成（本日の打刻画面確認用）
     */
    public function working(): static
    {
        return $this->state(fn(array $attributes) => [
            'date' => now()->format('Y-m-d'),
            'clock_in_at' => now()->format('Y-m-d 09:00:00'),
            'clock_out_at' => null,
            'status' => 2, // 出勤中
            'remarks' => null,
        ]);
    }

    /**
     * 【State】休憩中のデータを作成（本日の打刻画面確認用）
     */
    public function resting(): static
    {
        return $this->state(fn(array $attributes) => [
            'date' => now()->format('Y-m-d'),
            'clock_in_at' => now()->format('Y-m-d 09:00:00'),
            'clock_out_at' => null,
            'status' => 3, // 休憩中
            'remarks' => null,
        ]);
    }

    /**
     * 【State】勤務外のデータを作成
     */
    public function offWork(): static
    {
        return $this->state(fn(array $attributes) => [
            'date' => now()->format('Y-m-d'),
            'clock_in_at' => null,
            'clock_out_at' => null,
            'status' => 1, // 勤務外
            'remarks' => null,
        ]);
    }
}