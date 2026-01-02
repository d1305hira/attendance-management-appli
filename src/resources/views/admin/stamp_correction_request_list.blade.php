@extends('layouts.admin_app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/stamp_correction_request_list.css') }}">
@endsection

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">
      修正申請一覧
    </h2>


    <ul class="nav nav-tabs mb-4">
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
            @foreach($requests as $request)
            <tr>
                <!-- 状態 -->
                <td>{{ $request->approval_status }}</td>
                <!-- 名前 -->
                <td>{{ $request->worktime->user->name }}</td>
                <!-- 対象日時 -->
                <td>{{ optional($request->worktime->date)->format('Y/m/d') }}</td>
                <!-- 申請理由 -->
                <td>{{ $request->reason }}</td>
                <!-- 申請日時 -->
                <td>{{ optional($request->created_at)->format('Y/m/d') }}</td>
                <!-- 詳細 -->
                <td><a href="{{ route('admin.stamp_correction_request.approve', $request->id) }}">詳細</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection