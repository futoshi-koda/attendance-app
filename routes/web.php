<?php

use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceStampController;
use App\Http\Controllers\User\AttendanceController;
use App\Http\Controllers\User\AttendanceDetailController;
use App\Http\Controllers\User\ApplicationListController;

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
// 認証済みユーザー用ルート（auth ミドルウェア）
// --------------------------------------------------
Route::middleware('auth')->group(function () {

    // ログイン後の振分トップ（リダイレクト受け皿）
    Route::get('/home', function () {
        $user = auth()->user();

        if ($user->role === 2) {
            return redirect()->route('admin.attendance.list');
        }

        return redirect()->route('attendance.register');
    })->name('home');

    // --------------------------------------------------
    // 一般ユーザー：勤怠・打刻
    // --------------------------------------------------
    Route::get('/attendance', [AttendanceStampController::class, 'show'])->name('attendance.show');
    Route::post('/attendance', [AttendanceStampController::class, 'store'])->name('attendance.store');
    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.list');

    // --------------------------------------------------
    // 一般ユーザー：勤怠詳細・修正申請
    // --------------------------------------------------
    Route::get('/attendance/{id}', [AttendanceDetailController::class, 'show'])->name('attendance.detail');
    Route::post('/attendance/{id}', [AttendanceDetailController::class, 'update'])->name('attendance.update');

    // ★ 申請一覧の Blade （/application/{id}）に対応するルートを追加
    Route::get('/application/{id}', [AttendanceDetailController::class, 'show'])->name('application.detail');

    // --------------------------------------------------
    // 一般ユーザー：申請一覧
    // --------------------------------------------------
    Route::get('/stamp_correction_request/list', [ApplicationListController::class, 'index'])->name('application.list');

    // --------------------------------------------------
    // 管理者用ルート（仮）
    // --------------------------------------------------
    Route::get('/admin/attendance/list', function () {
        $user = auth()->user();
        return "管理者ログイン成功！ようこそ {$user->name} 管理者（日次勤怠一覧画面：準備中）";
    })->name('admin.attendance.list');
});