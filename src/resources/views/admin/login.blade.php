@extends('layouts.admin_app')

@section('content')
<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">管理者ログイン</div>

        <div class="card-body">
          <form method="POST" action="{{ route('admin.login') }}">
          @csrf

            <div class="mb-3">
              <label for="email" class="form-label">メールアドレス</label>
                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus>
                  @error('email')
                  <span class="invalid-feedback" role="alert">
                      <strong>{{ $message }}</strong>
                  </span>
                  @enderror
              </div>

              <div class="mb-3">
                <label for="password" class="form-label">パスワード</label>
                  <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                    @error('password')
                    <span class="invalid-feedback" role="alert">
                      <strong>{{ $message }}</strong>
                    </span>
                    @enderror
              </div>

              <div class="mb-0">
                <button type="submit" class="btn btn-primary">ログイン
                </button>
              </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection