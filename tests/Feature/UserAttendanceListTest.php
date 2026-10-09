<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // ID9: 勤怠一覧情報取得機能（一般ユーザー）
    // ==========================================

    /** @test */
    public function 勤怠一覧画面に遷移した際に現在の月が表示される(): void
    {
        // Arrange: 2026年10月1日に固定
        $now = Carbon::create(2026, 10, 1, 9, 0, 0);
        $this->travelTo($now);

        $user = User::factory()->create(['role' => 1]);

        // Act: 勤怠一覧画面を取得
        $response = $this->actingAs($user)->get('/attendance/list');

        // Assert: 2026/10 が表示されていること
        $response->assertStatus(200);
        $response->assertSee('2026/10');
    }

    /** @test */
    public function 自分が行った勤怠情報および退勤時刻が勤怠一覧画面で確認できる(): void
    {
        // Arrange
        $now = Carbon::create(2026, 10, 15, 9, 0, 0);
        $this->travelTo($now);

        $user = User::factory()->create(['role' => 1]);

        // 10月15日の勤怠データを作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-10-15',
            'clock_in_at' => '2026-10-15 09:00:00',
            'clock_out_at' => '2026-10-15 18:00:00',
            'status' => 4, // 退勤済
        ]);

        // Act
        $response = $this->actingAs($user)->get('/attendance/list');

        // Assert: 日付フォーマット「10/15(木)」と「09:00」「18:00」が表示されていること
        $response->assertStatus(200);
        $response->assertSee('10/15(木)');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    /** @test */
    public function 前月を押下した時に表示月の前月の情報が表示される(): void
    {
        // Arrange: 2026年10月に固定
        $now = Carbon::create(2026, 10, 1);
        $this->travelTo($now);

        $user = User::factory()->create(['role' => 1]);

        // 9月の勤怠データを作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-09-15',
            'clock_in_at' => '2026-09-15 09:00:00',
            'clock_out_at' => '2026-09-15 18:00:00',
            'status' => 4,
        ]);

        // Act: ?date=2026-09 でアクセス
        $response = $this->actingAs($user)->get('/attendance/list?date=2026-09');

        // Assert: 2026/09 と 9月15日の打刻が表示されること
        $response->assertStatus(200);
        $response->assertSee('2026/09');
        $response->assertSee('09/15(火)');
    }

    /** @test */
    public function 翌月を押下した時に表示月の翌月の情報が表示される(): void
    {
        // Arrange: 2026年10月に固定
        $now = Carbon::create(2026, 10, 1);
        $this->travelTo($now);

        $user = User::factory()->create(['role' => 1]);

        // 11月の勤怠データを作成
        Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-11-15',
            'clock_in_at' => '2026-11-15 09:00:00',
            'clock_out_at' => '2026-11-15 18:00:00',
            'status' => 4,
        ]);

        // Act: ?date=2026-11 でアクセス
        $response = $this->actingAs($user)->get('/attendance/list?date=2026-11');

        // Assert: 2026/11 と 11月15日の打刻が表示されること
        $response->assertStatus(200);
        $response->assertSee('2026/11');
        $response->assertSee('11/15(日)');
    }

    /** @test */
    public function 詳細を押下するとその日の勤怠詳細画面のリンクが含まれている(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => 1]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today()->toDateString(),
            'clock_in_at' => Carbon::now()->subHours(8),
            'clock_out_at' => Carbon::now(),
            'status' => 4,
        ]);

        // Act
        $response = $this->actingAs($user)->get('/attendance/list');

        // Assert: /attendance/{id} の詳細リンクが含まれていること
        $response->assertStatus(200);
        $response->assertSee(url('/attendance/'.$attendance->id));
    }
}
