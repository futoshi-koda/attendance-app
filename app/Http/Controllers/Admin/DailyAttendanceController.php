<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DailyAttendanceController extends Controller
{
    /**
     * 日次勤怠一覧表示（PG08）
     */
    public function index(Request $request): View
    {
        // クエリパラメータから日付を取得（指定がない場合は本日）
        $dateParam = $request->query('date', Carbon::today()->toDateString());
        $date = Carbon::parse($dateParam);

        // 前日・翌日の日付を取得（Y-m-d 形式）
        $previousDay = $date->copy()->subDay()->toDateString();
        $nextDay = $date->copy()->addDay()->toDateString();

        // 全ユーザーを取得
        $users = User::all();

        // 対象日の勤怠レコードを全件取得（Eager Loading で N+1 問題を防止）
        $attendanceRecords = Attendance::with(['user', 'rests'])
            ->whereDate('date', $date->toDateString())
            ->get();

        return view('admin.admin-attendance-list', compact(
            'date',
            'previousDay',
            'nextDay',
            'users',
            'attendanceRecords'
        ));
    }
}
