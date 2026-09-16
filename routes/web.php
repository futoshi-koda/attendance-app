<?php

use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceStampController;

// トップページアクセス時は一般ログイン画面へリダイレクト
Route::get('/', function () {
    return redirect()->route('login');
});

// 管理者ログイン画面表示（GET）
Route::get('/admin/login', function () {
    return view('admin.admin-login');
})->name('admin.login');

// 【変更】管理者ログイン処理（POST）
Route::post('/admin/login', [AdminAuthenticatedSessionController::class, 'store']);

// --------------------------------------------------
// 認証済みユーザー用ルート（auth ミドルウェア）
// --------------------------------------------------
Route::middleware('auth')->group(function () {

    // ログイン後の振分トップ（リダイレクト受け皿）
    Route::get('/home', function () {
        $user = auth()->user();

        // role が 2 の場合は管理者
        if ($user->role === 2) {
            return redirect()->route('admin.attendance.list');
        }

        return redirect()->route('attendance.register');
    })->name('home');

    // 勤怠打刻画面（一般ユーザーのログイン後・登録後のリダイレクト先）
    Route::get('/attendance', [AttendanceStampController::class, 'show'])->name('attendance.show');
    // 打刻処理用 POST ルートを追加
    Route::post('/attendance', [AttendanceStampController::class, 'store'])->name('attendance.store');

    // 管理者用（仮ルート）
    Route::get('/admin/attendance/list', function () {
        $user = auth()->user();
        return "管理者ログイン成功！ようこそ {$user->name} 管理者（日次勤怠一覧画面：準備中）";
    })->name('admin.attendance.list');
});