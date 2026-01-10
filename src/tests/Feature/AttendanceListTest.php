<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Worktime;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    /* ------------------------------
        共通メソッド
    ------------------------------ */

    // ユーザー作成＋ログイン
    private function createUserAndLogin()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        return $user;
    }

    // 勤怠レコード作成
    private function createWorktime($user, $date, $start = '09:00', $end = '18:00')
    {
        return Worktime::create([
            'user_id' => $user->id,
            'date' => $date,
            'start_time' => Carbon::parse("$date $start"),
            'end_time' => Carbon::parse("$date $end"),
            'status' => 3,
        ]);
    }

    // 勤怠一覧ページアクセス
    private function accessList($yearMonth = null)
    {
        $url = '/attendance/list' . ($yearMonth ? "?year_month=$yearMonth" : '');
        return $this->get($url);
    }

    // 月パラメータ（URL用）
    private function monthParam($offset)
    {
        return Carbon::now()->addMonths($offset)->format('Y-m');
    }

    // 月表示（画面表示用）
    private function monthDisplay($offset)
    {
        return Carbon::now()->addMonths($offset)->format('Y/m');
    }


    /* ------------------------------
        テスト本体
    ------------------------------ */

    //! 勤怠一覧--表示確認
    public function test_attendance_list_display()
    {
        $user = $this->createUserAndLogin();

        $dates = ['2026-01-01', '2026-01-02', '2026-01-03'];

        foreach ($dates as $date) {
            $this->createWorktime($user, $date);
        }

        $response = $this->accessList();
        $response->assertStatus(200)->assertSee('勤怠一覧');

        foreach ($dates as $date) {
            $response->assertSeeInOrder([
                Carbon::parse($date)->format('m/d'),
                '09:00',
                '18:00',
            ]);
        }
    }

    //! 勤怠一覧--現在の月
    public function test_attendance_list_current_month()
    {
        Carbon::setTestNow('2026-01-03');
        $this->createUserAndLogin();

        $response = $this->accessList();
        $response->assertSee($this->monthDisplay(0));
    }

    //! 勤怠一覧--前月
    public function test_attendance_list_previous_month()
    {
        Carbon::setTestNow('2026-01-03');
        $this->createUserAndLogin();

        $response = $this->accessList($this->monthParam(-1));
        $response->assertSee($this->monthDisplay(-1));
    }

    //! 勤怠一覧--翌月
    public function test_attendance_list_next_month()
    {
        Carbon::setTestNow('2026-01-03');
        $this->createUserAndLogin();

        $response = $this->accessList($this->monthParam(1));
        $response->assertSee($this->monthDisplay(1));
    }

    //! 勤怠一覧--詳細表示確認
    public function test_attendance_list_detail_display()
    {
        $user = $this->createUserAndLogin();

        $date = '2026-01-01';
        $worktime = $this->createWorktime($user, $date);

        $response = $this->get("/attendance/detail/{$worktime->id}");
        $response->assertStatus(200);
        $response->assertSee('2026年');
        $response->assertSee('01月01日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }
}
