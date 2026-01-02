<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Worktime;
use Carbon\Carbon;

class AttendanceTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */

    //!打刻画面に現在の日時が表示されていることを確認
    public function test_attendance_screen_has_datetime_elements()
    {
    $user = \App\Models\User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/attendance');

    // Blade に日時表示用の要素が存在することを確認
    $response->assertSee('id="date"', false);
    $response->assertSee('id="time"', false);
    }

    //!勤務ステータス--勤務外
    public function test_status_display_for_no_worktime_record()
    {
        // ユーザー作成
        $user = User::factory()->create();

        // ログイン状態にする
        $this->actingAs($user);

        // 打刻画面にアクセス
        $response = $this->get('/attendance');

        // ステータス表示が「勤務外」であることを確認
        $response->assertSee('勤務外');
    }

    //!勤務ステータス--出勤中
    public function test_status_display_for_working()
    {
        $user = User::factory()->create();

        // 今日の勤務レコードを「出勤中」で作成
        Worktime::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
            'status' => 1,
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance');

        // ステータス表示が「出勤中」であることを確認
        $response->assertSee('出勤中');
    }

    //!勤務ステータス--休憩中
    public function test_status_display_for_breaking()
    {
        $user = User::factory()->create();

        // 今日の勤務レコードを「休憩中」で作成
        Worktime::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now(),
            'status' => 2,
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance');

        // ステータス表示が「休憩中」であることを確認
        $response->assertSee('休憩中');
    }

    //!勤務ステータス--退勤済
    public function test_status_display_for_finished()
    {
        $user = User::factory()->create();

        // 今日の勤務レコードを「退勤済」で作成
        Worktime::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'start_time' => now()->subHours(8),
            'end_time' => now(),
            'status' => 3,
        ]);

        $this->actingAs($user);

        $response = $this->get('/attendance');

        // ステータス表示が「退勤済」であることを確認
        $response->assertSee('退勤済');
    }


    //!出勤機能--出勤ボタンの動作確認
    public function test_status_changes_to_working_after_clock_in()
    {
      $user = User::factory()->create();
      $this->actingAs($user);

      // 出勤前の画面 → 「出勤」ボタンが表示されていること
      $response = $this->get('/attendance');
      $response->assertSee('出勤');

      // 出勤処理を実行
      $this->post('/attendance', [
        'date' => now()->toDateString(),
      ]);

    // 出勤後の画面 → 「勤務中」が表示されていることを確認
      $responseAfter = $this->get('/attendance');
      $responseAfter->assertSee('出勤中');
    }


    //!出勤機能--一日１回のみ出勤可能
    public function test_start_button_hidden_and_cannot_reclock_for_finished_user()
    {
      $user = User::factory()->create();

      // 退勤済の勤怠レコードを作成
      Worktime::create([
        'user_id' => $user->id,
        'date' => now()->toDateString(),
        'start_time' => now()->subHours(8),
        'end_time' => now(),
        'status' => 3,
      ]);
      $this->actingAs($user);

      // 打刻画面にアクセス
      $response = $this->get('/attendance');

      // 出勤ボタンが表示されていないことを確認
      $response->assertDontSee('出勤');

      // 出勤処理を試みる
      $postResponse = $this->post('/attendance', [
        'date' => now()->toDateString(),
      ]);

      // 「お疲れ様でした」が表示されること
      $response->assertSee('お疲れ様でした');

      // 「出勤」ボタンが表示されないこと
      $response->assertDontSee('出勤');
    }

    //!出勤機能--勤怠一覧画面で確認
    public function test_clock_in_and_check_attendance_list_for_today()
    {
      $user = User::factory()->create();
      $this->actingAs($user);

      // 出勤前の画面に「出勤」ボタンが表示されていることを確認
      $response = $this->get('/attendance');
      $response->assertSee('出勤');

      // 出勤処理を実行
      $this->post('/attendance', [
        'date' => now()->toDateString(),
      ]);

      //DBから出勤時刻を取得
      $worktime = Worktime::where('user_id', $user->id)
                          ->where('date', now()->toDateString())
                          ->first();
      $startTime = \Carbon\Carbon::parse($worktime->start_time)
                          ->format('H:i');

      // 勤怠一覧画面にアクセス
      $listResponse = $this->get('/attendance/list');

      // 勤怠一覧に出勤時刻（H:i）が表示されていることを確認
      $listResponse->assertSee($startTime);
    }

    //!休憩機能--休憩入
    public function test_break_start()
    {
      $user = User::factory()->create();
      $this->actingAs($user);

      // 出勤中の勤怠レコードを作成（status: 1）
      $worktime = Worktime::create([
        'user_id' => $user->id,
        'date' => now()->toDateString(),
        'start_time' => now()->subHours(2),
        'status' => 1,
        ]);

      //「休憩入」ボタンの表示を確認
      $response = $this->get('/attendance');
      $response->assertSee('休憩入');

      // 休憩開始処理
      $breakStart = now()->subHour()->setSeconds(0);
      $this->post('/attendance/break', [
        'date' => $breakStart->toDateString(),
      ]);

      //休憩中画面に「休憩戻」ボタンが表示されていることを確認
      $responseAfterBreak = $this->get('/attendance');
      $responseAfterBreak->assertSee('休憩中');
    }

    //!休憩機能--休憩複数回可能
    public function test_break_manytimes()
    {
      $user = User::factory()->create();
      $this->actingAs($user);

      // 出勤中の勤怠レコードを作成（status: 1）
      Worktime::create([
        'user_id' => $user->id,
        'date' => now()->toDateString(),
        'start_time' => now()->subHours(3),
        'status' => 1,
        ]);

      // 1回目の休憩入
      $breakStart1 = now()->subHour()->setSeconds(0);
      $this->post('/attendance/break', [
          'date' => $breakStart1->toDateString(),
        ]);

      // 1回目の休憩戻
      $breakEnd1 = now()->setSeconds(0);
      $this->post('/attendance/break/end', [
          'date' => $breakEnd1->toDateString(),
      ]);

      // 休憩戻後に「休憩入」が表示される（＝再度休憩できる）
      $responseAfterBreakEnd = $this->get('/attendance');
      $responseAfterBreakEnd->assertSee('休憩入');

      // 2回目の休憩入（複数回できることの証明）
      $breakStart2 = now()->addMinutes(1)->setSeconds(0);
      $this->post('/attendance/break', [
          'date' => $breakStart2->toDateString(),
      ]);

      // 2回目の休憩中表示を確認
      $responseAfterBreak2 = $this->get('/attendance');
      $responseAfterBreak2->assertSee('休憩中');
    }

    //!休憩機能--休憩入→休憩戻
    public function test_break_start_and_end()
    {
      $user = User::factory()->create();
      $this->actingAs($user);

      // 出勤中の勤怠レコードを作成（status: 1）
      Worktime::create([
        'user_id' => $user->id,
        'date' => now()->toDateString(),
        'start_time' => now()->subHours(2),
        'status' => 1,
        ]);

      // 休憩開始処理
      $breakStart = now()->subHour()->setSeconds(0);
      $this->post('/attendance/break', [
        'date' => $breakStart->toDateString(),
      ]);

      // 休憩中画面に「休憩戻」ボタンが表示されていることを確認
      $responseAfterBreak = $this->get('/attendance');
      $responseAfterBreak->assertSee('休憩戻');

      // 休憩終了処理
      $breakEnd = now()->setSeconds(0);
      $this->post('/attendance/break/end', [
        'date' => $breakEnd->toDateString(),
      ]);

      // 休憩終了後に「出勤中」が表示されていることを確認
      $responseAfterBreakEnd = $this->get('/attendance');
      $responseAfterBreakEnd->assertSee('出勤中');
    }

    //!休憩機能--休憩戻複数回可能
    public function test_break_end_manytimes()
    {
      $user = User::factory()->create();
      $this->actingAs($user);

      // 出勤中の勤怠レコードを作成（status: 1）
      Worktime::create([
        'user_id' => $user->id,
        'date' => now()->toDateString(),
        'start_time' => now()->subHours(4),
        'status' => 1,
        ]);

      // 1回目の休憩入
      $breakStart1 = now()->subHours(2)->setSeconds(0);
      $this->post('/attendance/break', [
          'date' => $breakStart1->toDateString(),
        ]);

      // 1回目の休憩戻
      $breakEnd1 = now()->subHour()->setSeconds(0);
      $this->post('/attendance/break/end', [
          'date' => $breakEnd1->toDateString(),
      ]);

      // 2回目の休憩入
      $breakStart2 = now()->subMinutes(30)->setSeconds(0);
      $this->post('/attendance/break', [
          'date' => $breakStart2->toDateString(),
      ]);

      // 2回目の「休憩戻」が表示されることを確認
      $responseAfterBreakEnd1 = $this->get('/attendance');
      $responseAfterBreakEnd1->assertSee('休憩戻');
    }

    //!休憩機能--勤怠一覧画面で確認
    public function test_break_time_recorded_in_attendance_list()
    {
      $user = User::factory()->create();
      $this->actingAs($user);

      $date = '2026-01-02';

      // 出勤時刻を固定
      Carbon::setTestNow(Carbon::parse("$date 09:00"));

      Worktime::create([
        'user_id' => $user->id,
        'date' => $date,
        'start_time' => Carbon::parse("$date 09:00"),
        'status' => 1,
      ]);

      // ★ 休憩開始（12:00）
      $breakStart = Carbon::parse("$date 12:00");
      Carbon::setTestNow($breakStart);
      $this->post('/attendance/break', [
        'date' => $date,
      ]);

      // ★ 休憩終了（12:30）
      $breakEnd = Carbon::parse("$date 12:30");
      Carbon::setTestNow($breakEnd);
      $this->post('/attendance/break/end', [
        'date' => $date,
      ]);

      // ★ 期待される休憩時間を計算
      $totalBreakMinutes = $breakEnd->diffInMinutes($breakStart);
      $expectedBreakTime = sprintf(
        '%02d:%02d',
        floor($totalBreakMinutes / 60),
        $totalBreakMinutes % 60
      );

      // 一覧画面へ
      $listResponse = $this->get('/attendance/list');

      // 休憩時間が表示されていることを確認
      $listResponse->assertSeeInOrder([
      '01/02',      // 日付
      $expectedBreakTime,  // 休憩時間
      ]);
    }

    //!退勤機能--退勤ボタンの動作確認
    public function test_clock_out()
    {
      $user = User::factory()->create();
      $this->actingAs($user);

      // 出勤中の勤怠レコードを作成
      Worktime::create([
        'user_id' => $user->id,
        'date' => now()->toDateString(),
        'start_time' => now()->subHours(8),
        'status' => 1,
      ]);

      // 退勤前の画面 → 「退勤」ボタンが表示されていることを確認
      $response = $this->get('/attendance');
      $response->assertSee('退勤');

      // 退勤処理を実行
      $this->post('/attendance/end', [
        'date' => now()->toDateString(),
      ]);

      // 退勤後の画面 → 「退勤済」が表示されていることを確認
      $responseAfter = $this->get('/attendance');
      $responseAfter->assertSee('退勤済');
    }

    //!退勤機能--勤怠一覧画面で確認
    public function test_clock_out_and_check_attendance_list()
    {
      $user = User::factory()->create();
      $this->actingAs($user);

      $date = '2026-01-02';

      // 出勤外の勤怠レコードを作成
      Worktime::create([
        'user_id' => $user->id,
        'date' => $date,
        'status' => 0,
      ]);

      // 出勤時刻を固定
      Carbon::setTestNow(Carbon::parse("$date 09:00"));

      //出勤処理を実行
      $this->post('/attendance', [
        'date' => $date,
      ]);

      // 退勤時刻を固定
      Carbon::setTestNow(Carbon::parse("$date 18:00"));

      // 退勤処理を実行
      $this->post('/attendance/end', [
        'date' => $date,
      ]);

      $this->assertDatabaseHas('worktimes', [
        'user_id' => $user->id,
        'date' => $date,
        'end_time' => '18:00',
      ]);
    }

}