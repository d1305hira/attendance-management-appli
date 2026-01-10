@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="container">
    <h2 class="mb-4">勤退一覧</h2>

    @php
        $prevMonth = $yearMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $yearMonth->copy()->addMonth()->format('Y-m');
    @endphp

    <div class="calender-nav">
        <a href="{{ route('attendance_list', ['year_month' => $prevMonth]) }}" class="btn btn-primary">&larr; 前月</a>
        <span class="mx-3 h4">
          <i class="fa-solid fa-calendar-days me-2"></i>{{ $yearMonth->format('Y/m') }}
        </span>
        <a href="{{ route('attendance_list', ['year_month' => $nextMonth]) }}" class="btn btn-primary">翌月 &rarr;</a>
    </div>

    <table class="table table-bordered mt-4">
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dates as $date)
    @php
      $worktime = $worktimes[$date] ?? null;

      // 休憩（分）
      $totalBreakMinutes = 0;
        if ($worktime) {
          foreach ($worktime->breaks as $break) {
            if ($break->break_start && $break->break_end) {

              $breakStart = \Carbon\Carbon::parse($break->break_start)->copy()->setSeconds(0);
              $breakEnd = \Carbon\Carbon::parse($break->break_end)->copy()->setSeconds(0);

              $totalBreakMinutes += $breakStart->diffInMinutes($breakEnd);
              }
            }
          }

      $breakHours = floor($totalBreakMinutes / 60);
      $breakMins = $totalBreakMinutes % 60;

      // 実働
      $actualHours = null;
      $actualMins = null;

      if ($worktime && $worktime->start_time && $worktime->end_time) {
        $start = \Carbon\Carbon::parse($worktime->start_time)->copy()->seconds(0);
        $end = \Carbon\Carbon::parse($worktime->end_time)->copy()->seconds(0);

        $startMinute = $start->copy()->setSecond(0);
        $endMinute = $end->copy()->setSecond(0);

      // 勤務時間（分
      $totalWorkMinutes = $startMinute->diffInMinutes($endMinute);

      // 実働（分）
      $actualMinutes = $totalWorkMinutes - $totalBreakMinutes;
      $actualHours = floor($actualMinutes / 60);
      $actualMins = $actualMinutes % 60;
      }
    @endphp

    @php
      // 曜日取得
      $carbonDate = \Carbon\Carbon::parse($date);
      $weekday = $carbonDate->format('w'); // 0 (日曜) から 6 (土曜)
    @endphp

    <tr>
        {{-- 日付 --}}
        <td>{{ $carbonDate->format('m/d') }}({{ ['日', '月', '火', '水', '木', '金', '土'][$weekday] }})</td>

        {{-- 出勤 --}}
        <td>
            @if ($worktime && $worktime->start_time)
                {{ \Carbon\Carbon::parse($worktime->start_time)->format('H:i') }}
            @endif
        </td>

        {{-- 退勤 --}}
        <td>
            @if ($worktime && $worktime->end_time)
                {{ \Carbon\Carbon::parse($worktime->end_time)->format('H:i') }}
            @endif
        </td>

        {{-- 休憩 --}}
        <td>
            @if ($worktime && $worktime->start_time)
            {{ sprintf('%02d:%02d', $breakHours, $breakMins) }}
    @endif
        </td>

        {{-- 実働 --}}
        <td>
            @if (!is_null($actualHours))
              {{ sprintf('%02d:%02d', $actualHours, $actualMins) }}
            @endif
        </td>

        {{-- 詳細 --}}
        <td>
        @if ($worktime)
          {{-- 勤怠がある日 → ID で遷移 --}}
          <a href="{{ route('attendance_detail', ['id' => $worktime->id]) }}" class="btn btn-sm btn-secondary">詳細</a>
        @else
          {{-- 勤怠がない日 → date パラメータで遷移 --}}
          <a href="{{ route('attendance_detail', ['date' => $date]) }}" class="btn btn-sm btn-secondary">詳細</a>
        @endif
        </td>
    </tr>
@endforeach
        </tbody>
    </table>
</div>
@endsection
