<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use App\Models\User;
use App\Models\Worktime;
use App\Models\WorktimeRequest;
use Carbon\Carbon;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    /* ------------------------------
        共通メソッド
    ------------------------------ */

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

    // デフォルト勤怠データ作成
    private function createDefaultWorktime($user)
    {
        return $this->createWorktimeWithBreaks(
            $user,
            '2026-01-01',
            '09:00',
            '18:00',
            [
                ['start' => '12:00', 'end' => '13:00'],
            ]
        );
    }

    // 勤怠詳細ページを開く
    private function openAttendanceDetailPage($worktime)
    {
        return $this->get("/attendance/detail/{$worktime->id}")
                    ->assertStatus(200);
    }


    /* ------------------------------
        テスト本体
    ------------------------------ */

    //! 勤怠詳細情報修正--バリデーション：出勤・退勤時間
    public function test_attendance_correction_validate_attendancetime()
    {
        $user = $this->createUserAndLogin();
        $worktime = $this->createDefaultWorktime($user);

        $this->openAttendanceDetailPage($worktime);

        $response = $this->post("/attendance/detail/{$worktime->id}", [
            'start_time' => '19:00', // 出勤 > 退勤
            'end_time'   => '18:00',
            'remarks'    => '修正します',
        ]);

        $response->assertSessionHasErrors(['end_time']);
    }

    //! 勤怠詳細情報修正--バリデーション：休憩開始 > 退勤時間
    public function test_attendance_correction_validate_break_start()
    {
        $user = $this->createUserAndLogin();
        $worktime = $this->createDefaultWorktime($user);

        $this->openAttendanceDetailPage($worktime);

        $response = $this->post("/attendance/detail/{$worktime->id}", [
            'start_time'  => '09:00',
            'end_time'    => '18:00',
            'remarks'     => '修正します',
            'break_start' => ['19:30'],
            'break_end'   => ['20:00'],
        ]);

        $response->assertSessionHasErrors(['break_start.0']);
    }

    //! 勤怠詳細情報修正--バリデーション：休憩終了 > 退勤時間
    public function test_attendance_correction_validate_break_end()
    {
        $user = $this->createUserAndLogin();
        $worktime = $this->createDefaultWorktime($user);

        $this->openAttendanceDetailPage($worktime);

        $response = $this->post("/attendance/detail/{$worktime->id}", [
            'start_time'  => '09:00',
            'end_time'    => '18:00',
            'remarks'     => '修正します',
            'break_start' => ['17:30'],
            'break_end'   => ['19:00'],
        ]);

        $response->assertSessionHasErrors(['break_end.0']);
    }

    //! 勤怠詳細情報修正--バリデーション：備考未記入
    public function test_attendance_correction_validate_remarks_required()
    {
        $user = $this->createUserAndLogin();
        $worktime = $this->createDefaultWorktime($user);

        $this->openAttendanceDetailPage($worktime);

        $response = $this->post("/attendance/detail/{$worktime->id}", [
            'start_time'  => '09:00',
            'end_time'    => '18:00',
            'remarks'     => '',
            'break_start' => ['12:00'],
            'break_end'   => ['13:00'],
        ]);

        $response->assertSessionHasErrors(['remarks']);
    }

    //! 勤怠詳細情報修正--正常登録
    public function test_attendance_correction_successful_submission()
    {
      // 一般ユーザーで勤怠修正申請
      // 一般ユーザーで申請
      $user = $this->createUserAndLogin();
      $worktime = $this->createDefaultWorktime($user);

      $this->openAttendanceDetailPage($worktime);

      // 正常登録
      $response = $this->post("/attendance/detail/{$worktime->id}", [
        'start_time'  => '10:00',
        'end_time'    => '19:00',
        'remarks'     => '修正します',
        'break_start' => ['13:00'],
        'break_end'   => ['14:00'],
        ]);

      $response->assertSessionHasNoErrors();

      // WorktimeRequest を取得
      $worktimeRequest = WorktimeRequest::first();

      // ログアウト
      Auth::logout();

      // 管理者作成
      $admin = \App\Models\Admin::create([
          'name' => 'adminman',
          'email' => 'admin' . uniqid() . '@example.com',
          'password' => bcrypt('password'),
      ]);
      // 管理者ログイン
      $this->actingAs($admin, 'admin');

      // 管理者用一覧画面の確認
      $this->get('/stamp_correction_request/list')
        ->assertStatus(200)
        ->assertSee($user->name)
        ->assertSee('2026/01/01')
        ->assertSee('修正します')
        ->assertSee("/stamp_correction_request/approve/{$worktimeRequest->id}");

      // 詳細ボタンから承認画面へ遷移
      $this->get("/stamp_correction_request/approve/{$worktimeRequest->id}")
        ->assertStatus(200)
        ->assertSee('修正します')
        ->assertSee('10:00')
        ->assertSee('19:00')
        ->assertSee('13:00')
        ->assertSee('14:00');
    }

    //! 勤怠詳細情報修正--「承認待ち」タブに自分の申請が表示
    public function test_attendance_correction_pendingtab()
    {
      $user = $this->createUserAndLogin();
      $worktime = $this->createDefaultWorktime($user);

      $this->openAttendanceDetailPage($worktime);

      $response = $this->post("/attendance/detail/{$worktime->id}", [
        'start_time'  => '09:00',
        'end_time'    => '18:00',
        'remarks'     => '修正します',
        'break_start' => ['13:00'],
        'break_end'   => ['14:00'],
      ]);

      $response->assertSessionHasNoErrors();

      $request = \App\Models\WorktimeRequest::first();

      // 「承認待ち」タブを開く
      $this->get(route('stamp_correction_request_list', ['tab' => 'pending']))
        ->assertStatus(200)
        ->assertSee($user->name)
        ->assertSee($worktime->date->format('Y/m/d'))
        ->assertSee($request->reason)
        ->assertSee($request->created_at->format('Y/m/d'))
        ->assertSee($request->approval_status_label);
    }

    //! 勤怠詳細情報修正--「承認済み」タブに自分の申請が表示
    public function test_attendance_correction_approvedtab()
    {
      $user = $this->createUserAndLogin();
      $worktime = $this->createDefaultWorktime($user);

      $this->openAttendanceDetailPage($worktime);

      $response = $this->post("/attendance/detail/{$worktime->id}", [
        'start_time'  => '09:00',
        'end_time'    => '18:00',
        'remarks'     => '修正します',
        'break_start' => ['13:00'],
        'break_end'   => ['14:00'],
      ]);

      $response->assertSessionHasNoErrors();

      $request = \App\Models\WorktimeRequest::first();
      $request->approval_status = 1; // 承認済み
      $request->save();

      // 「承認済み」タブを開く
      $this->get(route('stamp_correction_request_list', ['tab' => 'approved']))
        ->assertStatus(200)
        ->assertSee($user->name)
        ->assertSee($worktime->date->format('Y/m/d'))
        ->assertSee($request->reason)
        ->assertSee($request->created_at->format('Y/m/d'))
        ->assertSee($request->approval_status_label);
    }

    //! 勤怠詳細情報修正--詳細画面へ遷移
    public function test_attendance_correction_to_detailpage()
    {
      $user = $this->createUserAndLogin();
      $worktime = $this->createDefaultWorktime($user);

      $this->openAttendanceDetailPage($worktime);

      $response = $this->post("/attendance/detail/{$worktime->id}", [
        'start_time'  => '09:00',
        'end_time'    => '18:00',
        'remarks'     => '修正します',
        'break_start' => ['13:00'],
        'break_end'   => ['14:00'],
      ]);

      $response->assertSessionHasNoErrors();

      $request = \App\Models\WorktimeRequest::first();

      // 詳細画面へ遷移
      $this->get("/attendance/detail/{id}")
        ->assertStatus(200)
        ->assertViewIs('attendance_detail');
    }
}