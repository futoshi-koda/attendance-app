<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 名前が未入力の場合_バリデーションメッセージが表示される(): void
    {
        // Act
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors(['name']);
    }

    /** @test */
    public function メールアドレスが未入力の場合_バリデーションメッセージが表示される(): void
    {
        // Act
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $response->assertSessionHasErrors(['email']);
    }

    /** @test */
    public function パスワードが8文字未満の場合_バリデーションメッセージが表示される(): void
    {
        // Act
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'pass123', // 7文字
            'password_confirmation' => 'pass123',
        ]);

        // Assert
        $response->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function パスワードが一致しない場合_バリデーションメッセージが表示される(): void
    {
        // Act
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
        ]);

        // Assert
        $response->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function パスワードが未入力の場合_バリデーションメッセージが表示される(): void
    {
        // Act
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        // Assert
        $response->assertSessionHasErrors(['password']);
    }

    /** @test */
    public function フォームに内容が入力されていた場合_データが正常に保存される(): void
    {
        // Act
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Assert
        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect('/attendance');
    }
}