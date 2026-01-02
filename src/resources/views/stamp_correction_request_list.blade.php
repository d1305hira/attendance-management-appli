@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/stamp_correction_request_list.css') }}">
@endsection

@section('content')
<div class="container">
    <div class="mb-4">
        <h2 class="title">
            申請一覧
        </h2>

        <ul class="nav nav-tabs">
          <li class="nav-item">
            <a href="{{ route('stamp_correction_request_list', ['tab' => 'pending']) }}" class="{{ request('tab', 'pending') === 'pending' ? 'active' : '' }}">承認待ち</a>
          </li>
          <li class="nav-item">
            <a href="{{ route('stamp_correction_request_list', ['tab' => 'approved']) }}" class="{{ request('tab') === 'approved' ? 'active' : '' }}">承認済み</a>
          </li>
        </ul>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($requests as $request)
                <tr>
                    <td>{{ $request->approval_status_label }}</td>
                    <td>{{ optional($request->worktime->user)->name }}</td>
                    <td>{{ $request->worktime->date->format('Y/m/d') }}</td>
                    <td>{{ $request->reason }}</td>
                    <td>{{ $request->created_at->format('Y/m/d') }}</td>
                    <td><a href="{{ route('attendance_detail', ['id' => $request->worktime->id]) }}">詳細</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection