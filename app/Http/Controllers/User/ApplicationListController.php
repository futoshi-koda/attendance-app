<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Carbon\Carbon;

class ApplicationListController extends Controller
{
    /**
     * 申請一覧画面（PG06 / PG12）の表示
     */
    public function index()
    {
        $user = Auth::user();

        // 1. 勤怠データと申請者情報（user）を取得
        $query = Attendance::with('user')
            ->whereIn('status', [5, 6]);

        // 一般ユーザー（role !== 2）の場合は自分の申請のみに絞り込む
        // 管理者（role === 2）の場合は全ユーザー分を取得する
        if ($user->role !== 2) {
            $query->where('user_id', $user->id);
        }

        $attendances = $query->orderBy('updated_at', 'desc')->get();

        // 2. Blade が求めるプロパティ構造に合わせて動的属性を追加
        $applications = $attendances->map(function ($attendance) {
            // ステータス表示テキスト（5: 承認待ち, 6: 承認済み）
            $attendance->approval_status = ((int) $attendance->status === 6) ? '承認済み' : '承認待ち';

            // 申請理由・申請日時
            $attendance->comment = $attendance->remarks;
            $attendance->application_date = $attendance->updated_at;

            // 管理者用 Blade の $application->AttendanceRecord->date 参照に対応するため自身をセット
            $attendance->AttendanceRecord = $attendance;

            return $attendance;
        });

        // 3. 管理者と一般ユーザーで View を分岐して呼び出し
        if ($user->role === 2) {
            // 管理者の場合は管理者用 Blade を返す
            return view('admin.admin-application-list', [
                'user' => $user,
                'applications' => $applications,
            ]);
        }

        // 一般ユーザーの場合
        return view('user.user-application-list', [
            'user' => $user,
            'applications' => $applications,
            'formattedApplications' => $applications,
        ]);
    }
}