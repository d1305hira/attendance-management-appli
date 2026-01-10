<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Admin;

class AdminAttendanceListTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    //! 勤怠一覧（管理者）
    public function test_staff_attendance_list()
    {
      // 管理者ログイン
      $admin = \App\Models\Admin::factory()->create();
      $this->actingAs($admin, 'admin');

      // 2. 一般ユーザーを3人作成
      $users = \App\Models\User::factory()->count(3)->create();

      // 今日の日付
      $today = '2026-01-01';

      // 各ユーザーに勤怠データを作成
      foreach ($users as $user) {
        \App\Models\Worktime::factory()->create([
            'user_id'    => $user->id,
            'date'       => $today,
            'start_time' => "$today 09:00:00",
            'end_time'   => "$today 18:00:00",
        ]);
        }

      // 管理者勤怠一覧画面へアクセス
      $response = $this->get('/admin/attendance/list?year_month_day=2026-01-01');
      $response->assertStatus(200);

      // 3人分の勤怠情報が画面に表示されていることを確認
      foreach ($users as $user) {
        $response->assertSee($user->name);
        $response->assertSee('09:00');
        $response->assertSee('18:00');
      }
    }

    //! 勤怠一覧（管理者）表示の確認
    public function test_admin_attendance_list_shows_selected_date()
    {
      // 管理者ログイン
      $admin = \App\Models\Admin::factory()->create();
      $this->actingAs($admin, 'admin');

      // 固定日付
      $date = '2026-01-01';

      // 勤怠一覧画面へアクセス
      $response = $this->get("/admin/attendance/list?year_month_day={$date}");

      // ステータス確認
      $response->assertStatus(200);

      // 日付が画面に表示されていることを確認
      $response->assertSee('2026/01/01');
    }

    //! 勤怠一覧（管理者）表示の確認（前日ボタン）
    public function test_admin_attendance_list_prev_day_navigation()
    {
      // 管理者ログイン
      $admin = \App\Models\Admin::factory()->create();
      $this->actingAs($admin, 'admin');

      // 一般ユーザー作成
      $user = \App\Models\User::factory()->create();

      // 今日と前日
      $today = '2026-01-02';
      $prevDay = '2026-01-01';

      // 前日の勤怠データを作成
      \App\Models\Worktime::factory()->create([
        'user_id'    => $user->id,
          'date'       => $prevDay,
          'start_time' => "$prevDay 09:00:00",
          'end_time'   => "$prevDay 18:00:00",
      ]);

      // まず今日の日付でアクセス
      $response = $this->get("/admin/attendance/list?year_month_day={$today}");
      $response->assertStatus(200);

      // 実際に前日へアクセス
      $responsePrev = $this->get("/admin/attendance/list?year_month_day={$prevDay}");
      $responsePrev->assertStatus(200);

      // 前日の勤怠情報が表示されていることを確認
      $responsePrev->assertSee('2026/01/01');
      $responsePrev->assertSee($user->name);
      $responsePrev->assertSee('09:00');
      $responsePrev->assertSee('18:00');
    }

    //! 勤怠一覧（管理者）表示の確認（翌日ボタン）
    public function test_admin_attendance_list_next_day_navigation()
    {
      // 管理者ログイン
      $admin = \App\Models\Admin::factory()->create();
      $this->actingAs($admin, 'admin');

      // 一般ユーザー作成
      $user = \App\Models\User::factory()->create();

      // 今日と翌日
      $today = '2026-01-01';
      $nextDay = '2026-01-02';

      // 翌日の勤怠データを作成
      \App\Models\Worktime::factory()->create([
        'user_id'    => $user->id,
          'date'       => $nextDay,
          'start_time' => "$nextDay 09:00:00",
          'end_time'   => "$nextDay 18:00:00",
      ]);

      // まず今日の日付でアクセス
      $response = $this->get("/admin/attendance/list?year_month_day={$today}");
      $response->assertStatus(200);

      // 実際に翌日へアクセス
      $responseNext = $this->get("/admin/attendance/list?year_month_day={$nextDay}");
      $responseNext->assertStatus(200);

      // 翌日の勤怠情報が表示されていることを確認
      $responseNext->assertSee('2026/01/02');
      $responseNext->assertSee($user->name);
      $responseNext->assertSee('09:00');
      $responseNext->assertSee('18:00');
    }
}