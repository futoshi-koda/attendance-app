<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Rest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class RestFactory extends Factory
{
    protected $model = Rest::class;

    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'start_at' => function (array $attributes) {
                // 紐づくAttendanceの日付を取得して12:00に設定
                $attendance = Attendance::find($attributes['attendance_id']);
                $date = $attendance ? $attendance->date : now()->format('Y-m-d');
                return "{$date} 12:00:00";
            },
            'end_at' => function (array $attributes) {
                // 紐づくAttendanceの日付を取得して13:00に設定
                $attendance = Attendance::find($attributes['attendance_id']);
                $date = $attendance ? $attendance->date : now()->format('Y-m-d');
                return "{$date} 13:00:00";
            },
        ];
    }
}