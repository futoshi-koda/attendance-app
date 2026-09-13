<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function store(LoginRequest $request)
    {
        // メールアドレス・パスワードに加えて「role = 2（管理者）」の条件を追加して認証を試みる
        $credentials = array_merge($request->only('email', 'password'), ['role' => 2]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // 管理者用画面（仮ルート）へリダイレクト
            return redirect()->intended(route('admin.attendance.list'));
        }

        // roleが一致しない、またはパスワード誤りの場合は認証失敗エラーを返す
        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }
}