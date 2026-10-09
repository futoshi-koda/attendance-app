<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Rest;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttendanceStampController extends Controller
{
    /**
     * 打刻画面（PG02 / PG03）を表示
     */
    public function show(): View
    {
        $user = Auth::user();
        $now = Carbon::now();

        $formattedDate = $now->isoFormat('YYYY年M月D日(ddd)');
        $formattedTime = $now->format('H:i');

        return view('user.attendance-register', compact('user', 'formattedDate', 'formattedTime'));
    }

    /**
     * 打刻アクション（出勤・休憩入・休憩戻・退勤）の登録処理
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        switch ($request->input('action')) {
            case 'clock_in': // 出勤
                if (! $attendance) {
                    Attendance::create([
                        'user_id' => $user->id,
                        'date' => $today,
                        'clock_in_at' => $now,
                        'status' => 2,
                    ]);
                }
                break;

            case 'break_in': // 休憩入
                if ($attendance && $attendance->status === 2) {
                    $attendance->update(['status' => 3]);

                    Rest::create([
                        'attendance_id' => $attendance->id,
                        'break_in' => $now,
                    ]);
                }
                break;

            case 'break_out': // 休憩戻
                if ($attendance && $attendance->status === 3) {
                    $attendance->update(['status' => 2]);

                    $latestRest = $attendance->rests()->whereNull('break_out')->latest()->first();
                    if ($latestRest) {
                        $latestRest->update(['break_out' => $now]);
                    }
                }
                break;

            case 'clock_out': // 退勤
                if ($attendance && ($attendance->status === 2 || $attendance->status === 3)) {
                    $attendance->update([
                        'clock_out_at' => $now,
                        'status' => 4,
                    ]);
                }
                break;
        }

        return redirect()->route('attendance.show');
    }
}
