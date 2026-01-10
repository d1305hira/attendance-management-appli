<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminStaffListTest extends TestCase
{
    use RefreshDatabase;

    //管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
    public function test_admin_staff_list()
    {
        // 管理者ログイン
      $admin = \App\Models\Admin::factory()->create();
      $this->actingAs($admin, 'admin');

      // 2. 一般ユーザーを3人作成
      $users = \App\Models\User::factory()->count(3)->create();

      // 今日の日付
      $today = '2026-01-01';

      // 管理者スタッフ一覧画面へアクセス
      $response = $this->get('/admin/staff/list');
      $response->assertStatus(200);

      // 3人分の氏名とメールアドレスが画面に表示されていることを確認
      foreach ($users as $user) {
        $response->assertSee($user->name);
        $response->assertSee($user->email);
        }
    }

    //!ユーザーの勤怠情報確認
    public function test_admin_can_view_staff_monthly_attendance_list()
    {
      // 管理者ログイン
      $admin = \App\Models\Admin::factory()->create();
      $this->actingAs($admin, 'admin');

      // 一般ユーザー作成
      $user = \App\Models\User::factory()->create([
        'name' => 'testman',
      ]);

      // 勤怠データ（1件だけ）作成
      $date = '2026-01-03';
      \App\Models\Worktime::factory()->create([
        'user_id'    => $user->id,
        'date'       => $date,
        'start_time' => "$date 21:39:00",
        'end_time'   => "$date 22:00:00",
        ]);

      //! スタッフ別勤怠一覧画面へアクセス
      $response = $this->get("/admin/attendance/staff/{$user->id}?year_month=2026-01");
      $response->assertStatus(200);

      //! 表示確認（氏名・日付・出退勤・合計時間・詳細リンク）
      $response->assertSee('testman');
      $response->assertSee('2026/01/03');
      $response->assertSee('21:39');
      $response->assertSee('22:00');
      $response->assertSee('00:21');
      $response->assertSee('詳細');
    }

    //!勤怠一覧（前月）
    public function test_admin_can_view_previous_month_attendance_list()
  {
      // 管理者ログイン
      $admin = \App\Models\Admin::factory()->create();
      $this->actingAs($admin, 'admin');

      // 一般ユーザー作成
      $user = \App\Models\User::factory()->create([
        'name' => 'testman',
        ]);

      // 勤怠データ（2026年1月分）作成
      $date = '2026-01-03';
      \App\Models\Worktime::factory()->create([
        'user_id'    => $user->id,
        'date'       => $date,
        'start_time' => "$date 21:39:00",
        'end_time'   => "$date 22:00:00",
        ]);

      // 当月（2026-01）
      $response = $this->get("/admin/attendance/staff/{$user->id}?year_month=2026-01");
      $response->assertStatus(200);
      $response->assertSee('2026/01'); // ← 当月表示確認

      // 前月（2025-12）
      $prevResponse = $this->get("/admin/attendance/staff/{$user->id}?year_month=2025-12");
      $prevResponse->assertStatus(200);
      $prevResponse->assertSee('2025/12'); // ← 前月表示確認（重要）
  }


    //!勤怠一覧（翌月）
    public function test_admin_can_view_next_month_attendance_list()
    {
      $admin = \App\Models\Admin::factory()->create();
      $this->actingAs($admin, 'admin');

      $user = \App\Models\User::factory()->create([
        'name' => 'testman',
        ]);

      // 2026年1月の勤怠データ
      $date = '2026-01-03';
      \App\Models\Worktime::factory()->create([
        'user_id'    => $user->id,
        'date'       => $date,
        'start_time' => "$date 21:39:00",
        'end_time'   => "$date 22:00:00",
      ]);

      // 当月（2026-01）
      $response = $this->get("/admin/attendance/staff/{$user->id}?year_month=2026-01");
      $response->assertStatus(200);
      $response->assertSee('2026/01'); // ← 当月表示

      // 翌月（2026-02）
      $nextResponse = $this->get("/admin/attendance/staff/{$user->id}?year_month=2026-02");
      $nextResponse->assertStatus(200);
      $nextResponse->assertSee('2026/02'); // ← 翌月表示
    }

    //!勤怠一覧--詳細
    public function test_admin_can_view_staff_attendance_detail()
    {
      // 1. 管理者ログイン
      $admin = \App\Models\Admin::factory()->create();
      $this->actingAs($admin, 'admin');

      // 一般ユーザー作成
      $user = \App\Models\User::factory()->create([
        'name' => 'testman',
        ]);

      // 勤怠データ（1件だけ）作成
      $date = '2026-01-03';
      $worktime = \App\Models\Worktime::factory()->create([
        'user_id'    => $user->id,
        'date'       => $date,
        'start_time' => "$date 21:39:00",
        'end_time'   => "$date 22:00:00",
      ]);

      // 2. 勤怠一覧ページを開く
      $listResponse = $this->get("/admin/attendance/staff/{$user->id}?year_month=2026-01");
      $listResponse->assertStatus(200);

      // 一覧画面に「詳細」ボタン（リンク）が表示されていること
      $listResponse->assertSee("/admin/attendance/{$worktime->id}");
      $listResponse->assertSee('詳細');

      // 3. 「詳細」ボタンを押す（＝リンク先に GET する）
      $detailResponse = $this->get("/admin/attendance/{$worktime->id}");
      $detailResponse->assertStatus(200);

      // → 詳細画面に遷移していることを確認
      $detailResponse->assertSee('testman');
      $detailResponse->assertSee('2026年');
      $detailResponse->assertSee('01月03日');
      $detailResponse->assertSee('21:39');
      $detailResponse->assertSee('22:00');
    }
}
