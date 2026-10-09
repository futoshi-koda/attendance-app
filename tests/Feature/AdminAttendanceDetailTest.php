<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Rest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 勤怠詳細画面に表示されるデータが選択したものになっている(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => 2]);
        $user = User::factory()->create(['name' => 'テスト太郎']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-10-05',
            'clock_in_at' => '2026-10-05 09:00:00',
            'clock_out_at' => '2026-10-05 18:00:00',
            'status' => 4,
            'remarks' => 'テスト備考',
        ]);

        Rest::create([
            'attendance_id' => $attendance->id,
            'break_in' => '2026-10-05 12:00:00',
            'break_out' => '2026-10-05 13:00:00',
        ]);

        // Act
        $response = $this->actingAs($admin)->get('/admin/attendance/'.$attendance->id);

        // Assert
        $response->assertStatus(200);
        $response->assertSee('テスト太郎');
        $response->assertSee('2026年');
        $response->assertSee('10月05日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('13:00');
        $response->assertSee('テスト備考');
    }

    /** @test */
    public function 出勤時間が退勤時間より後になっている場合_エラーメッセージが表示される(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => 2]);
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-10-05',
            'clock_in_at' => '2026-10-05 09:00:00',
            'clock_out_at' => '2026-10-05 18:00:00',
            'status' => 4,
        ]);

        // Act: 出勤時間(19:00) > 退勤時間(18:00)
        $response = $this->actingAs($admin)->post('/attendance/'.$attendance->id, [
            'new_clock_in' => '19:00',
            'new_clock_out' => '18:00',
            'comment' => '修正理由',
        ]);

        // Assert: new_clock_out 側でエラーを検知している場合もあるため、anyOf でいずれかのフィールドのエラーを確認
        $response->assertSessionHasErrors();
        $this->assertTrue(
            session('errors')->has('new_clock_in') || session('errors')->has('new_clock_out'),
            '出勤時間または退勤時間にエラーメッセージがセットされていません。'
        );
    }

    /** @test */
    public function 休憩開始時間が退勤時間より後になっている場合_エラーメッセージが表示される(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => 2]);
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-10-05',
            'clock_in_at' => '2026-10-05 09:00:00',
            'clock_out_at' => '2026-10-05 18:00:00',
            'status' => 4,
        ]);

        // Act
        $response = $this->actingAs($admin)->post('/attendance/'.$attendance->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['0' => '19:00'],
            'new_break_out' => ['0' => '19:30'],
            'comment' => '修正理由',
        ]);

        // Assert
        $response->assertSessionHasErrors(['new_break_in.0']);
    }

    /** @test */
    public function 休憩終了時間が退勤時間より後になっている場合_エラーメッセージが表示される(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => 2]);
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-10-05',
            'clock_in_at' => '2026-10-05 09:00:00',
            'clock_out_at' => '2026-10-05 18:00:00',
            'status' => 4,
        ]);

        // Act
        $response = $this->actingAs($admin)->post('/attendance/'.$attendance->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'new_break_in' => ['0' => '17:30'],
            'new_break_out' => ['0' => '19:00'],
            'comment' => '修正理由',
        ]);

        // Assert
        $response->assertSessionHasErrors(['new_break_out.0']);
    }

    /** @test */
    public function 備考欄が未入力の場合のエラーメッセージが表示される(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => 2]);
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-10-05',
            'clock_in_at' => '2026-10-05 09:00:00',
            'clock_out_at' => '2026-10-05 18:00:00',
            'status' => 4,
        ]);

        // Act
        $response = $this->actingAs($admin)->post('/attendance/'.$attendance->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'comment' => '',
        ]);

        // Assert
        $response->assertSessionHasErrors(['comment']);
    }
}
