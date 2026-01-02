@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/authentication.css') }}">
@endsection

@section('content')
<div class="container">
    <h2 class="title">会員登録</h2>

  <div class="row justify-content-center">
  <form method="POST" action="/register" class="authenticate center">
  @csrf
    <!-- ユーザー名 -->
    <div class="form-group_regi row">
      <label for="name">名前</label>
        <input id="name" type="text" class="form-control_regi @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}">
        @error('name')
          <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
          </span>
        @enderror
    </div>

    <!-- メールアドレス -->
    <div class="form-group_regi row">
      <label for="email">メールアドレス</label>
        <input id="email" type="email" class="form-control_regi @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}">
        @error('email')
          <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
          </span>
        @enderror
    </div>

    <!-- パスワード -->
    <div class="form-group_regi row">
      <label for="password">パスワード</label>
        <input id="password" type="password" class="form-control_regi @error('password') is-invalid @enderror" name="password">
        @error('password')
        <span class="invalid-feedback" role="alert">
          <strong>{{ $message }}</strong>
        </span>
        @enderror
    </div>

    <!-- パスワード確認 -->
    <div class="form-group_regi row">
      <label for="password-confirm" class="col-md-4 col-form-label text-md-right">{{ __('パスワード確認') }}</label>
        <input id="password-confirm" type="password" class="form-control_regi" name="password_confirmation">
    </div>

    <!-- 登録ボタン -->
    <div class="form-group_regi row mb-0">
      <button type="submit" class="registerbtn btn-primary">登録する</button>
    </div>

    <!-- ログインリンク -->
    <div class="text-center">
      <a href="/login">ログインはこちら</a>
    </div>
    </form>
  </div>
</div>
@endsection