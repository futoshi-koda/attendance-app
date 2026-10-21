<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\AttendanceSeeder;
use Database\Seeders\RestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * テスト実行前の準備
     */
    protected function setUp(): void
    {
        parent::setUp();

        // いつテストを実行しても10月基準（2026年10月20日）で正しく計算されるよう日付を固定
        Carbon::setTestNow('2026-10-20 00:00:00');
    }

    /**
     * テスト終了後のクリーンアップ
     */
    protected function tearDown(): void
    {
        // テスト終了後は必ず時間を元に戻す
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function 認証済みユーザーのマイ勤怠レポートが正しい統計データと月次推移を含んで表示される(): void
    {
        // Arrange: ユーザー1を作成し、勤怠と休憩のシーダーを両方実行
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $this->seed(AttendanceSeeder::class);
        $this->seed(RestSeeder::class);

        // Act: ユーザー1としてレポート画面へアクセス
        $response = $this->actingAs($user1)
            ->get('/attendance/report');

        // Assert: ステータス200確認
        $response->assertStatus(200);

        // 1. 基本サマリーの検証（ユーザー１の意図的データにて検証）
        // ・総労働時間: 744時間 = 44640分
        // ・総残業時間: 10時間 = 600分
        // ・平均労働時間/日: 8時間5分 = 485分
        $response->assertViewHas('summary', function ($summary) {
            return $summary['total_work_minutes'] === 44640
                && $summary['total_overtime_minutes'] === 600
                && $summary['avg_work_minutes'] === 485;
        });

        // 2. 月次推移の検証（6ヶ月分のデータと必須キーの網羅）
        $response->assertViewHas('monthlyTrend', function ($monthlyTrend) {
            return is_array($monthlyTrend)
                && count($monthlyTrend) === 6
                && collect($monthlyTrend)->every(function ($row) {
                    return isset($row['month'], $row['work_minutes'], $row['overtime_minutes']);
                });
        });

        // 3. 異常検知の検証
        $response->assertViewHas('anomalies', function ($anomalies) {
            return $anomalies['late_count'] === 2          // 遅刻回数: 2回
                && $anomalies['early_leave_count'] === 1   // 早退回数: 1回
                && $anomalies['long_work_count'] === 1;    // 長時間労働日数: 1日
        });
    }

    /** @test */
    public function 勤怠記録がないユーザーの場合は安全に各項目が0や空で処理される(): void
    {
        // Arrange: 勤怠データを一切持たない新しいテストユーザーをファクトリーで作成
        $emptyUser = User::factory()->create();

        // Act: 勤怠データのないユーザーとしてレポート画面へアクセス
        $response = $this->actingAs($emptyUser)
            ->get('/attendance/report');

        // Assert: エラーにならず、ステータス200で正常に表示されること
        $response->assertStatus(200);

        // 各集計値が安全に 0 として返っていることを検証
        $response->assertViewHas('summary', function ($summary) {
            return $summary['total_work_minutes'] === 0
                && $summary['total_overtime_minutes'] === 0
                && $summary['avg_work_minutes'] === 0;
        });

        // 異常検知もすべて 0件 であること
        $response->assertViewHas('anomalies', function ($anomalies) {
            return $anomalies['late_count'] === 0
                && $anomalies['early_leave_count'] === 0
                && $anomalies['long_work_count'] === 0;
        });
    }

    /** @test */
    public function 未認証ユーザーがレポートにアクセスした場合はログイン画面にリダイレクトされる(): void
    {
        $response = $this->get('/attendance/report');

        $response->assertRedirect('/login');
    }
}