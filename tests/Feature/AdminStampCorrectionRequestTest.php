<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Tests\TestCase;

class AdminStampCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    // ==========================================
    // ID15: 勤怠情報修正機能（管理者）
    // ==========================================

    /** @test */
    public function 承認待ちの修正申請が全て表示されている(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => 2]);
        $user = User::factory()->create(['name' => '申請太郎']);

        $today = Carbon::now();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today->format('Y-m-d'),
            'clock_in_at' => $today->format('Y-m-d') . ' 09:00:00',
            'clock_out_at' => $today->format('Y-m-d') . ' 18:00:00',
            'status' => 5, // 承認待ち
            'remarks' => '修正理由テスト',
            'application_date' => $today->format('Y-m-d'),
        ]);

        // Act
        $response = $this->actingAs($admin)->get('/admin/stamp_correction_request/list');

        // Assert
        $response->assertStatus(200);
        $response->assertSee('承認待ち');
        $response->assertSee('申請太郎');
        $response->assertSee('修正理由テスト');
    }

    /** @test */
    public function 承認済みの修正申請が全て表示されている(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => 2]);
        $user = User::factory()->create(['name' => '承認済花子']);

        $today = Carbon::now();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today->format('Y-m-d'),
            'clock_in_at' => $today->format('Y-m-d') . ' 09:00:00',
            'clock_out_at' => $today->format('Y-m-d') . ' 18:00:00',
            'status' => 6, // 承認済み
            'remarks' => '承認済み理由テスト',
            'application_date' => $today->format('Y-m-d'),
        ]);

        // Act
        $response = $this->actingAs($admin)->get('/admin/stamp_correction_request/list');

        // Assert
        $response->assertStatus(200);
        $response->assertSee('承認済み');
        $response->assertSee('承認済花子');
        $response->assertSee('承認済み理由テスト');
    }

    /** @test */
    public function 修正申請の詳細内容が正しく表示されている(): void
    {
        // Arrange
        $admin = User::factory()->create(['role' => 2]);
        $user = User::factory()->create(['name' => '詳細確認太郎']);

        $today = Carbon::now();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => $today->format('Y-m-d'),
            'clock_in_at' => $today->format('Y-m-d') . ' 09:00:00',
            'clock_out_at' => $today->format('Y-m-d') . ' 18:00:00',
            'status' => 5,
            'remarks' => '詳細画面理由テスト',
            'application_date' => $today->format('Y-m-d'),
        ]);

        // Act
        $response = $this->actingAs($admin)->get('/stamp_correction_request/approve/' . $attendance->id);

        // Assert
        $response->assertStatus(200);
        $response->assertSee('詳細確認太郎');
        $response->assertSee('詳細画面理由テスト');
        $response->assertSee('承認');
    }

    /** @test */
    public function 修正申請の承認処理が正しく行われる(): void
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
            'status' => 5,
            'remarks' => '承認実行テスト',
            'application_date' => $today->format('Y-m-d'),
        ]);

        // Act
        $response = $this->actingAs($admin)->post('/stamp_correction_request/approve/' . $attendance->id);

        // Assert
        $response->assertRedirect('/admin/stamp_correction_request/list');
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 6,
        ]);
    }
}