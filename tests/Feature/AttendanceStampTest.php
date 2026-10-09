<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceStampTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // ID4: 日時取得機能
    // ==========================================

    /** @test */
    public function 現在の日時情報が_u_iと同じ形式で出力されている(): void
    {
        // Arrange
        $now = Carbon::create(2026, 10, 2, 9, 0, 0);
        $this->travelTo($now);

        $user = User::factory()->create(['role' => 1]);

        // Act
        $response = $this->actingAs($user)->get('/attendance');

        // Assert
        $response->assertStatus(200);
        $response->assertSee($now->isoFormat('YYYY年M月D日(ddd)'));
        $response->assertSee('09:00');
    }

    // ==========================================
    // ID5: ステータス確認機能
    // ==========================================

    /** @test */
    public function 勤務外の場合_勤怠ステータスが正しく表示される(): void
    {
        // Arrange: 勤怠データなし
        $user = User::factory()->create(['role' => 1]);

        // Act
        $response = $this->actingAs($user)->get('/attendance');

        // Assert
        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    /** @test */
    public function 出勤中の場合_勤怠ステータスが正しく表示される(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => 1]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in_at' => Carbon::now(),
            'status' => 2,
        ]);

        // データを再読込してアクセサを最新化
        $user->refresh();

        // Act
        $response = $this->actingAs($user)->get('/attendance');

        // Assert
        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    /** @test */
    public function 休憩中の場合_勤怠ステータスが正しく表示される(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => 1]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in_at' => Carbon::now(),
            'status' => 3,
        ]);

        // データを再読込してアクセサを最新化
        $user->refresh();

        // Act
        $response = $this->actingAs($user)->get('/attendance');

        // Assert
        $response->assertStatus(200);
        $response->assertSee('休憩中');
    }

    /** @test */
    public function 退勤済の場合_勤怠ステータスが正しく表示される(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => 1]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in_at' => Carbon::now()->subHours(8),
            'clock_out_at' => Carbon::now(),
            'status' => 4,
        ]);

        // データを再読込してアクセサを最新化
        $user->refresh();

        // Act
        $response = $this->actingAs($user)->get('/attendance');

        // Assert
        $response->assertStatus(200);
        $response->assertSee('退勤済');
    }
    // ==========================================
    // ID6: 出勤機能
    // ==========================================

    /** @test */
    public function 勤務外の場合_出勤ボタンが表示され_押下するとステータスが出勤中になる(): void
    {
        // Arrange
        $user = User::factory()->create(['role' => 1]);

        // Act: 打刻画面を開いて出勤ボタンを押下
        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);

        // Assert: 画面にリダイレクトされ、ステータスが出勤中になること
        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'status' => 2,
        ]);

        // リダイレクト先の画面で「出勤中」が表示されること
        $this->actingAs($user)
            ->get('/attendance')
            ->assertSee('出勤中');
    }

    /** @test */
    public function 出勤は一日一回しかできない(): void
    {
        // Arrange: すでに出勤済みのデータを作成
        $user = User::factory()->create(['role' => 1]);
        Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in_at' => Carbon::now(),
            'status' => 2, // 出勤中
        ]);

        // Act: 再度出勤処理を送信
        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);

        // Assert: レコードが増えていないこと（1件のまま）
        $this->assertDatabaseCount('attendances', 1);
    }

    /** @test */
    public function 出勤時刻が正しくデータベースに保存されている(): void
    {
        // Arrange
        $now = Carbon::create(2026, 10, 2, 9, 0, 0);
        $this->travelTo($now);

        $user = User::factory()->create(['role' => 1]);

        // Act
        $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_in',
        ]);

        // Assert: 打刻時刻が一致すること
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'clock_in_at' => $now->toDateTimeString(),
        ]);
    }
    // ==========================================
    // ID7: 休憩機能
    // ==========================================

    /** @test */
    public function 出勤中の場合_休憩入ボタンが正しく機能しステータスが休憩中になる(): void
    {
        // Arrange: 出勤中の状態を作成
        $user = User::factory()->create(['role' => 1]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in_at' => Carbon::now(),
            'status' => 2, // 出勤中
        ]);

        // Act: 休憩入ボタンを押下
        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);

        // Assert
        $response->assertRedirect('/attendance');

        // ステータスが 3（休憩中）に更新されていること
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 3,
        ]);

        // rests テーブルにレコードが作成されていること
        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
        ]);
    }

    /** @test */
    public function 休憩は一日に何回でもできる(): void
    {
        // Arrange: 過去に1回休憩を完了（休憩戻りまで完了）している出勤中状態
        $user = User::factory()->create(['role' => 1]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in_at' => Carbon::now()->subHours(4),
            'status' => 2, // 出勤中
        ]);

        // 1回目の休憩データ
        $attendance->rests()->create([
            'break_in' => Carbon::now()->subHours(2),
            'break_out' => Carbon::now()->subHour(),
        ]);

        // Act: 2回目の休憩入を押下
        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);

        // Assert
        $response->assertRedirect('/attendance');

        // rests レコードが計2件作成されていること
        $this->assertDatabaseCount('rests', 2);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 3, // 休憩中
        ]);
    }

    /** @test */
    public function 休憩中の場合_休憩戻ボタンが正しく機能しステータスが出勤中に戻る(): void
    {
        // Arrange: 休憩中の状態を作成
        $user = User::factory()->create(['role' => 1]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in_at' => Carbon::now()->subHours(2),
            'status' => 3, // 休憩中
        ]);

        $rest = $attendance->rests()->create([
            'break_in' => Carbon::now()->subMinutes(30),
        ]);

        // Act: 休憩戻ボタンを押下
        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'break_out',
        ]);

        // Assert
        $response->assertRedirect('/attendance');

        // ステータスが 2（出勤中）に戻っていること
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 2,
        ]);

        // break_out に時刻が入っていること
        $this->assertDatabaseMissing('rests', [
            'id' => $rest->id,
            'break_out' => null,
        ]);
    }

    /** @test */
    public function 休憩時刻が正しくデータベースに保存されている(): void
    {
        // Arrange
        $now = Carbon::create(2026, 10, 2, 12, 0, 0);
        $this->travelTo($now);

        $user = User::factory()->create(['role' => 1]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in_at' => Carbon::now()->subHours(3),
            'status' => 2, // 出勤中
        ]);

        // Act: 12:00 に休憩入
        $this->actingAs($user)->post('/attendance', [
            'action' => 'break_in',
        ]);

        // Assert
        $this->assertDatabaseHas('rests', [
            'attendance_id' => $attendance->id,
            'break_in' => $now->toDateTimeString(),
        ]);
    }
    // ==========================================
    // ID8: 退勤機能
    // ==========================================

    /** @test */
    public function 出勤中の場合_退勤ボタンが正しく機能しステータスが退勤済になる(): void
    {
        // Arrange: 出勤中の状態を作成
        $user = User::factory()->create(['role' => 1]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in_at' => Carbon::now()->subHours(8),
            'status' => 2, // 出勤中
        ]);

        // Act: 退勤ボタンを押下
        $response = $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_out',
        ]);

        // Assert
        $response->assertRedirect('/attendance');

        // ステータスが 4（退勤済）に更新されていること
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 4,
        ]);

        // 画面に「退勤済」が表示されていること
        $this->actingAs($user)
            ->get('/attendance')
            ->assertSee('退勤済');
    }

    /** @test */
    public function 退勤時刻が正しくデータベースに保存されている(): void
    {
        // Arrange
        $now = Carbon::create(2026, 10, 2, 18, 0, 0);
        $this->travelTo($now);

        $user = User::factory()->create(['role' => 1]);
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => Carbon::today(),
            'clock_in_at' => Carbon::now()->subHours(8),
            'status' => 2, // 出勤中
        ]);

        // Act: 18:00 に退勤
        $this->actingAs($user)->post('/attendance', [
            'action' => 'clock_out',
        ]);

        // Assert: clock_out_at に時刻が正しく保存されていること
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_out_at' => $now->toDateTimeString(),
        ]);
    }
}
