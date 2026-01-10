<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdminAttendanceDetailTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    //!勤怠詳細--選択情報と一致
    public function test_admin_attendance_detail_shows_correct_information()
    {
      // 管理者ログイン
      $admin = \App\Models\Admin::factory()->create();
      $this->actingAs($admin, 'admin');

      // 一般ユーザー作成
      $user = \App\Models\User::factory()->create([
        'name' => 'testman',
        ]);

      // 勤怠データ作成
      $date = '2026-01-01';
      $worktime = \App\Models\Worktime::factory()->create([
        'user_id'    => $user->id,
        'date'       => $date,
        'start_time' => "$date 09:00:00",
        'end_time'   => "$date 18:00:00",
        ]);

      // 勤怠詳細ページへアクセス
      $response = $this->get("/admin/attendance/{$worktime->id}");
      $response->assertStatus(200);

      // 表示内容の確認
      $response->assertSee('testman');
      $response->assertSee('2026年');
      $response->assertSee('01月01日');
      $response->assertSee('09:00');
      $response->assertSee('18:00');
    }

    //!勤怠詳細--バリデーション：出勤時間＞退勤時間
    public function test_admin_validate_start()
    {
      // 管理者ログイン
      $admin = \App\Models\Admin::factory()->create();
      $this->actingAs($admin, 'admin');

      // 一般ユーザー作成
      $user = \App\Models\User::factory()->create();

      // 勤怠データ作成
      $date = '2026-01-01';
      $worktime = \App\Models\Worktime::factory()->create([
        'user_id'    => $user->id,
        'date'       => $date,
        'start_time' => "$date 09:00:00",
        'end_time'   => "$date 18:00:00",
        ]);

      // 出勤時間＞退勤時間
      $response = $this->post("/admin/attendance/update/{$worktime->id}", [
        'start_time' => '19:00',
        'end_time'   => '18:00',
        'break_start' => ['10:00'],
        'break_end'   => ['10:30'],
        ]);

      // バリデーションエラーを確認
      $response->assertSessionHasErrors([
        'start_time' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    //!勤怠詳細--バリデーション：休憩開始時間＞休憩終了時間
    public function test_admin_validate_break_start_after_end()
    {
      // 管理者ログイン
      $admin = \App\Models\Admin::factory()->create();
      $this->actingAs($admin, 'admin');

      // 一般ユーザー作成
      $user = \App\Models\User::factory()->create();

      // 勤怠データ作成
      $date = '2026-01-01';
      $worktime = \App\Models\Worktime::factory()->create([
        'user_id'    => $user->id,
        'date'       => $date,
        'start_time' => "$date 09:00:00",
        'end_time'   => "$date 18:00:00",
        ]);

      // 休憩開始が退勤時間より後（例：19:00）
      $response = $this->post("/admin/attendance/update/{$worktime->id}", [
        'start_time'  => '09:00',
        'end_time'    => '18:00',
        'break_start' => ['19:00'],  // ★ 退勤より後
        'break_end'   => ['19:30'],
        'remarks'     => 'test',
        ]);

      // バリデーションエラーを確認
      $response->assertSessionHasErrors([
        'break_start.0' => '休憩時間が不適切な値です',
      ]);
    }

    //!勤怠詳細--バリデーション：休憩終了時間＞退勤時間
    public function test_admin_validate_break_end_after_end_time()
{
    // 管理者ログイン
    $admin = \App\Models\Admin::factory()->create();
    $this->actingAs($admin, 'admin');

    // 一般ユーザー作成
    $user = \App\Models\User::factory()->create();

    // 勤怠データ作成
    $date = '2026-01-01';
    $worktime = \App\Models\Worktime::factory()->create([
        'user_id'    => $user->id,
        'date'       => $date,
        'start_time' => "$date 09:00:00",
        'end_time'   => "$date 18:00:00",
    ]);

    // 休憩終了が退勤時間より後（例：19:00）
    $response = $this->post("/admin/attendance/update/{$worktime->id}", [
        'start_time'  => '09:00',
        'end_time'    => '18:00',
        'break_start' => ['17:00'],
        'break_end'   => ['19:00'],  // ★ 退勤より後
        'remarks'     => 'test',
    ]);

    // バリデーションエラーを確認
    $response->assertSessionHasErrors([
        'break_end.0' => '休憩時間もしくは退勤時間が不適切な値です',
    ]);
}

    //!勤怠詳細--バリデーション：備考必須
public function test_admin_validate_remarks_required()
{
    // 管理者ログイン
    $admin = \App\Models\Admin::factory()->create();
    $this->actingAs($admin, 'admin');

    // 一般ユーザー作成
    $user = \App\Models\User::factory()->create();

    // 勤怠データ作成
    $date = '2026-01-01';
    $worktime = \App\Models\Worktime::factory()->create([
        'user_id'    => $user->id,
        'date'       => $date,
        'start_time' => "$date 09:00:00",
        'end_time'   => "$date 18:00:00",
    ]);

    // 備考未入力で保存
    $response = $this->post("/admin/attendance/update/{$worktime->id}", [
        'start_time'  => '09:00',
        'end_time'    => '18:00',
        'break_start' => ['10:00'],
        'break_end'   => ['10:30'],
        'remarks'     => '',   // ★ 未入力
    ]);

    // バリデーションエラーを確認
    $response->assertSessionHasErrors([
        'remarks' => '備考を記入してください',
    ]);
}


  }
