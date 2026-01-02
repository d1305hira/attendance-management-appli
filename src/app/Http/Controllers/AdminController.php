<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Worktime;
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

    public function admin_attendance_detail($id)
    {
        $date = request()->input('date');
        $user_id = request()->input('user_id');

        if($id == 0){
          $worktime = new Worktime([
            'user_id' => $user_id,
            'date' => $date,
            'start_time' => null,
            'end_time' => null,
          ]);

        return view('admin.attendance_detail',
        [ 'worktime' => $worktime,
          'user' => User::findOrFail($user_id),
          ]);
        }

        $worktime = Worktime::with(['user', 'breaks'])->findOrFail($id);

        return view('admin.attendance_detail', 
        [ 'worktime' => $worktime,
          'user' => $worktime->user,
          ]);
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

        $query = \App\Models\WorktimeRequest::with(['worktime.user']);

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
        //修正申請データ
        $worktimeRequest = \App\Models\WorktimeRequest::findOrFail($id);

        //対象のworktimeデータ
        $worktime = $worktimeRequest->worktime;

        //出勤・退勤時間の更新
        $worktime->update([
            'start_time' => $worktimeRequest->requested_start_time,
            'end_time' => $worktimeRequest->requested_end_time,
        ]);

        //休憩時間の更新
        $worktime->breaks()->delete();

        //承認画面で入力された休憩時間を登録
        if (is_array($request->break_start)) {
        foreach ($request->break_start as $i => $start) {
            $end = $request->break_end[$i] ?? null;

            if($start || $end){
              \App\Models\WorkBreak::create([
                'worktime_id' => $worktime->id,
                'break_start' => $start,
                'break_end' => $end,
                ]);
              }
            }
          }

        //修正申請の承認ステータスを更新
        $worktimeRequest->update([
            'approval_status' => '1']);

        return redirect()->route('admin.stamp_correction_request.update', ['id' => $id])
            ->with('success', '修正を承認しました');
    }
}
