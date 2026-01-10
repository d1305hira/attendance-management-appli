<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>管理者ログイン</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('/css/reset.css') }}">
  <link rel="stylesheet" href="{{ asset('/css/common.css') }}">
  @yield('css')
</head>

<body>
  <header class="app-header">
  <div class="app-container d-flex justify-content-between align-items-center">

    <!-- 左：ロゴ -->
    <div class="header-logo">
      COACHTECH
    </div>

    <!-- 右：ナビゲーション -->
    <nav class="header-nav d-flex align-items-center">
      @yield('header-nav')

      @auth('admin')
        <a href="{{ route('admin.attendance_list') }}">勤怠一覧</a>
        <a href="{{ route('admin.staff.list') }}">スタッフ一覧</a>
        <a href="{{ route('stamp_correction_request_list') }}">申請一覧</a>

        <form method="POST" action="{{ route('admin.logout') }}" class="m-0">
          @csrf
          <button type="submit" class="logout-btn">ログアウト</button>
        </form>
      @endauth
    </nav>

  </div>
</header>


  <main class="py-4">
    @yield('content')
  </main>
</body>
</html>
