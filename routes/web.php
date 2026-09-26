<?php

use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceStampController;
use App\Http\Controllers\User\AttendanceController;
use App\Http\Controllers\User\AttendanceDetailController;
use App\Http\Controllers\User\ApplicationListController;
use App\Http\Controllers\Admin\DailyAttendanceController;

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

// --------------------------------------------------
// 認証済みユーザー用ルート（一般ユーザー・管理者共通）
// --------------------------------------------------
Route::middleware('auth')->group(function () {

    // ログイン後の振分トップ（リダイレクト受け皿）
    Route::get('/home', function () {
        $user = auth()->user();

        if ($user->role === 2) {
            return redirect()->route('admin.attendance.list');
        }

        // ★ 'attendance.register' から 'attendance.show' に修正
        return redirect()->route('attendance.show');
    })->name('home');

    // --------------------------------------------------
    // 一般ユーザー用機能（auth のみ）
    // --------------------------------------------------
    Route::get('/attendance', [AttendanceStampController::class, 'show'])->name('attendance.show');
    Route::post('/attendance', [AttendanceStampController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.list');

    Route::get('/attendance/{id}', [AttendanceDetailController::class, 'show'])->name('attendance.detail');
    Route::post('/attendance/{id}', [AttendanceDetailController::class, 'update'])->name('attendance.update');
    Route::get('/application/{id}', [AttendanceDetailController::class, 'show'])->name('application.detail');

    Route::get('/stamp_correction_request/list', [ApplicationListController::class, 'index'])->name('application.list');
});

// --------------------------------------------------
// 管理者専用ルート（auth + admin ミドルウェア）
// --------------------------------------------------
Route::middleware(['auth', 'admin'])->prefix('admin')->as('admin.')->group(function () {

    // 管理者用日次勤怠一覧画面（PG08）
    Route::get('/attendance/list', [DailyAttendanceController::class, 'index'])->name('attendance.list');

});