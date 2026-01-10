<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Worktime;
use App\Models\WorkBreak;
use App\Models\WorktimeRequest;
use App\Models\User;
use Carbon\Carbon;


class AdminController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.admin_login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('admin')->attempt($credentials)) {
            $request->session()->regenerate();
            return redirect()->route('admin.attendance_list');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function index()
    {
      $yearMonthDay = request()->input('year_month_day')
          ? Carbon::createFromFormat('Y-m-d', request()->input('year_month_day'))
          : now();
      $worktimes = Worktime::whereDate('date', $yearMonthDay->format('Y-m-d'))
      ->with(['user', 'breaks'])
      ->get();

        return view('admin.attendance_list', compact('worktimes', 'yearMonthDay'));
    }

    public function admin_attendance_detail($id = null)
{
    $dateInput = request()->input('date');
    $date = $dateInput ? Carbon::parse($dateInput) : null;

    if ($id !== null) {
        $worktime = Worktime::with(['user', 'breaks'])->findOrFail($id);
        $date = $date ?? Carbon::parse($worktime->date);

        return view('admin.attendance_detail', [
            'worktime' => $worktime,
            'user' => $worktime->user,
            'date' => $date,
        ]);
    }

    $user_id = request()->input('user_id');
    $user = User::findOrFail($user_id);

    if (!$date) {
        abort(400, 'date パラメータが必要です');
    }

    $existing = Worktime::with('breaks')
        ->where('user_id', $user_id)
        ->whereDate('date', $date)
        ->first();

    if ($existing) {
        return view('admin.attendance_detail', [
            'worktime' => $existing,
            'user' => $existing->user,
            'date' => $date,
        ]);
    }

    $worktime = new Worktime([
        'user_id' => $user_id,
        'date' => $date,
        'start_time' => null,
        'end_time' => null,
    ]);

    return view('admin.attendance_detail', [
        'worktime' => $worktime,
        'user' => $user,
        'date' => $date,
    ]);
}




