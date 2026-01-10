@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="container">
    <div class="mb-4">

        {{-- 勤務状態表示 --}}
        <p class="status-display">
            {{ $status }}
        </p>

        {{-- 日付表示 --}}
        <div class="day mb-3">
            <span id="date"></span>
        </div>

        {{-- 時刻表示 --}}
        <div class="time mb-3">
            <span id="time"></span>
        </div>

        {{-- hidden date --}}
        <input type="hidden" id="hiddenDate">

        {{-- 状態ごとのボタン表示 --}}
        @if ($status === '勤務外')
          {{-- 出勤 --}}
          <form method="POST" action="{{ route('attendance.store') }}">
          @csrf
            <button type="submit" class="btn-attendance btn-start">出勤</button>
          </form>

        @elseif ($status === '出勤中')
        <div class="btn-row">
          {{-- 退勤 --}}
          <form method="POST" action="{{ route('attendance.end') }}">
            @csrf
            <button type="submit" class="btn-attendance btn-end">退勤</button>
          </form>

          {{-- 休憩開始 --}}
          <form method="POST" action="{{ route('attendance.break') }}">
            @csrf
            <button type="submit" class="btn-attendance btn-break">休憩入</button>
          </form>
        </div>

        @elseif ($status === '休憩中')
          {{-- 休憩終了 --}}
          <form method="POST" action="{{ route('attendance.break_end') }}">
          @csrf
            <button type="submit" class="btn-attendance btn-break-end">休憩戻</button>
          </form>

        @elseif ($status === '退勤済')
          <p class="mt-5">お疲れ様でした。</p>
        @endif
    </div>
</div>

{{-- 時計表示 --}}
<script>
    function updateClock() {
        const now = new Date();

        // hidden フィールドに日付をセット (YYYY-MM-DD形式)
        document.querySelectorAll('#hiddenDate').forEach(el => {
            el.value = now.toISOString().split('T')[0];
        });

        // 年月日＋曜日表示
        const year = now.getFullYear();
        const month = now.getMonth() + 1;
        const day = now.getDate();
        const weekday = ['日', '月', '火', '水', '木', '金', '土'][now.getDay()];

        document.getElementById('date').textContent =
            `${year}年${month}月${day}日(${weekday})`;

        // 時刻表示
        const timeOptions = { hour: '2-digit', minute: '2-digit' };
        document.getElementById('time').textContent =
            now.toLocaleTimeString('ja-JP', timeOptions);
    }

    updateClock();
    setInterval(updateClock, 1000);
</script>
@endsection
