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

    {{-- 🔥 勤怠がある日もない日もフォームを表示 --}}
    <form method="POST"
      action="{{ $worktime->id
          ? route('admin.attendance_update', ['id' => $worktime->id])
          : route('admin.attendance_store') }}">

      @csrf

      {{-- 名前 --}}
      <div class="name">
        <div class="label">名前</div>
        <div class="time-group">
          <div class="user-name">{{ $user->name }}</div>
        </div>
      </div>

      {{-- 日付 --}}
      <div class="workdate">
        <div class="label">日付</div>
        <div class="time-group">
          <div class="time-box">{{ $date->format('Y年') }}</div>
          <div class="tilde"></div>
          <div class="time-box">{{ $date->format('m月d日') }}</div>
        </div>
      </div>

      @php
        $isPending = $worktime->id
            ? $worktime->requests()->where('approval_status', 0)->exists()
            : false;

        $startValue = optional($worktime->start_time)->format('H:i') ?? '';
        $endValue   = optional($worktime->end_time)->format('H:i') ?? '';
      @endphp

      {{-- 出勤・退勤 --}}
      <div class="attendance">
        <div class="label">出勤・退勤</div>

        <div class="time-group">
          <div class="time-box">
            <input type="time" name="start_time" value="{{ old('start_time', $startValue) }}">
            @error('start_time')
              <div class="error-message">{{ $message }}</div>
            @enderror
          </div>

          <div class="tilde">〜</div>

          <div class="time-box">
            <input type="time" name="end_time" value="{{ old('end_time', $endValue) }}">
            @error('end_time')
              <div class="error-message">{{ $message }}</div>
            @enderror
          </div>
        </div>
      </div>

      {{-- 休憩欄 --}}
      @php
        $breaks = $worktime->breaks->concat([
          (object)['break_start' => null, 'break_end' => null]
        ]);
      @endphp

      @foreach ($breaks as $i => $break)
      <div class="break-row">
        <div class="label">
          {{ $i === 0 ? '休憩' : '休憩'.($i+1) }}
        </div>

        {{-- 休憩開始 --}}
        <div class="time-group">
          <div class="time-box">
            <input type="time" name="break_start[{{ $i }}]"
              value="{{ old("break_start.$i", optional($break->break_start)->format('H:i')) }}">
            @error("break_start.$i")
              <div class="error-message">{{ $message }}</div>
            @enderror
          </div>

          <div class="tilde">〜</div>

          {{-- 休憩終了 --}}
          <div class="time-box">
            <input type="time" name="break_end[{{ $i }}]"
              value="{{ old("break_end.$i", optional($break->break_end)->format('H:i')) }}">
            @error("break_end.$i")
              <div class="error-message">{{ $message }}</div>
            @enderror
          </div>
        </div>
      </div>
      @endforeach

      {{-- 備考 --}}
      <div class="remarks">
        <div class="label">備考</div>
        <div class="remarks_text">
          <textarea name="remarks" class="remarks_box">{{ old('remarks', $worktime->remarks) }}</textarea>
          @error('remarks')
            <div class="error-message">{{ $message }}</div>
          @enderror
        </div>
      </div>

      {{-- ボタン --}}
      <div class="button-group">
        @if ($isPending)
          <div class="button disabled">承認待ちのため修正できません</div>
        @else
          <button type="submit" class="button-enabled">修正</button>
        @endif
      </div>
    </form>
  </div>
</div>
@endsection
