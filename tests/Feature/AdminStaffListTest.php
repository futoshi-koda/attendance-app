<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class AdminStaffListTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // ID14: ユーザー情報取得機能（管理者）
    // ==========================================

    /** @test */
    public function 管理者が全一般ユーザーの氏名とメールアドレスを確認できる(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => 2]);
        $user1 = User::factory()->create(['name' => '一般太郎', 'email' => 'taro@example.com', 'role' => 1]);
        $user2 = User::factory()->create(['name' => '一般花子', 'email' => 'hanako@example.com', 'role' => 1]);

        // Act
        $response = $this->actingAs($admin)->get('/admin/staff/list');

        // Assert
        $response->assertStatus(200);
        $response->assertSee('一般太郎');
        $response->assertSee('taro@example.com');
        $response->assertSee('一般花子');
        $response->assertSee('hanako@example.com');
    }

    /** @test */
    public function ユーザーの勤怠情報が正しく表示される(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => 2]);
        $user = User::factory()->create(['name' => 'テスト太郎']);

        $today = Carbon::now();
        Attendance::create([
            'user_id' => $user->id,
            'date' => $today->format('Y-m-d'),
            'clock_in_at' => $today->format('Y-m-d') . ' 09:00:00',
            'clock_out_at' => $today->format('Y-m-d') . ' 18:00:00',
            'status' => 4,
        ]);

        // Act
        $response = $this->actingAs($admin)->get('/admin/attendance/staff/' . $user->id);

        // Assert
        $response->assertStatus(200);
        $response->assertSee('テスト太郎さんの勤怠');
        $response->assertSee($today->format('Y/m'));
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /** @test */
    public function 前月を押下した時に表示月の前月の情報が表示される(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => 2]);
        $user = User::factory()->create();

        $currentMonth = Carbon::now()->firstOfMonth();
        $prevMonth = $currentMonth->copy()->subMonth();

        // Act
        $response = $this->actingAs($admin)->get('/admin/attendance/staff/' . $user->id . '?date=' . $prevMonth->format('Y-m'));

        // Assert
        $response->assertStatus(200);
        $response->assertSee($prevMonth->format('Y/m'));
    }

    /** @test */
    public function 翌月を押下した時に表示月の翌月の情報が表示される(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => 2]);
        $user = User::factory()->create();

        $currentMonth = Carbon::now()->firstOfMonth();
        $nextMonth = $currentMonth->copy()->addMonth();

        // Act
        $response = $this->actingAs($admin)->get('/admin/attendance/staff/' . $user->id . '?date=' . $nextMonth->format('Y-m'));

        // Assert
        $response->assertStatus(200);
        $response->assertSee($nextMonth->format('Y/m'));
    }

    /** @test */
    public function 詳細を押下するとその日の勤怠詳細画面に遷移する(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => 2]);
        $user = User::factory()->create();

        $today = Carbon::now();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today->format('Y-m-d'),
            'clock_in_at' => $today->format('Y-m-d') . ' 09:00:00',
            'clock_out_at' => $today->format('Y-m-d') . ' 18:00:00',
            'status' => 4,
        ]);

        // Act
        $response = $this->actingAs($admin)->get('/admin/attendance/staff/' . $user->id);

        // Assert
        $response->assertStatus(200);
        $response->assertSee(url('/attendance/' . $attendance->id));
    }
}