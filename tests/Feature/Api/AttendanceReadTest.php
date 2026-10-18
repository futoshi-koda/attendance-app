<?php

namespace Tests\Feature\Api;

use App\Models\Attendance;
use App\Models\User;
use Database\Seeders\AttendanceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReadTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 勤怠一覧がJSONで取得できる(): void
    {
        // Arrange
        User::factory()->create(['email' => 'user1@example.com']);
        $this->seed(AttendanceSeeder::class);

        // Act
        $response = $this->getJson('/api/v1/attendance-records');

        // Assertgi
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user' => [
                            'id',
                            'name',
                        ],
                        'date',
                        'clock_in',
                        'clock_out',
                        'applications',
                        'comment',
                    ]
                ],
                'links' => [
                    'first',
                    'last',
                    'prev',
                    'next',
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);

        $this->assertGreaterThan(0, Attendance::count());
    }

    /** @test */
    public function 指定したIDの勤怠詳細がJSONで取得できる(): void
    {
        // Arrange
        User::factory()->create(['email' => 'user1@example.com']);
        $this->seed(AttendanceSeeder::class);

        $attendance = Attendance::first();

        // Act
        $response = $this->getJson("/api/v1/attendance-records/{$attendance->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $attendance->id,
                    'date' => $attendance->date,
                ]
            ]);
    }

    /** @test */
    public function 存在しないIDでリクエストした場合は404エラーが返る(): void
    {
        // Arrange
        $nonExistentId = 99999;

        // Act
        $response = $this->getJson("/api/v1/attendance-records/{$nonExistentId}");

        // Assert
        $response->assertStatus(404);
    }
}