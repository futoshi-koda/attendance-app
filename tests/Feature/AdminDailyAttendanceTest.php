<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class AdminDailyAttendanceTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // ID12: 勤怠一覧情報取得機能（管理者）
    // ==========================================

    /** @test */
    public function 管理者ユーザーは日次勤怠一覧画面に遷移した際に現在の日付が表示される(): void
    {
        // Arrange: 2026年10月5日に固定
        $now = Carbon::create(2026, 10, 5, 9, 0, 0);
        $this->travelTo($now);

        $admin = User::factory()->create(['role' => 2]); // 管理者ユーザー

        // Act
        $response = $this->actingAs($admin)->get('/admin/attendance/list'); // ※ルートURLに合わせて調整してください

        // Assert
        $response->assertStatus(200);
        $response->assertSee('2026/10/05'); // Bladeの表示形式に合わせて必要に応じて '2026-10-05' 等に変更してください
    }

    /** @test */
    public function その日になされた全ユーザーの勤怠情報が正確に確認できる(): void
    {
        // Arrange
        $now = Carbon::create(2026, 10, 5, 9, 0, 0);
        $this->travelTo($now);

        $admin = User::factory()->create(['role' => 2]);
        $user1 = User::factory()->create(['name' => '一般ユーザーA']);
        $user2 = User::factory()->create(['name' => '一般ユーザーB']);

        // 本日の勤怠データ作成
        Attendance::create([
            'user_id' => $user1->id,
            'date' => '2026-10-05',
            'clock_in_at' => '2026-10-05 09:00:00',
            'clock_out_at' => '2026-10-05 18:00:00',
            'status' => 4,
        ]);

        Attendance::create([
            'user_id' => $user2->id,
            'date' => '2026-10-05',
            'clock_in_at' => '2026-10-05 10:00:00',
            'status' => 2,
        ]);

        // Act
        $response = $this->actingAs($admin)->get('/admin/attendance/list');

        // Assert: 両方のユーザー名や打刻時間が画面に表示されていること
        $response->assertStatus(200);
        $response->assertSee('一般ユーザーA');
        $response->assertSee('一般ユーザーB');
        $response->assertSee('09:00');
        $response->assertSee('10:00');
    }

    /** @test */
    public function 前日を押下した時に前の日の勤怠情報が表示される(): void
    {
        // Arrange
        $now = Carbon::create(2026, 10, 5, 9, 0, 0);
        $this->travelTo($now);

        $admin = User::factory()->create(['role' => 2]);
        $user = User::factory()->create(['name' => '前日打刻ユーザー']);

        // 10月4日の勤怠データ作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-10-04',
            'clock_in_at' => '2026-10-04 09:00:00',
            'clock_out_at' => '2026-10-04 18:00:00',
            'status' => 4,
        ]);

        // Act: ?date=2026-10-04 でアクセス
        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=2026-10-04');

        // Assert
        $response->assertStatus(200);
        $response->assertSee('2026/10/04');
        $response->assertSee('前日打刻ユーザー');
    }

    /** @test */
    public function 翌日を押下した時に次の日の勤怠情報が表示される(): void
    {
        // Arrange
        $now = Carbon::create(2026, 10, 5, 9, 0, 0);
        $this->travelTo($now);

        $admin = User::factory()->create(['role' => 2]);
        $user = User::factory()->create(['name' => '翌日打刻ユーザー']);

        // 10月6日の勤怠データ作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-10-06',
            'clock_in_at' => '2026-10-06 09:00:00',
            'clock_out_at' => '2026-10-06 18:00:00',
            'status' => 4,
        ]);

        // Act: ?date=2026-10-06 でアクセス
        $response = $this->actingAs($admin)->get('/admin/attendance/list?date=2026-10-06');

        // Assert
        $response->assertStatus(200);
        $response->assertSee('2026/10/06');
        $response->assertSee('翌日打刻ユーザー');
    }
}