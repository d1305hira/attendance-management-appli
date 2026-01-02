@extends('layouts.admin_app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_list.css') }}">
@endsection

@section('content')
<div class="container mt-5">

    <h2 class="mb-4">{{ $yearMonthDay->format('Y年m月d日') }}の勤怠</h2>

    @php
        $prevDay = $yearMonthDay->copy()->subDay()->format('Y-m-d');
        $nextDay = $yearMonthDay->copy()->addDay()->format('Y-m-d');
    @endphp

    <div class="calender-nav">
        <a href="{{ route('admin.attendance_list', ['year_month_day' => $prevDay]) }}" class="btn btn-primary">&laquo; 前日</a>
        <span class="mx-3 h4">{{ $yearMonthDay->format('Y/m/d') }}</span>
        <a href="{{ route('admin.attendance_list', ['year_month_day' => $nextDay]) }}" class="btn btn-primary">翌日 &raquo;</a>
    </div>

    <table class="table mt-4">
        <thead>
            <tr>
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>

        <tbody>
            @foreach($worktimes as $worktime)
            @php
                // 休憩計算
                $totalBreakMinutes = 0;
                foreach ($worktime->breaks as $break) {
                    if ($break->break_start && $break->break_end) {
                        $start = \Carbon\Carbon::parse($break->break_start)->setSeconds(0);
                        $end   = \Carbon\Carbon::parse($break->break_end)->setSeconds(0);
                        $totalBreakMinutes += $start->diffInMinutes($end);
                    }
                }
                $breakHours = floor($totalBreakMinutes / 60);
                $breakMins  = $totalBreakMinutes % 60;

                // 実働
                $actual = '--:--';
                if ($worktime->start_time && $worktime->end_time) {
                    $start = \Carbon\Carbon::parse($worktime->start_time)->setSeconds(0);
                    $end   = \Carbon\Carbon::parse($worktime->end_time)->setSeconds(0);
                    $workMinutes = $start->diffInMinutes($end) - $totalBreakMinutes;
                    $actual = sprintf('%02d:%02d', floor($workMinutes / 60), $workMinutes % 60);
                }
            @endphp

            <tr>
                <td>{{ $worktime->user->name }}</td>
                <td>{{ optional($worktime->start_time)->format('H:i') }}</td>
                <td>{{ optional($worktime->end_time)->format('H:i') }}</td>
                <td>{{ sprintf('%02d:%02d', $breakHours, $breakMins) }}</td>
                <td>{{ $actual }}</td>
                <td>
                    <a href="{{ route('admin.attendance_detail', ['id' => $worktime->id, 'date' => $yearMonthDay->format('Y-m-d')]) }}"
                      class="btn-sm btn-secondary">
                      詳細
                    </a>
                </td>
            </tr>

            @endforeach
        </tbody>
    </table>
</div>
@endsection
