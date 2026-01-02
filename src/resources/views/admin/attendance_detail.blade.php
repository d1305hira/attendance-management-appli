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

    {{-- ✅ 修正申請フォーム（常に表示） --}}
    <form action="{{ route('attendance.correction_request', optional($worktime)->id ?? 0) }}" method="POST">
    @csrf

    {{-- ✅ 名前 --}}
    <div class="name">
      <div class="label">名前</div>
      <div class="user-name">{{ $user->name }}</div>
    </div>

    {{-- ✅ 日付 --}}
    <div class="workdate">
      <div class="label">日付</div>
      <div class="date-box">
        <div class="year">{{ optional(optional($worktime)->date)->format('Y年') ?? '' }}</div>
        <div class="date">{{ optional(optional($worktime)->date)->format('m月d日') ?? '' }}</div>
      </div>
    </div>

    @php
      // ✅ 承認待ち判定（null のときは false）
      $isPending = $worktime
          ? $worktime->requests()->where('approval_status', '承認待ち')->exists()
          : false;

      // ✅ 出勤・退勤（null → 空白）
      $startValue = optional(optional($worktime)->start_time)->format('H:i') ?? '';
      $endValue   = optional(optional($worktime)->end_time)->format('H:i') ?? '';
    @endphp

    {{-- ✅ 出勤・退勤 --}}
    <div class="attendance">
      <div class="label">出勤・退勤</div>
      <div class="time-box">
        @if ($isPending)
          {{ $startValue }}
        @else
          <input type="time" name="start_time" value="{{ old('start_time', $startValue) }}">
        @endif
      </div>

      <div class="tilde">〜</div>

      <div class="time-box">
        @if ($isPending)
          {{ $endValue }}
        @else
          <input type="time" name="end_time" value="{{ old('end_time', $endValue) }}">
        @endif
      </div>

      @error('end_time')
            <div class="error-message">
              {{ $message }}
            </div>
      @enderror
    </div>

    {{-- ✅ 休憩欄を最低1行表示 --}}
    @php
    $breaks = $worktime ? $worktime->breaks : collect();

    $breaks = $breaks->concat([
      (object)[
        'break_start' => null,
        'break_end' => null
      ]]);
    @endphp

    {{-- ✅ 休憩 --}}
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
        <input type="time" name="break_start[{{ $i }}]" value="{{ old("break_start.$i", optional($break->break_start)->format('H:i')) }}">
      </div>

      @error("break_start.$i")
        <div class="error-message">{{ $message }}</div>
      @enderror

      <div class="tilde">〜</div>

      <div class="time-box">
        <input type="time" name="break_end[{{ $i }}]" value="{{ old("break_end.$i", optional($break->break_end)->format('H:i')) }}">
      </div>

      @error("break_end.$i")
        <div class="error-message">{{ $message }}</div>
      @enderror
    </div>
    @endforeach

    {{-- ✅ 備考 --}}
    <div class="remarks">
      <div class="label">備考</div>
      <div class="remarks_text">
        @if ($isPending)
          {{ optional($worktime)->remarks ?? '' }}
        @else
          <textarea name="remarks" class="remarks_box">{{ old('remarks', optional($worktime)->remarks ?? '') }}</textarea>

          @error('remarks')
            <div class="error-message">
              {{ $message }}
            </div>
          @enderror
        @endif
      </div>
    </div>

    {{-- ✅ ボタン --}}
    @if ($isPending)
      <button type="button" class="button disabled" disabled>承認待ちのため修正できません</button>
    @else
      <button type="submit" class="button">修正</button>
    @endif

    </form>
  </div>
</div>
@endsection