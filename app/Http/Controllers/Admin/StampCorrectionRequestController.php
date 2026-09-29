<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StampCorrectionRequestController extends Controller
{
    /**
     * PG13: 修正申請の承認画面表示
     */
    public function show($id)
    {
        // 勤怠データおよび関連データを取得
        $attendance = Attendance::with(['user', 'rests'])->findOrFail($id);

        // Blade が参照する動的プロパティを補完
        $attendance->new_date = Carbon::parse($attendance->date);
        $attendance->new_clock_in = $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '';
        $attendance->new_clock_out = $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '';

        // Blade の $application->proposalBreaks に rests を割り当て
        $attendance->proposalBreaks = $attendance->rests ?? collect();

        // Blade の $application->approval_status（ステータスの文字列変換）
        // 5: 修正申請中（承認待ち）、6: 承認済
        $attendance->approval_status = ($attendance->status === 6) ? '承認済み' : '承認待ち';

        return view('admin.admin-application-detail', [
            'application' => $attendance,
            'user' => $attendance->user,
        ]);
    }

    /**
     * PG13: 修正申請の承認実行処理
     */
    public function approve(Request $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        // ステータスを「6：承認済」に更新
        $attendance->update([
            'status' => 6,
        ]);

        return redirect()->route('admin.application.list')
            ->with('success', '申請を承認しました。');
    }
}