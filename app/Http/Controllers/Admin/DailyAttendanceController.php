<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DailyAttendanceController extends Controller
{
    /**
     * 日次勤怠一覧表示（PG08）
     */
    public function index(Request $request)
    {
        // クエリパラメータから日付を取得（指定がない場合は本日）
        $dateParam = $request->query('date', Carbon::today()->toDateString());
        $date = Carbon::parse($dateParam);

        // 前日・翌日の日付を取得（Y-m-d 形式）
        $previousDay = $date->copy()->subDay()->toDateString();
        $nextDay = $date->copy()->addDay()->toDateString();

        // 一般ユーザー一覧を取得
        $users = User::where('role', 1)->get();

        // 対象日の勤怠レコードを全件取得
        $attendanceRecords = Attendance::whereDate('date', $date->toDateString())->get();

        return view('admin.admin-attendance-list', compact(
            'date',
            'previousDay',
            'nextDay',
            'users',
            'attendanceRecords'
        ));
    }
}