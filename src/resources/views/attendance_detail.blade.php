@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_detail.css') }}">
@endsection

@section('content')
<div class="container">
  <div class="title">
    <h2>勤怠詳細</h2>
  </div>

  <div class="container-form">
    {{-- 修正申請フォーム --}}
    <form action="{{ route('attendance.correction_request', optional($worktime)->id ?? 0) }}" method="POST">
    @csrf

    {{-- 名前 --}}
    <div class="name">
      <div class="label">名前</div>
      <div class="user-name">{{ $user->name }}</div>
    </div>

    {{-- 日付 --}}
    <div class="workdate">
      <div class="label">日付</div>
        <div class="date-box">
          <div class="year">{{ $date->format('Y年') }}</div>
          <div class="date">{{ $date->format('m月d日') }}</div>
        </div>
    </div>

    @php
      // 承認待ち判定
      $isPending = $requestData ? ($requestData->approval_status == 0) : false;
    @endphp

    {{-- 出勤・退勤 --}}
    <div class="attendance">
      <div class="label">出勤・退勤</div>
      <div class="time-box">
        @if ($isPending)
          {{-- ★ 申請された出勤時間を表示 --}}
          {{ optional($requestData->requested_start_time)->format('H:i') }}
        @else
          <input type="time" name="start_time"
            value="{{ old('start_time', $worktime->start_time ? $worktime->start_time->format('H:i') : '') }}">
        @endif
      </div>

      <div class="tilde">〜</div>

      <div class="time-box">
        @if ($isPending)
          {{-- ★ 申請された退勤時間を表示 --}}
          {{ optional($requestData->requested_end_time)->format('H:i') }}
        @else
          <input type="time" name="end_time"
            value="{{ old('end_time', $worktime->end_time ? $worktime->end_time->format('H:i') : '') }}">
        @endif
      </div>

      @error('end_time')
        <div class="error-message">{{ $message }}</div>
      @enderror
    </div>

    {{-- 休憩欄 --}}
    @php
      // ★ 承認待ちなら申請された休憩を表示
      if ($isPending && $requestData && $requestData->requestBreaks->count()) {
          $breaks = $requestData->requestBreaks;
      } else {
          $breaks = $worktime ? $worktime->breaks : collect();
      }

      // 最低1行は表示
      if ($breaks->isEmpty()) {
          $breaks->push((object)[ 'break_start' => null, 'break_end' => null ]);
      }
    @endphp

    @foreach ($breaks as $i => $break)
    <div class="break-row">
      <div class="label">
      @if ($i === 0)
        休憩
      @else
        休憩{{ $i + 1 }}
      @endif
      </div>

      <div class="time-box">
        @if ($isPending)
          {{-- ★ 申請された休憩開始 --}}
          {{ optional($break->break_start)->format('H:i') }}
        @else
          <input type="time" name="break_start[{{ $i }}]"
            value="{{ old("break_start.$i", $break->break_start ? $break->break_start->format('H:i') : '') }}">
        @endif
      </div>

      <div class="tilde">〜</div>

      <div class="time-box">
        @if ($isPending)
          {{-- ★ 申請された休憩終了 --}}
          {{ optional($break->break_end)->format('H:i') }}
        @else
          <input type="time" name="break_end[{{ $i }}]"
            value="{{ old("break_end.$i", $break->break_end ? $break->break_end->format('H:i') : '') }}">
        @endif
      </div>

      @error("break_end.$i")
        <div class="error-message">{{ $message }}</div>
      @enderror
    </div>
    @endforeach

    {{-- 備考 --}}
    <div class="remarks">
      <div class="label">備考</div>
        <div class="remarks_text">
        @if ($isPending)
          {{-- ★ 申請された備考 --}}
          {{ $requestData->reason }}
        @else
          <textarea name="remarks" class="remarks_box">{{ old('remarks', optional($worktime)->remarks ?? '') }}</textarea>

          @error('remarks')
            <div class="error-message">{{ $message }}</div>
          @enderror
        @endif
        </div>
    </div>
  </div>

    {{-- ボタン --}}
    @if ($isPending)
      <button type="button" class="button-disabled" disabled>*承認待ちのため修正できません。</button>
    @else
      <button type="submit" class="button-enabled">修正</button>
    @endif
  </div>


</div>
@endsection
