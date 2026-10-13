<?php

namespace App\Providers;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\RegisterResponse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ログインエラーメッセージ
        Lang::addLines([
            'auth.failed' => 'ログイン情報が登録されていません',
        ], 'en');

        // ログイン成功時のリダイレクト制御（メール未認証の考慮）
        $this->app->singleton(LoginResponse::class, function () {
            return new class implements LoginResponse {
                public function toResponse($request)
                {
                    $user = auth()->user();

                    // 管理者の場合は管理者用の画面へ
                    if ($user->role === 2) {
                        return redirect()->route('admin.application.list');
                    }

                    // 一般ユーザーでメール未認証の場合はメール認証誘導画面へ
                    if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail()) {
                        return redirect()->route('verification.notice');
                    }

                    // 認証済みの場合は通常の打刻画面へ
                    return redirect()->intended(config('fortify.home'));
                }
            };
        });

        // 新規登録成功時のリダイレクト制御
        $this->app->singleton(RegisterResponse::class, function () {
            return new class implements RegisterResponse {
                public function toResponse($request)
                {
                    // 新規登録直後は必ずメール認証画面へリダイレクト
                    return redirect()->route('verification.notice');
                }
            };
        });
    }
}