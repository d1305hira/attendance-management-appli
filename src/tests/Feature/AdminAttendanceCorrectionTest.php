<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;
use App\Models\User;
use App\Models\Worktime;
use App\Models\WorktimeRequest;
use App\Models\WorktimeRequestBreak;

class AdminAttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    /* ============================================================
        共通処理まとめ
    ============================================================ */

    private function loginAsAdmin()
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    private function createUserWithWorktime($name = 'user', $date = '2026-01-01')
    {
        $user = User::factory()->create(['name' => $name]);

        $worktime = Worktime::factory()->create([
            'user_id' => $user->id,
            'date' => $date,
            'start_time' => "$date 09:00:00",
            'end_time'   => "$date 18:00:00",
        ]);

        return [$user, $worktime];
    }

    private function createCorrectionRequest($worktime, $date = '2026-01-01')
    {
        return WorktimeRequest::factory()->create([
            'worktime_id' => $worktime->id,
            'requested_start_time' => "$date 10:00:00",
            'requested_end_time'   => "$date 19:00:00",
            'reason' => '修正します',
            'approval_status' => 0,
        ]);
    }

    private function createCorrectionBreak($request, $date = '2026-01-01')
    {
        return WorktimeRequestBreak::factory()->create([
            'worktime_request_id' => $request->id,
            'break_start' => "$date 13:00:00",
            'break_end'   => "$date 14:00:00",
        ]);
    }

    /* ============================================================
        ここからテスト本体
    ============================================================ */

    //!勤怠情報修正--承認待ち一覧
    public function test_admin_attendance_pending_correction_requests()
    {
        $this->loginAsAdmin();

        [$user1, $worktime1] = $this->createUserWithWorktime('user1');
        [$user2, $worktime2] = $this->createUserWithWorktime('user2', '2026-01-02');

        $req1 = $this->createCorrectionRequest($worktime1);
        $this->createCorrectionBreak($req1);

        $req2 = $this->createCorrectionRequest($worktime2, '2026-01-02');
        $this->createCorrectionBreak($req2, '2026-01-02');

        $response = $this->get('/stamp_correction_request/list');
        $response->assertStatus(200)
            ->assertSee('承認待ち')
            ->assertSee('user1')
            ->assertSee('user2');
    }

    //!勤怠情報修正--承認済み一覧
    public function test_admin_attendance_approved_correction_requests()
    {
        $this->loginAsAdmin();

        [$user1, $worktime1] = $this->createUserWithWorktime('user1');
        [$user2, $worktime2] = $this->createUserWithWorktime('user2');

        WorktimeRequest::factory()->create([
            'worktime_id' => $worktime1->id,
            'reason' => '修正します',
            'approval_status' => 1,
        ]);

        WorktimeRequest::factory()->create([
            'worktime_id' => $worktime2->id,
            'reason' => '修正します',
            'approval_status' => 1,
        ]);

        $response = $this->get('/stamp_correction_request/list?tab=approved');
        $response->assertStatus(200)
            ->assertSee('承認済み')
            ->assertSee('user1')
            ->assertSee('user2');
    }

    //!勤怠情報修正--詳細表示
    public function test_admin_can_view_correction_request_detail()
    {
        $this->loginAsAdmin();

        [$user, $worktime] = $this->createUserWithWorktime('user1');

        $request = $this->createCorrectionRequest($worktime);
        $this->createCorrectionBreak($request);

        $response = $this->get(route(
            'admin.stamp_correction_request.approve',
            ['attendance_correct_request_id' => $request->id]
        ));

        $response->assertStatus(200)
            ->assertSee('user1')
            ->assertSee('2026年')
            ->assertSee('01月01日')
            ->assertSee('10:00')
            ->assertSee('19:00')
            ->assertSee('13:00')
            ->assertSee('14:00');
    }

    //!勤怠情報修正--承認処理
    public function test_admin_can_approve_correction_request_and_update_worktime()
    {
        $this->loginAsAdmin();

        [$user, $worktime] = $this->createUserWithWorktime();

        $request = $this->createCorrectionRequest($worktime);
        $this->createCorrectionBreak($request);

        $response = $this->post(
            route('admin.stamp_correction_request.update', [
                'attendance_correct_request_id' => $request->id
            ]),
            [
                'break_start' => ['13:00'],
                'break_end'   => ['14:00'],
            ]
        );

        $response->assertStatus(302);

        $this->assertDatabaseHas('worktimes', [
            'id' => $worktime->id,
            'start_time' => '2026-01-01 10:00:00',
            'end_time'   => '2026-01-01 19:00:00',
        ]);

        $this->assertDatabaseHas('breaks', [
            'worktime_id' => $worktime->id,
            'break_start' => '2026-01-01 13:00:00',
            'break_end'   => '2026-01-01 14:00:00',
        ]);


        $this->assertDatabaseHas('worktime_requests', [
            'id' => $request->id,
            'approval_status' => 1,
        ]);
    }
}
