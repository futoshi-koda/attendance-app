<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in_at',
        'clock_out_at',
        'status',
        'remarks',
    ];

    /**
     * ユーザーとのリレーション（多対1）
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 休憩データとのリレーション（1対多）
     */
    public function rests(): HasMany
    {
        return $this->hasMany(Rest::class);
    }

    // ==================================================
    // アクセサ（Blade・コントローラー参照用アクセサ）
    // ==================================================

    /**
     * 出勤時刻 ($attendance->clock_in)
     */
    public function getClockInAttribute()
    {
        return $this->clock_in_at;
    }

    /**
     * 退勤時刻 ($attendance->clock_out)
     */
    public function getClockOutAttribute()
    {
        return $this->clock_out_at;
    }

    /**
     * 備考・修正理由 ($attendance->comment)
     */
    public function getCommentAttribute()
    {
        return $this->remarks;
    }

    /**
     * 休憩合計時間 ($attendance->total_break_time)
     */
    public function getTotalBreakTimeAttribute()
    {
        $totalMinutes = 0;
        foreach ($this->rests as $rest) {
            if ($rest->break_in && $rest->break_out) {
                $start = Carbon::parse($rest->break_in);
                $end = Carbon::parse($rest->break_out);
                $totalMinutes += $end->diffInMinutes($start);
            }
        }

        if ($totalMinutes === 0) {
            return null;
        }

        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * 勤務合計時間 ($attendance->total_time)
     */
    public function getTotalTimeAttribute()
    {
        if (!$this->clock_in_at || !$this->clock_out_at) {
            return null;
        }

        $start = Carbon::parse($this->clock_in_at);
        $end = Carbon::parse($this->clock_out_at);
        $workMinutes = $end->diffInMinutes($start);

        // 休憩時間を差し引く
        foreach ($this->rests as $rest) {
            if ($rest->break_in && $rest->break_out) {
                $bStart = Carbon::parse($rest->break_in);
                $bEnd = Carbon::parse($rest->break_out);
                $workMinutes -= $bEnd->diffInMinutes($bStart);
            }
        }

        if ($workMinutes <= 0) {
            return null;
        }

        $hours = floor($workMinutes / 60);
        $minutes = $workMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }
}