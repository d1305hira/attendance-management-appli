@extends('layouts.admin_app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/staff_attendance.css') }}">
@endsection

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">{{ $user->name }}さんの勤怠</h2>

    @php
        $prevMonth = $yearMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $yearMonth->copy()->addMonth()->format('Y-m');
    @endphp

    <div class="calender-nav mb-4">
        <a href="{{ route('admin.staff.attendance', ['id' => $user->id, 'year_month' => $prevMonth]) }}" class="btn btn-primary">&laquo; 前月</a>
        <span class="mx-3 h4">{{ $yearMonth->format('Y/m') }}</span>
        <a href="{{ route('admin.staff.attendance', ['id' => $user->id, 'year_month' => $nextMonth]) }}" class="btn btn-primary">翌月 &raquo;</a>
    </div>

    <table class="table table-bordered">
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

        @php
            $current = $startOfMonth->copy();
        @endphp

        @while($current->lte($endOfMonth))
            @php
                $dateKey = $current->format('Y-m-d');
                $worktime = $worktimes->get($dateKey);

                // 休憩時間計算
                $totalBreakMinutes = $worktime
                    ? $worktime->breaks->sum(function($break){
                        return $break->start_time && $break->end_time
                            ? \Carbon\Carbon::parse($break->end_time)->diffInMinutes(\Carbon\Carbon::parse($break->start_time))
                            : 0;
                    })
                    : 0;

                $breakHours = floor($totalBreakMinutes / 60);
                $breakMinutes = $totalBreakMinutes % 60;

                // 勤務時間計算
                $workHours = '';
                if ($worktime && $worktime->start_time && $worktime->end_time) {
                    $totalWorkMinutes =
                        \Carbon\Carbon::parse($worktime->end_time)->diffInMinutes(\Carbon\Carbon::parse($worktime->start_time))
                        - $totalBreakMinutes;

                    $workHours = sprintf('%02d:%02d', floor($totalWorkMinutes / 60), $totalWorkMinutes % 60);
                }
            @endphp

            <tr>
                <td>{{ $current->format('Y/m/d') }}</td>
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
                <td>{{ sprintf('%02d:%02d', $breakHours, $breakMinutes) }}</td>
                <td>{{ $workHours }}</td>
                <td>
                    @if ($worktime)
    <a href="{{ route('admin.attendance_detail', ['id' => $worktime->id]) }}" class="btn btn-info btn-sm">詳細</a>
@else
    <a href="{{ route('admin.attendance_detail', [
        'date' => $dateKey,
        'user_id' => $user->id
    ]) }}" class="btn btn-info btn-sm">詳細</a>
@endif



                </td>
            </tr>

            @php $current->addDay(); @endphp
        @endwhile

        </tbody>
    </table>
</div>
@endsection
