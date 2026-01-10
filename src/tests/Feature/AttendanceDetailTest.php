<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Worktime;
use Carbon\Carbon;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    /* ------------------------------------
        共通メソッド
    ------------------------------------ */

    // ユーザー作成＋ログイン
    private function createUserAndLogin($name = 'testman')
    {
        $user = User::factory()->create(['name' => $name]);
        $this->actingAs($user);
        return $user;
    }

    // 勤怠レコード＋休憩レコードまとめて作成
    private function createWorktimeWithBreaks($user, $date, $start = '09:00', $end = '18:00', $breaks = [])
    {
        $worktime = Worktime::create([
            'user_id'    => $user->id,
            'date'       => $date,
            'start_time' => Carbon::parse("$date $start"),
            'end_time'   => Carbon::parse("$date $end"),
            'status'     => 3,
        ]);

        foreach ($breaks as $break) {
            $worktime->breaks()->create([
                'break_start' => Carbon::parse("$date {$break['start']}"),
                'break_end'   => Carbon::parse("$date {$break['end']}"),
            ]);
        }

        return $worktime;
    }

    // 詳細ページアクセス
    private function accessDetail($worktime)
    {
        return $this->get("/attendance/detail/{$worktime->id}");
    }


    /* ------------------------------------
        テスト本体
    ------------------------------------ */

    //! 勤怠詳細--名前がログインユーザー
    public function test_detail_check_user_name()
    {
        $user = $this->createUserAndLogin('testman');

        $worktime = $this->createWorktimeWithBreaks($user, '2026-01-01');

        $response = $this->accessDetail($worktime);
        $response->assertStatus(200);
        $response->assertSee('testman');
    }

    //! 勤怠詳細--日付表示
    public function test_detail_check_date()
    {
        $user = $this->createUserAndLogin();

        $worktime = $this->createWorktimeWithBreaks($user, '2026-01-03');

        $response = $this->accessDetail($worktime);
        $response->assertSee('2026年');
        $response->assertSee('01月03日');
    }

    //! 勤怠詳細--出勤・退勤時間表示
    public function test_detail_check_time()
    {
        $user = $this->createUserAndLogin();

        $worktime = $this->createWorktimeWithBreaks($user, '2026-01-03');

        $response = $this->accessDetail($worktime);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    //! 勤怠詳細--休憩時間表示
    public function test_detail_check_break_time()
  {
    $user = $this->createUserAndLogin();

    $worktime = $this->createWorktimeWithBreaks(
        $user,
        '2026-01-03',
        '09:00',
        '18:00',
        [
            ['start' => '12:00', 'end' => '13:00'],
        ]
    );

    $response = $this->accessDetail($worktime);

    // 休憩時間の表示を確認
    $response->assertSeeInOrder(['12:00', '13:00']);
  }
}