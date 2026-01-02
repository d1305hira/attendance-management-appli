<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Worktime;
use App\Models\WorkBreak;
use App\Models\WorktimeRequest;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index() { $today = now()->toDateString();

      // 今日の勤務データを取得
      $worktime = Worktime::where('user_id', auth()->id()) ->where('date', $today) ->latest() ->first();
      // 状態判定
      if (!$worktime) {
        $status = '勤務外'; // 出勤前
      } elseif ($worktime->status == 1) {
      $status = '出勤中'; } elseif ($worktime->status == 2) { $status = '休憩中'; } elseif ($worktime->status == 3) { $status = '退勤済'; } else { $status = '勤務外'; } return view('attendance', compact('status')); }

    public function store(Request $request)
    {
      $today = now()->toDateString();

      $worktime = Worktime::where('user_id', auth()->id())
          ->where('date', $today)
          ->exists();

      if($worktime){
          return back()->withErrors(['date' => '本日の勤退は既に記録されています']);
      }

      Worktime::create([
          'user_id' => auth()->id(),
          'date' => $today,
          'start_time' => now(),
          'status' => 1,
      ]);

      return redirect()->route('attendance.index')->with('status', 1);
    }

    public function end(Request $request)
    {
      $today = now()->toDateString();

      $worktime = Worktime::where('user_id', auth()->id())
          ->where('date', $today)
          ->where('status', 1)
          ->first();

      if ($worktime) {
          $worktime->update([
              'end_time' => now(),
              'status' => 3,
          ]);
      }

      return redirect()->route('attendance.index')->with('status', 3);
    }

    public function break(Request $request)
    {
      $today = now()->toDateString();

      $worktime = Worktime::where('user_id', auth()->id())
          ->where('date', $today)
          ->where('status', 1)
          ->first();

      if ($worktime) {
          $worktime->update([
              'status' => 2,
          ]);

          WorkBreak::create([
              'worktime_id' => $worktime->id,
              'break_start' => now(),
          ]);
      }

      return redirect()->route('attendance.index')->with('status', 2);
    }

    public function break_end(Request $request)
    {
      $today = now()->toDateString();

      $worktime = Worktime::where('user_id', auth()->id())
          ->where('date', $today)
          ->where('status', 2)
          ->first();

      if ($worktime) {
          $worktime->update([
              'status' => 1,
          ]);

          $break = WorkBreak::where('worktime_id', $worktime->id)
              ->whereNull('break_end')
              ->latest('break_start')
              ->first();

          if ($break) {
              $break->update([
                  'break_end' => now(),
              ]);
          }
      }

      return redirect()->route('attendance.index')->with('status', 1);
    }

    public function attendance_list()
    {
      $yearMonth = request()->input('year_month')
          ? Carbon::createFromFormat('Y-m', request()->input('year_month'))
          : now();

      $startOfMonth = $yearMonth->copy()->startOfMonth();
      $endOfMonth = $yearMonth->copy()->endOfMonth();

      $worktimes = Worktime::with('breaks')
          ->whereYear('date', $yearMonth->year)
          ->whereMonth('date', $yearMonth->month)
          ->where('user_id', auth()->id())
          ->orderBy('date', 'asc')
          ->get()
          ->keyBy(function ($item) {
              return $item->date->toDateString();
          });

        $dates = [];
        for ($date = $startOfMonth->copy(); $date->lte($endOfMonth); $date->addDay()) {
            $dates[] = $date->copy()->toDateString();
        }

        return view('attendance_list', compact('worktimes', 'yearMonth', 'dates'));
    }

    public function attendance_detail($id)
    {
      $user = auth()->user();

      $worktime = Worktime::with('breaks')->find($id);

      $date = $worktime
          ? Carbon::parse($worktime->date)
          : (request()->query('date') ? Carbon::parse(request()
          ->query('date')):Carbon::today());

      // 勤怠がない日でも詳細画面に遷移したい場合
      if (!$worktime) {
        $existing= Worktime::where('user_id', auth()->id())
        ->where('date', $date)
        ->first();

        if ($existing) {
          $worktime = $existing;}else{
            $worktime = Worktime::create([
              'user_id' => auth()->id(),
              'date' => $date,
              'start_time' => null,
              'end_time' => null,
              'status' => 0,
            ]);
          }

        $worktime->setRelation('breaks', collect([]));
        }

        $requestData = WorktimeRequest::where('worktime_id', $worktime->id)
          ->with('requestBreaks')
          ->orderBy('created_at', 'desc')
          ->first();

        // ★ 休憩合計時間（分）を計算
        $totalBreakMinutes = $worktime->breaks->sum(function ($break) {
          if ($break->break_start && $break->break_end) {
            return $break->break_end->diffInMinutes($break->break_start);
          }
          return 0;
        });

        $startValue = optional($worktime->start_time)->format('H:i') ?? '';
        $endValue = optional($worktime->end_time)->format('H:i') ?? '';

        return view('attendance_detail', compact(
          'worktime',
          'user',
          'date',
          'totalBreakMinutes',
          'startValue',
          'endValue',
          'requestData'
        ));
    }

}
