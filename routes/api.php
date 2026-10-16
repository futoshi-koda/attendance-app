<?php

use App\Http\Controllers\Api\AttendanceRecordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API v1 グループ
Route::prefix('v1')->group(function () {

    // -----------------------------------------------------------------
    // 読み取り系エンドポイント（認証不要）
    // -----------------------------------------------------------------
    Route::get('/attendance-records', [AttendanceRecordController::class, 'index']);
    Route::get('/attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'show']);

    // -----------------------------------------------------------------
    // 書き込み系エンドポイント（Sanctum認証必須）
    // -----------------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/attendance-records', [AttendanceRecordController::class, 'store']);
        Route::put('/attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'update']);
        Route::delete('/attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'destroy']);
    });
});
