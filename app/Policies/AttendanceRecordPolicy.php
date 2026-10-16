<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AttendanceRecordPolicy
{
    /**
     * 勤怠データの更新権限を判定 (AP04)
     */
    public function update(User $user, Attendance $attendance): bool
    {
        // ログインユーザーのIDと、勤怠データの user_id が一致していれば許可
        // （※もし管理者権限カラム等があれば `|| $user->is_admin` などを追加）
        return $user->id === $attendance->user_id;
    }

    /**
     * 勤怠データの削除権限を判定 (AP05)
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        // 削除も同様に本人のみ（または管理者）許可
        return $user->id === $attendance->user_id;
    }
}