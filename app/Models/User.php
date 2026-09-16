<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function getAttendanceStatusAttribute()
    {
        // 今日の勤怠レコードを取得
        $todayAttendance = $this->attendances()
            ->whereDate('date', Carbon::today())
            ->first();

        // ① 当日のデータがない場合は「勤務外」
        if (!$todayAttendance) {
            return '勤務外';
        }

        // ② 当日のデータがある場合は、status カラムの値（1〜6）に応じて文字列を返す
        switch ($todayAttendance->status) {
            case 1:
                return '勤務外';
            case 2:
                return '出勤中';
            case 3:
                return '休憩中';
            case 4:
                return '退勤済';
            case 5:
                return '修正申請中';
            case 6:
                return '承認済';
            default:
                return '勤務外';
        }
    }
}