@extends('layouts.admin_app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')

<div class="container">
  <div class="title">
    <h2>勤怠詳細</h2>
  </div>

  <div class="container-form">

  {{-- 承認フォーム --}}
  <form action="{{ route('admin.stamp_correction_request.update', ['attendance_correct_request_id' => $request->id]) }}" method="POST">

      @csrf

      {{-- 名前 --}}
      <div class="name">
        <div class="label">名前</div>
        <div class="time-group">
          <div class="time-box user-name-box">
            {{ $request->worktime->user->name }}
          </div>
          <div class="tilde"></div>
          <div class="time-box empty-box"></div>
        </div>
      </div>

      {{-- 日付 --}}
      <div class="workdate">
        <div class="label">日付</div>
        <div class="time-group">
          <div class="time-box">{{ optional($request->worktime->date)->format('Y年') }}</div>
          <div class="time-box">{{ optional($request->worktime->date)->format('m月d日') }}</div>
        </div>
      </div>

      {{-- 出勤・退勤 --}}
      <div class="attendance">
        <div class="label">出勤・退勤</div>
        <div class="time-group">
          <div class="time-box">
            {{ $request->requested_start_time ? $request->requested_start_time->format('H:i') : optional($request->worktime->start_time)->format('H:i') }}
          </div>

          <div class="tilde">〜</div>

          <div class="time-box">
            {{ $request->requested_end_time ? $request->requested_end_time->format('H:i') : optional($request->worktime->end_time)->format('H:i') }}
          </div>
        </div>
      </div>

      {{-- 休憩 --}}
      @php
        $breaks = $request->requestBreaks->isNotEmpty()
          ? $request->requestBreaks
        : $request->worktime->breaks;
      @endphp

      @foreach ($breaks as $i => $break)
      <div class="break-row">
        <div class="label">
          休憩{{ $i + 1 }}
        </div>
        <div class="time-group">
          <div class="time-box">
            {{ \Carbon\Carbon::parse($break->break_start)->format('H:i') }}
          </div>

          <div class="tilde">〜</div>

          <div class="time-box">
            {{ \Carbon\Carbon::parse($break->break_end)->format('H:i') }}
          </div>
        </div>
      </div>
      @endforeach

      {{-- 備考 --}}
      <div class="remarks">
        <div class="label">備考</div>
        <div class="remarks_text">
          {{ $request->reason }}
        </div>
      </div>
  </div>

  <div class="button-group">
    {{-- ボタン --}}
    @if ($request->approval_status == 1)
      <div class="button-wrapper">
        <button type="button" class="button-approve-comp" disabled>承認済</button>
      </div>
    @else
      <div class="button-wrapper">
        <button type="submit" class="button-approve">承認</button>
      </div>
    @endif
  </div>
  </form>
</div>
@endsection
