<?php

use App\Http\Controllers\Admin\AttendanceDetailController as AdminAttendanceDetailController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\DailyAttendanceController;
use App\Http\Controllers\Admin\StaffAttendanceController;
use App\Http\Controllers\Admin\StaffListController;
use App\Http\Controllers\Admin\StampCorrectionRequestController;
use App\Http\Controllers\AttendanceStampController;
use App\Http\Controllers\User\ApplicationListController;
use App\Http\Controllers\User\AttendanceController;
use App\Http\Controllers\User\AttendanceDetailController;
use Illuminate\Support\Facades\Route;

// トップページアクセス時は一般ログイン画面へリダイレクト
Route::get('/', function () {
    return redirect()->route('login');
});

// 管理者ログイン画面表示（GET）
Route::get('/admin/login', function () {
    return view('admin.admin-login');
})->name('admin.login');

// 管理者ログイン処理（POST）
Route::post('/admin/login', [AdminAuthenticatedSessionController::class, 'store']);

// ==================================================
// 1. 管理者専用ルート（auth + admin）
// prefix('admin') を設定し、/admin/... のパスを統合します
// ==================================================
Route::middleware(['auth', 'admin'])->prefix('admin')->as('admin.')->group(function () {

    // 管理者用日次勤怠一覧画面（PG08）
    Route::get('/attendance/list', [DailyAttendanceController::class, 'index'])->name('attendance.list');

    // PG09 管理者用勤怠詳細画面表示
    Route::get('/attendance/{id}', [AdminAttendanceDetailController::class, 'show'])->name('attendance.show');

    // 管理者ログアウト処理（/admin/logout に対応）
    Route::post('/logout', [AdminAuthenticatedSessionController::class, 'destroy'])->name('logout');

    // PG10: スタッフ一覧画面
    Route::get('/staff/list', [StaffListController::class, 'index'])->name('staff.list');

    // PG11: スタッフ別月次勤怠一覧画面（{id} はユーザーID）
    Route::get('/attendance/staff/{id}', [StaffAttendanceController::class, 'show'])->name('attendance.staff');
});

// ==================================================
// 2. 認証済み一般ユーザー・管理者共通ルート（auth）
// ==================================================
Route::middleware('auth')->group(function () {

    // ログイン後の振分トップ（リダイレクト受け皿）
    Route::get('/home', function () {
        $user = auth()->user();

        if ($user->role === 2) {
            return redirect()->route('admin.application.list');
        }

        return redirect()->route('attendance.show');
    })->name('home');

    // 一般ユーザー機能
    Route::get('/attendance', [AttendanceStampController::class, 'show'])->name('attendance.show');
    Route::post('/attendance', [AttendanceStampController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.list');

    Route::get('/attendance/{id}', [AttendanceDetailController::class, 'show'])->name('attendance.detail');
    Route::post('/attendance/{id}', [AttendanceDetailController::class, 'update'])->name('attendance.update');

    // PG13: 承認詳細画面の表示（GET）と 承認実行処理（POST）
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [StampCorrectionRequestController::class, 'show'])
        ->name('stamp_correction_request.approve.show');

    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [StampCorrectionRequestController::class, 'approve'])
        ->name('stamp_correction_request.approve.submit');

    // 申請詳細画面（/application/{id}）の分岐対応
    Route::get('/application/{id}', function ($id) {
        if (auth()->user()->role === 2) {
            return app(StampCorrectionRequestController::class)->show($id);
        }

        return app(AttendanceDetailController::class)->show($id);
    })->name('application.detail');

    // 申請一覧（メインルート）
    Route::get('/stamp_correction_request/list', [ApplicationListController::class, 'index'])
        ->name('application.list');

    // コントローラー側の admin.application.list 呼び出し（リダイレクト先）にも対応させるエイリアスルート
    Route::get('/admin/stamp_correction_request/list', [ApplicationListController::class, 'index'])
        ->name('admin.application.list');
});
// 管理者用 CSV出力ルート
Route::post('/export', [App\Http\Controllers\Admin\AttendanceCsvExportController::class, 'export'])
    ->middleware(['auth', 'admin']);