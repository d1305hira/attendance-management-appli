@extends('layouts.admin_app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/approve.css') }}">
@endsection

@section('content')

<div class="container">
  <div class="title">
    <h2>勤怠詳細</h2>
  </div>

  <div class="container-form">
  {{-- 承認フォーム --}}
  <form action="{{ route('admin.stamp_correction_request.update', $request->id) }}" method="POST">
    @csrf

    {{-- 名前 --}}
    <div class="name">
      <div class="label">名前</div>
      <div class="user-name">
        {{ $request->worktime->user->name }}
      </div>
    </div>

    {{-- 日付 --}}
    <div class="workdate">日付
      <div class="year">{{ optional($request->worktime->date)->format('Y年') }}</div>
      <div class="date">{{ optional($request->worktime->date)->format('m月d日') }}</div>
    </div>

    {{-- 出勤・退勤 --}}
    <div class="attendance">出勤・退勤
      <div class="time-box">
        {{ $request->requested_start_time ? $request->requested_start_time->format('H:i') : optional($request->worktime->start_time)->format('H:i') }}
      </div>

      <div class="tilde">〜</div>

      <div class="time-box">
        {{ $request->requested_end_time ? $request->requested_end_time->format('H:i') : optional($request->worktime->end_time)->format('H:i') }}
      </div>
    </div>

    {{-- 休憩 --}}
    @php
      $breaks = $request->worktime->breaks;
    @endphp

    @foreach ($breaks as $i => $break)
    <div class="break-row">
      休憩{{ $i + 1 }}

      <div class="time-box">
        <input type="time" name="break_start[{{ $i }}]" value="{{ optional($break->break_start)->format('H:i') }}" class="time-box">
      </div>

      <div class="tilde">〜</div>

      <div class="time-box">
        <input type="time" name="break_end[{{ $i }}]" value="{{ optional($break->break_end)->format('H:i') }}" class="time-box">
      </div>
    </div>
    @endforeach

    {{-- 備考 --}}
    <div class="remarks">備考
      <div class="remarks_text">
        {{ $request->reason }}
      </div>
    </div>

    {{-- ボタン --}}
    @if ($request->approval_status == 1)
    <div class="button-wrapper">
      <button type="button" class="button-disabled" disabled>承認済</button>
    </div>
    @else
    <div class="button-wrapper">
      <button type="submit" class="button-enabled">承認</button>
    </div>
    @endif

  </form>
  </div>
</div>
@endsection
