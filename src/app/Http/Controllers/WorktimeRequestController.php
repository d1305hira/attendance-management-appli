<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Worktime;
use App\Models\WorktimeRequest;

use Carbon\Carbon;


class WorktimeRequestController extends Controller
{
  public function stamp_correction_request_list()
  {
    $tab = request()->input('tab', 'pending');

    if (auth()->guard('admin')->check()) {
        $requests = WorktimeRequest::with('worktime.user')
            ->where('approval_status', $tab === 'approved' ? 1 : 0)
            ->orderBy('created_at', 'desc')
            ->get();
    } else {
        $userId = auth()->id();

        $requests = WorktimeRequest::whereHas('worktime', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where('approval_status', $tab === 'approved' ? 1 : 0)
            ->with('worktime.user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    return view('stamp_correction_request_list', compact('requests', 'tab'));
  }



    public function store(Request $request, $worktimeId)
{
    $request->validate([
        'start_time'    => 'required|date_format:H:i',
        'end_time'      => 'required|date_format:H:i',
        'break_start'   => 'nullable|array',
        'break_start.*' => 'nullable|date_format:H:i',
        'break_end'     => 'nullable|array',
        'break_end.*'   => 'nullable|date_format:H:i',
        'remarks'       => 'required|string|max:255',
    ],[
      'remarks.required' => '備考を記入してください',
    ]
  );

    $worktime = Worktime::findOrFail($worktimeId);
    $date = $worktime->date->format('Y-m-d');

    // 出勤・退勤
    $start = Carbon::parse("$date {$request->start_time}");
    $end   = Carbon::parse("$date {$request->end_time}");

    if ($start->gte($end)) {
        return back()->withErrors(['end_time' => '出勤時間もしくは退勤時間が不適切な値です'])
                    ->withInput();
    }

    // 休憩の保存
    $breakStarts = (array)$request->break_start;
    $breakEnds   = (array)$request->break_end;

    foreach ($breakStarts as $i => $startValue) {
        $endValue = $breakEnds[$i] ?? null;

        if (!$startValue && !$endValue) continue;

        // Carbon変換
        $breakStart = $startValue ? Carbon::parse("$date $startValue") : null;
        $breakEnd   = $endValue ? Carbon::parse("$date $endValue") : null;

        // 休憩開始時間＜出勤時間
        if ($breakStart && $breakStart->lt($start)) {
            return back()
            ->withErrors(['break_start.' . $i => '休憩時間が不適切な値です'])->withInput();
        }

        //休憩開始時間＞退勤時間
        if ($breakStart && $breakStart->gt($end)) {
            return back()
            ->withErrors(['break_start.' . $i => '休憩時間もしくは退勤時間が不適切な値です'])->withInput();
        }

        // 休憩終了時間＞退勤時間
        if ($breakEnd && $breakEnd->gt($end)) {
            return back()
            ->withErrors(['break_end.' . $i => '休憩時間もしくは退勤時間が不適切な値です'])->withInput();
        }

        // 休憩時間の整合性チェック
        if ($breakStart && $breakEnd && $breakStart->gte($breakEnd)) {
            return back()
            ->withErrors(['break_end.' . $i => '休憩時間が不適切な値です'])->withInput();
        }
    }

    // 申請ヘッダ作成
    $requestHeader = WorktimeRequest::create([
        'worktime_id' => $worktime->id,
        'requested_start_time' => $start,
        'requested_end_time' => $end,
        'reason' => $request->remarks,
        'approval_status' => 0,
    ]);

    foreach ($breakStarts as $i => $startValue) {
        $endValue = $breakEnds[$i] ?? null;

        if (!$startValue && !$endValue) continue;

        $requestHeader->requestBreaks()->create([
            'break_start' => "$date $startValue",
            'break_end'   => "$date $endValue",
        ]);
    }

    return back()->with('success', '修正申請を送信しました');
}

public function approve($requestId)
{
    $requestData = WorktimeRequest::with('requestBreaks')->findOrFail($requestId);
    $worktime = $requestData->worktime;

    // 出勤・退勤・備考を更新
    $worktime->update([
        'start_time' => $requestData->requested_start_time,
        'end_time'   => $requestData->requested_end_time,
        'remarks'    => $requestData->reason,
    ]);

    // 既存の休憩を全削除
    $worktime->breaks()->delete();

    // 申請された休憩を反映
    foreach ($requestData->requestBreaks as $b) {
    $worktime->breaks()->create([
        'break_start' => $worktime->date->format('Y-m-d') . ' ' . $b->break_start->format('H:i'),
        'break_end'   => $worktime->date->format('Y-m-d') . ' ' . $b->break_end->format('H:i'),
    ]);
}


    // 申請ステータス更新
    $requestData->update([
        'approval_status' => 1,
    ]);

    return redirect()->back()->with('success', '修正申請を承認しました');
    }
}