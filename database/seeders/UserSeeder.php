<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ユーザー1 (一般ユーザー / メール認証済み)
        User::factory()->create([
            'name' => 'テストユーザー1',
            'email' => 'user1@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 1,
        ]);

        // ユーザー2 (一般ユーザー / メール認証済み)
        User::factory()->create([
            'name' => 'テストユーザー2',
            'email' => 'user2@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 1,
        ]);

        // ユーザー3 (管理者ユーザー / メール認証済み)
        User::factory()->admin()->create([
            'name' => '管理者ユーザー3',
            'email' => 'user3@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // その他、画面一覧表示や検索テスト用のダミー一般ユーザーを5人追加
        User::factory()->count(5)->create([
            'email_verified_at' => now(),
            'role' => 1,
        ]);
    }
}