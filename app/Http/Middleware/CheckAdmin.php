<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. 未認証（未ログイン）の場合は管理者ログイン画面へリダイレクト
        if (!Auth::check()) {
            return redirect()->route('admin.login');
        }

        // 2. ログイン済みだが管理者（role === 2）でない場合（一般ユーザー）は 403 エラー
        if (Auth::user()->role !== 2) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}