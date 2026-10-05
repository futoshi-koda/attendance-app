<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // ID10: 勤怠詳細情報取得機能（一般ユーザー）
    // ==========================================

    /** @test */
    public function 勤怠詳細画面にログインユーザーの名前と選択した日付が表示されている(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'テストユーザー']);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-09-05',
            'clock_in_at' => '2026-09-05 09:45:00',
            'clock_out_at' => '2026-09-05 21:45:00',
            'status' => 4,
        ]);

        // Act
        $response = $this->actingAs($user)->get('/attendance/' . $attendance->id);

        // Assert
        $response->assertStatus(200);
        $response->assertSee('テストユーザー');
        $response->assertSee('2026年');
        $response->assertSee('9月5日');
    }

    /** @test */
    public function 出勤時間と退勤時間が正しく表示されている(): void
    {
        // Arrange
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-09-05',
            'clock_in_at' => '2026-09-05 09:45:00',
            'clock_out_at' => '2026-09-05 21:45:00',
            'status' => 4,
        ]);

        // Act
        $response = $this->actingAs($user)->get('/attendance/' . $attendance->id);

        // Assert
        $response->assertStatus(200);
        $response->assertSee('09:45');
        $response->assertSee('21:45');
    }

    /** @test */
    public function 休憩時間が正しく表示されている(): void
    {
        // Arrange
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-09-05',
            'clock_in_at' => '2026-09-05 09:45:00',
            'clock_out_at' => '2026-09-05 21:45:00',
            'status' => 4,
        ]);

        $attendance->rests()->create([
            'break_in' => '2026-09-05 12:00:00',
            'break_out' => '2026-09-05 13:00:00',
        ]);

        // Act
        $response = $this->actingAs($user)->get('/attendance/' . $attendance->id);

        // Assert
        $response->assertStatus(200);
        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }
    // ==========================================
    // ID11: 勤怠詳細情報修正機能（一般ユーザー）
    // ==========================================

    /** @test */
    public function 出勤時間が退勤時間より後の場合_エラーメッセージが表示される(): void
    {
        // Arrange
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-09-05',
            'clock_in_at' => '2026-09-05 09:00:00',
            'clock_out_at' => '2026-09-05 18:00:00',
            'status' => 4,
        ]);

        // Act: 出勤時間(19:00) > 退勤時間(18:00) で送信
        $response = $this->actingAs($user)->post('/attendance/' . $attendance->id, [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'remarks' => '時間修正',
        ]);

        // Assert: セッションエラーが含まれていること
        $response->assertSessionHasErrors();
    }

    /** @test */
    public function 休憩開始時間が退勤時間より後の場合_エラーメッセージが表示される(): void
    {
        // Arrange
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-09-05',
            'clock_in_at' => '2026-09-05 09:00:00',
            'clock_out_at' => '2026-09-05 18:00:00',
            'status' => 4,
        ]);

        // Act: 休憩開始(19:00) > 退勤時間(18:00) で送信
        $response = $this->actingAs($user)->post('/attendance/' . $attendance->id, [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'rests' => [
                ['break_in' => '19:00', 'break_out' => '20:00']
            ],
            'remarks' => '休憩時間修正',
        ]);

        // Assert
        $response->assertSessionHasErrors();
    }

    /** @test */
    public function 休憩終了時間が退勤時間より後の場合_エラーメッセージが表示される(): void
    {
        // Arrange
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-09-05',
            'clock_in_at' => '2026-09-05 09:00:00',
            'clock_out_at' => '2026-09-05 18:00:00',
            'status' => 4,
        ]);

        // Act: 休憩終了(19:00) > 退勤時間(18:00) で送信
        $response = $this->actingAs($user)->post('/attendance/' . $attendance->id, [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'rests' => [
                ['break_in' => '12:00', 'break_out' => '19:00']
            ],
            'remarks' => '休憩時間修正',
        ]);

        // Assert
        $response->assertSessionHasErrors();
    }
    /** @test */
    public function 備考欄が未入力の場合_エラーメッセージが表示される(): void
    {
        // Arrange
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-09-05',
            'clock_in_at' => '2026-09-05 09:00:00',
            'clock_out_at' => '2026-09-05 18:00:00',
            'status' => 4,
        ]);

        // Act: 備考(comment)を空で送信
        $response = $this->actingAs($user)->post('/attendance/' . $attendance->id, [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'comment' => '',
        ]);

        // Assert: comment のバリデーションエラーが発生すること
        $response->assertSessionHasErrors(['comment']);
    }

    /** @test */
    public function 正常な入力で修正申請を送信すると承認待ちステータスで保存される(): void
    {
        // Arrange
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => '2026-09-05',
            'clock_in_at' => '2026-09-05 09:00:00',
            'clock_out_at' => '2026-09-05 18:00:00',
            'status' => 4,
        ]);

        // Act: コントローラーのフィールド名に合わせて送信
        $response = $this->actingAs($user)->post('/attendance/' . $attendance->id, [
            'new_clock_in' => '09:30',
            'new_clock_out' => '18:30',
            'comment' => '電車遅延のため修正',
        ]);

        // Assert: データベースのステータスが 5（承認待ち）になっていること
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 5,
            'remarks' => '電車遅延のため修正',
        ]);
    }
}