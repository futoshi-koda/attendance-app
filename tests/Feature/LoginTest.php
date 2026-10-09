<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // ID2: 一般ユーザー ログイン認証テスト
    // ==========================================

    /** @test */
    public function 一般ユーザー_メールアドレスが未入力の場合_バリデーションメッセージが表示される(): void
    {
        // Act
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function 一般ユーザー_パスワードが未入力の場合_バリデーションメッセージが表示される(): void
    {
        // Act
        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => '',
        ]);

        // Assert
        $response->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function 一般ユーザー_登録内容と一致しない場合_バリデーションメッセージが表示される(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
            'role' => 1, // 一般ユーザー
        ]);

        // Act
        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'wrong_password',
        ]);

        // Assert
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /** @test */
    public function 一般ユーザー_正しい情報が入力された場合_ログインして一般画面へ遷移する(): void
    {
        // Arrange
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
            'role' => 1,
        ]);

        // Act
        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/attendance');
    }

    // ==========================================
    // ID3: 管理者 ログイン認証テスト
    // ==========================================

    /** @test */
    public function 管理者_メールアドレスが未入力の場合_バリデーションメッセージが表示される(): void
    {
        // Act
        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function 管理者_パスワードが未入力の場合_バリデーションメッセージが表示される(): void
    {
        // Act
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        // Assert
        $response->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function 管理者_登録内容と一致しない場合_バリデーションメッセージが表示される(): void
    {
        // Arrange
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 2, // 管理者
        ]);

        // Act
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong_password',
        ]);

        // Assert
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /** @test */
    public function 管理者_正しい情報が入力された場合_ログインして管理者画面へ遷移する(): void
    {
        // Arrange
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'role' => 2,
        ]);

        // Act
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        // Assert
        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect('/admin/attendance/list');
    }
}
