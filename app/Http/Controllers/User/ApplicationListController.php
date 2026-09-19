<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use Carbon\Carbon;

class ApplicationListController extends Controller
{
    /**
     * 申請一覧画面（PG06）の表示
     */
    public function index()
    {
        $user = Auth::user();

        // 1. ログインユーザーの修正申請が存在する勤怠データ（status が 5:承認待ち または 6:承認済み）を取得
        $attendances = Attendance::where('user_id', $user->id)
            ->whereIn('status', [5, 6])
            ->orderBy('updated_at', 'desc')
            ->get();

        // 2. Blade が求める配列構造（$formattedApplications）に整形
        $formattedApplications = $attendances->map(function ($attendance) {
            // 数値型の status を文字列に変換
            $statusText = match ((int) $attendance->status) {
                5 => '承認待ち',
                6 => '承認済み',
                default => 'その他',
            };

            return [
                'id' => $attendance->id,
                'approval_status' => $statusText,
                'date' => Carbon::parse($attendance->date)->format('Y/m/d'),
                'comment' => $attendance->remarks,
                'application_date' => $attendance->updated_at ? Carbon::parse($attendance->updated_at)->format('Y/m/d') : '',
            ];
        });

        // 3. View に描画
        return view('user.user-application-list', compact('user', 'formattedApplications'));
    }
}
