@extends('layouts.admin_app')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="{{ asset('css/admin_staff_list.css') }}" rel="stylesheet">
@endsection

@section('content')
<div class="container mt-5">
    <h2 class="mb-4">スタッフ一覧</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>名前</th>
                <th>メールアドレス</th>
                <th>月次勤怠</th>
            </tr>
        </thead>
        <tbody>
            @foreach($staffs as $staff)
            <tr>
                <td>{{ $staff->name }}</td>
                <td>{{ $staff->email }}</td>
                <td>
                    <a href="{{ route('admin.staff.attendance', ['id' => $staff->id]) }}" class="btn btn-info">詳細</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection