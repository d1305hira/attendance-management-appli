<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>COACHTECH</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('/css/reset.css')  }}">
  <link rel="stylesheet" href="{{ asset('/css/common.css')  }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  @yield('css')
</head>

<body>
  <header class="bg-dark text-white py-2">
    <div class="container">
      <div class="row align-items-center">
        <!-- 左側：ロゴ -->
        <div class="col-md-2">
          <p>COACHTECH</p>
        </div>

        <!-- 右側：ナビゲーション -->
        <div class="header-nav col-md-10 text-end">
          @yield('header-nav')
          @auth
          <div class="d-flex justify-content-end align-items-center gap-4">
            <a href="{{ route('attendance.index') }}">勤怠</a>
            <a href="{{ route('attendance_list') }}">勤怠一覧</a>
            <a href="{{ route('stamp_correction_request_list') }}">申請一覧</a>
            <form method="POST" action="{{ route('logout') }}">
              @csrf
            <button type="submit" class="btn btn-link text-white">ログアウト</button>
            </form>
          </div>
        @endauth
        </div>
      </div>
    </div>
  </header>

  <main class="py-4">
  @yield('content')
  </main>

</body>
</html>