    public function admin_attendance_update(Request $request, $id)
{
    $worktime = Worktime::findOrFail($id);
    $date = Carbon::parse($worktime->date);

    $startInput = $request->start_time;
    $endInput   = $request->end_time;

    // -------------------------
    // 1. 出勤・退勤のバリデーション
    // -------------------------

    // 片方だけ入力 → エラー
    if ($startInput && !$endInput) {
        return back()
            ->withErrors(['end_time' => '出勤時間もしくは退勤時間が不適切な値です'])
            ->withInput();
    }

    if (!$startInput && $endInput) {
        return back()
            ->withErrors(['start_time' => '出勤時間もしくは退勤時間が不適切な値です'])
            ->withInput();
    }

    // 両方ある場合のみ比較
    if ($startInput && $endInput) {
        $start = Carbon::parse($startInput)->setDate($date->year, $date->month, $date->day);
        $end   = Carbon::parse($endInput)->setDate($date->year, $date->month, $date->day);

        if ($start->gt($end)) {
            return back()
                ->withErrors(['start_time' => '出勤時間もしくは退勤時間が不適切な値です'])
                ->withInput();
        }
    }

    // -------------------------
    // 2. 休憩のバリデーション
    // -------------------------
    $breakStarts = $request->break_start ?? [];
    $breakEnds   = $request->break_end ?? [];

    if (!is_array($breakStarts)) $breakStarts = [$breakStarts];
    if (!is_array($breakEnds))   $breakEnds   = [$breakEnds];

    foreach ($breakStarts as $i => $startTime) {
        $endTime = $breakEnds[$i] ?? null;

        // 両方空 → スキップ
        if (!$startTime && !$endTime) continue;

        // 片方だけ入力 → エラー
        if ($startTime && !$endTime) {
            return back()
                ->withErrors(["break_end.$i" => '休憩時間が不適切な値です'])
                ->withInput();
        }

        if (!$startTime && $endTime) {
            return back()
                ->withErrors(["break_start.$i" => '休憩時間が不適切な値です'])
                ->withInput();
        }

        // 両方ある場合のみ比較
        $breakStart = Carbon::parse($startTime)->setDate($date->year, $date->month, $date->day);
        $breakEnd   = Carbon::parse($endTime)->setDate($date->year, $date->month, $date->day);

        // 出勤より前 → エラー
        if ($startInput && $breakStart->lt($start)) {
            return back()
                ->withErrors(["break_start.$i" => '休憩時間が不適切な値です'])
                ->withInput();
        }

        // 退勤より後 → エラー
        if ($endInput && $breakStart->gt($end)) {
            return back()
                ->withErrors(["break_start.$i" => '休憩時間が不適切な値です'])
                ->withInput();
        }

        // 休憩終了が退勤より後 → エラー
        if ($endInput && $breakEnd->gt($end)) {
            return back()
                ->withErrors(["break_end.$i" => '休憩時間もしくは退勤時間が不適切な値です'])
                ->withInput();
        }
    }

    // -------------------------
    // 3. 備考のバリデーション
    // -------------------------
    if (!$request->remarks) {
        return back()
            ->withErrors(['remarks' => '備考を記入してください'])
            ->withInput();
    }

    // -------------------------
    // 4. 更新処理
    // -------------------------
    $worktime->update([
    'start_time' => $startInput ? Carbon::parse($startInput)->setDate($date->year, $date->month, $date->day) : null,
    'end_time'   => $endInput ? Carbon::parse($endInput)->setDate($date->year, $date->month, $date->day) : null,
    'remarks'    => $request->remarks,
]);


    // 休憩更新
    $worktime->breaks()->delete();

    foreach ($breakStarts as $i => $startTime) {
        $endTime = $breakEnds[$i] ?? null;

        if ($startTime || $endTime) {
            WorkBreak::create([
                'worktime_id' => $worktime->id,
                'break_start' => $startTime ? Carbon::parse($startTime)->setDate($date->year, $date->month, $date->day) : null,
                'break_end'   => $endTime ? Carbon::parse($endTime)->setDate($date->year, $date->month, $date->day) : null,
            ]);
        }
    }

    WorktimeRequest::create([
        'worktime_id' => $worktime->id,
        'user_id' => $worktime->user_id,
    'requested_start_time' => $startInput ? Carbon::parse($startInput)->setDate($date->year, $date->month, $date->day) : null,
    'requested_end_time'   => $endInput ? Carbon::parse($endInput)->setDate($date->year, $date->month, $date->day) : null,
    'reason' => $request->remarks,
    'approval_status' => 1,
]);


    return redirect()
        ->route('admin.attendance_detail', ['id' => $id, 'date' => $worktime->date])
        ->with('success', '更新しました');
}

public function admin_attendance_store(Request $request)
{
    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
        'date' => 'required|date',
        'start_time' => 'nullable|date_format:H:i',
        'end_time' => 'nullable|date_format:H:i',
        'remarks' => 'nullable|string',
    ]);

    $worktime = Worktime::create([
        'user_id' => $validated['user_id'],
        'date' => $validated['date'],
        'start_time' => $validated['start_time'],
        'end_time' => $validated['end_time'],
        'remarks' => $validated['remarks'],
    ]);

    return redirect()->route('admin.attendance_detail', ['id' => $worktime->id])
        ->with('success', '勤怠を登録しました');
}


    public function staff_list()
    {
        $staffs = \App\Models\User::all();
        return view('admin.staff_list', compact('staffs'));
    }

    public function staff_attendance($id) {
          $user = User::findOrFail($id);
          // year_month が指定されていればその月、なければ今月
          $yearMonth = request()->input('year_month') ? Carbon::createFromFormat('Y-m', request()->input('year_month')): now();

          // 月初〜月末
          $startOfMonth = $yearMonth->copy()->startOfMonth();
          $endOfMonth = $yearMonth->copy()->endOfMonth();

          // その月の勤怠を取得（キーを日付にしておく）
          $worktimes = Worktime::where('user_id', $id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->with('breaks') ->get()
            ->keyBy(function($item){ return $item->date->format('Y-m-d'); });

          return view('admin.staff_attendance', compact( 'user', 'worktimes', 'yearMonth', 'startOfMonth', 'endOfMonth' ));
        }

    public function admin_stamp_correction_request_list()
    {
        $tab = request()->input('tab', 'pending');

        $query = \App\Models\WorktimeRequest::with(['worktime.user'])
            ->orderBy('created_at', 'desc');

        if ($tab === 'pending') {
            $query->where('approval_status', 0);
        } elseif ($tab === 'approved') {
            $query->where('approval_status', 1);
        }

        $requests = $query->get();

        return view('admin.stamp_correction_request_list', compact('requests', 'tab'));
    }

    public function admin_stamp_correction_request_approve($attendance_correct_request_id)
    {
        $request = \App\Models\WorktimeRequest::findOrFail($attendance_correct_request_id);

        return view('admin.approve', compact('request'));
    }

    public function admin_stamp_correction_request_update(Request $request, $id)
{
    $worktimeRequest = WorktimeRequest::with('requestBreaks')->findOrFail($id);
    $worktime = $worktimeRequest->worktime;

    // 出勤・退勤の反映
    $worktime->update([
        'start_time' => $worktimeRequest->requested_start_time,
        'end_time'   => $worktimeRequest->requested_end_time,
        'remarks'    => $worktimeRequest->reason,
    ]);

    // 既存休憩削除
    $worktime->breaks()->delete();

    // ★ 申請された休憩を反映（ここが本来の仕様）
    foreach ($worktimeRequest->requestBreaks as $b) {
        $worktime->breaks()->create([
            'break_start' => $b->break_start,
            'break_end'   => $b->break_end,
        ]);
    }

    // 承認ステータス更新
    $worktimeRequest->update(['approval_status' => 1]);

    return back()->with('success', '修正を承認しました');
}

}
