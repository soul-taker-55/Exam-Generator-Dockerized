<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  {{-- Font Awesome (محلي) --}}
  <link rel="stylesheet" href="{{ asset('vendor/fa/css/all.min.css') }}" />

  {{-- CSS العام + ستايل السايدبار --}}
  <link rel="stylesheet" href="{{ asset('css/app.css') }}" />
  <link rel="stylesheet" href="{{ asset('css/pages/sidebar.css') }}" />

  <meta name="csrf-token" content="{{ csrf_token() }}">

  {{-- مساحة لحقن CSS خاص بكل صفحة --}}
  @stack('styles')

  <title>@yield('title', 'Exam Generator')</title>
</head>
<body class="layout"><!-- مهم: حتى تُطبَّق قواعد السايدبار فقط في الصفحات الداخلية -->

  <!-- ========== Sidebar ========== -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <span>Exam Generator</span>
      <button class="toggle-sidebar" id="toggleSidebar" type="button" title="Toggle sidebar" aria-label="Toggle sidebar">
        <i class="fa-solid fa-chevron-left"></i>
      </button>
    </div>

    <div class="sidebar-content">
      <div class="user-profile">
        <div class="avatar">
          <i class="fa-solid fa-user"></i>
        </div>
        <div class="user-info">
          {{-- استبدل بالقيم الفعلية لاحقًا من Auth --}}
          <h3>Mauricio Sayegh</h3>
          <p>Professor in<br>University of Aleppo</p>
        </div>
      </div>

      <nav class="dashboard-nav">
        <a href="{{ route('dashboard') }}" class="@if(Route::is('dashboard')) active @endif">
          <i class="fa-solid fa-gauge"></i>
          <span>Dashboard</span>
        </a>
        <a href="{{ route('subjects.index') }}" class="@if(Route::is('subjects.*')) active @endif">
          <i class="fa-solid fa-book"></i>
          <span>My Subjects</span>
        </a>
        <a href="{{ route('exams.create') }}" class="@if(Route::is('exams.create')) active @endif">
          <i class="fa-solid fa-file-lines"></i>
          <span>Create Exam</span>
        </a>
        <a href="{{ route('exams.index') }}" class="@if(Route::is('exams.index')) active @endif">
          <i class="fa-solid fa-list"></i>
          <span>View Exams</span>
        </a>
        <a href="{{ route('profile.index') }}" class="@if(Route::is('profile.index')) active @endif">
          <i class="fa-solid fa-user-gear"></i>
          <span>Profile</span>
        </a>

        <form method="POST" action="{{ route('logout') }}" class="logout-wrap">
          @csrf
          <button class="logout-btn" type="submit">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
          </button>
        </form>
      </nav>
    </div>
  </aside>
  <!-- ========== /Sidebar ========== -->

  <!-- ========== Main ========== -->
  <main class="main-content">
    <div class="dashboard-header">
      <h1>@yield('title')</h1>
    </div>
    <section class="page-content">
      @yield('content')
    </section>
  </main>
  <!-- ========== /Main ========== -->

  {{-- مساحة لحقن سكربتات الصفحات --}}
  @stack('scripts')

  {{-- سكربت طي/توسيع السايدبار (محلي) --}}
  <script src="{{ asset('js/sidebar.js') }}"></script>
</body>
</html>
