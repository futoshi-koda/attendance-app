<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 休憩データとのリレーション（1対多）
     *
     * @return HasMany
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
     *
     * @return mixed
     */
    public function getClockInAttribute(): mixed
    {
        return $this->clock_in_at;
    }

    /**
     * 退勤時刻 ($attendance->clock_out)
     *
     * @return mixed
     */
    public function getClockOutAttribute(): mixed
    {
        return $this->clock_out_at;
    }

    /**
     * 備考・修正理由 ($attendance->comment)
     *
     * @return string|null
     */
    public function getCommentAttribute(): ?string
    {
        return $this->remarks;
    }

    /**
     * 休憩合計時間 ($attendance->total_break_time)
     *
     * @return string|null
     */
    public function getTotalBreakTimeAttribute(): ?string
    {
        // Collection メソッド sum を活用して休憩合計分を算出
        $totalMinutes = $this->rests->sum(function ($rest) {
            if ($rest->break_in && $rest->break_out) {
                return Carbon::parse($rest->break_out)->diffInMinutes(Carbon::parse($rest->break_in));
            }
            return 0;
        });

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
    public function getTotalTimeAttribute(): ?string
    {
        if (!$this->clock_in_at || !$this->clock_out_at) {
            return null;
        }

        $start = Carbon::parse($this->clock_in_at);
        $end = Carbon::parse($this->clock_out_at);
        $workMinutes = $end->diffInMinutes($start);

        // Collection メソッド sum を活用して休憩時間を一括算出・マイナス
        $totalBreakMinutes = $this->rests->sum(function ($rest) {
            if ($rest->break_in && $rest->break_out) {
                return Carbon::parse($rest->break_out)->diffInMinutes(Carbon::parse($rest->break_in));
            }
            return 0;
        });

        $workMinutes -= $totalBreakMinutes;

        if ($workMinutes <= 0) {
            return null;
        }

        $hours = floor($workMinutes / 60);
        $minutes = $workMinutes % 60;

        return sprintf('%02d:%02d', $hours, $minutes);
    }
}