<?php

namespace Tests\Feature\Api;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceWriteTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 正常なデータでPOSTリクエストすると勤怠が作成される(): void
    {
        // Arrange: 認証ユーザーを用意してログイン状態にする
        $user = User::factory()->create();
        $data = [
            'date' => '2026-10-18',
            'clock_in' => '09:00:00',
            'clock_out' => '18:00:00',
            'comment' => '通常勤務',
        ];

        // Act: 認証した状態で POST
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/attendance-records', $data);

        // Assert
        $response->assertStatus(201);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'date' => '2026-10-18',
        ]);
    }

    /** @test */
    public function バリデーションエラー時に422とエラーメッセージが返る(): void
    {
        $user = User::factory()->create();
        $invalidData = [
            'date' => '',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/attendance-records', $invalidData);

        $response->assertStatus(422);
    }

    /** @test */
    public function 既存勤怠に対してPUTリクエストすると更新される(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-10-18',
            'clock_in_at' => '2026-10-18 09:00:00',
            'clock_out_at' => '2026-10-18 18:00:00',
        ]);

        $updateData = [
            'new_clock_in' => '08:30',
            'new_clock_out' => '17:30',
            'comment' => '修正理由のテスト',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/attendance-records/{$attendance->id}", $updateData);

        $response->assertStatus(200);
    }

    /** @test */
    public function 存在しないIDに対してPUTリクエストした場合は404が返る(): void
    {
        $user = User::factory()->create();
        $nonExistentId = 9999;
        $updateData = [
            'new_clock_in' => '09:00',
            'new_clock_out' => '18:00',
            'comment' => 'テスト',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/v1/attendance-records/{$nonExistentId}", $updateData);

        $response->assertStatus(404);
    }

    /** @test */
    public function 既存勤怠に対してDELETEリクエストすると削除される(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/attendance-records/{$attendance->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('attendances', [
            'id' => $attendance->id,
        ]);
    }

    /** @test */
    public function 存在しないIDに対してDELETEリクエストした場合は404が返る(): void
    {
        $user = User::factory()->create();
        $nonExistentId = 9999;

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/attendance-records/{$nonExistentId}");

        $response->assertStatus(404);
    }
    /** @test */
    public function 未認証時に書き込み系APIにアクセスすると401エラーが返る(): void
    {
        // 認証（actingAs）をせずに POST リクエストを送る
        $response = $this->postJson('/api/v1/attendance-records', [
            'date' => '2026-10-18',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function 他ユーザーの勤怠を更新しようとすると403エラーが返る(): void
    {
        // ユーザーA と ユーザーB を作成
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // ユーザーAの勤怠データを作成
        $attendance = Attendance::factory()->create([
            'user_id' => $userA->id,
            'date' => '2026-10-18',
        ]);

        // ユーザーBとしてログインし、ユーザーAの勤怠をPUTで更新しようとする
        $response = $this->actingAs($userB, 'sanctum')
            ->putJson("/api/v1/attendance-records/{$attendance->id}", [
                'new_clock_in' => '09:00',
                'new_clock_out' => '18:00',
                'comment' => '不正な更新',
            ]);

        // 認可エラー（403 Forbidden）が返ることを確認
        $response->assertStatus(403);
    }
}