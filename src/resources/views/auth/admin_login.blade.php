@extends('layouts.admin_app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/authentication.css') }}">
@endsection

@section('content')
<div class="container">
    <h2 class="title">管理者ログイン</h2>

    <div class="row justify-content-center">
      <form method="POST" action="/admin/login" class="authenticate center">
      @csrf

      <!-- メールアドレス -->
      <div class="form-group row">
        <label for="email">メールアドレス</label>
          <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}">
            @error('email')
              <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
              </span>
            @enderror
      </div>

      <!-- パスワード -->
      <div class="form-group row">
        <label for="password">パスワード</label>
          <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" >
            @error('password')
              <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
              </span>
            @enderror
      </div>

      <!-- ログインする -->
      <div class="form-group row mb-0">
        <button type="submit" class="loginbtn btn-primary">管理者ログインする</button>
      </div>
      </form>
    </div>
</div>
@endsection