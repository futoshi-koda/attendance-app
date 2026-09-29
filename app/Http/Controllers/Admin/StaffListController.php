<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StaffListController extends Controller
{
    /**
     * スタッフ一覧（PG10）を表示
     */
    public function index()
    {
        // 全ユーザーをID昇順で取得（Blade側の $users に渡す）
        $users = User::orderBy('id', 'asc')->get();

        return view('admin.staff-list', compact('users'));
    }
}