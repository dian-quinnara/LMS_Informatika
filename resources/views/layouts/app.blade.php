<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Ruang Belajar') | Ruang Belajar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Newsreader:opsz,wght@6..72,500;6..72,600&display=swap" rel="stylesheet">
    <link href="{{ asset('css/lms.css') }}" rel="stylesheet">
</head>
<body>
<div class="app-shell @guest guest-shell @endguest">
    @auth
        <aside class="sidebar">
            <a class="brand" href="{{ route('dashboard') }}">
                <span class="brand-mark">R</span>
                <span>ruang<span class="brand-light">belajar</span></span>
            </a>
            <p class="nav-label">MENU UTAMA</p>
            <nav class="primary-nav" aria-label="Navigasi utama">
                @if (auth()->user()->role === 'admin')
                    <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span class="nav-symbol">01</span> Kelola Kelas</a>
                    <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.manage') }}"><span class="nav-symbol">02</span> Kelola Pembelajaran</a>
                @else
                    <a class="nav-link {{ request()->routeIs('dashboard') && request('section', 'attendance') === 'attendance' ? 'active' : '' }}" href="{{ route('dashboard', ['section' => 'attendance']) }}"><span class="nav-symbol">01</span> Presensi siswa</a>
                    <a class="nav-link {{ request()->routeIs('dashboard') && request('section') === 'materials' ? 'active' : '' }}" href="{{ route('dashboard', ['section' => 'materials']) }}"><span class="nav-symbol">02</span> Materi belajar Informatika</a>
                @endif
            </nav>
            <div class="sidebar-bottom">
                <div class="profile-chip">
                    <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="profile-copy"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Siswa' }}</small></span>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="signout-button" type="submit">Keluar akun</button>
                </form>
            </div>
        </aside>
    @endauth

    <main class="main-content">
        @auth
            <header class="topbar">
                <div><span class="eyebrow">PORTAL PEMBELAJARAN</span><span class="topbar-date">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span></div>
                <span class="role-pill">{{ auth()->user()->role === 'admin' ? 'ADMIN' : 'SISWA' }}</span>
            </header>
        @endauth

        @if (session('status'))
            <div class="flash-message" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="error-message" role="alert">
                <strong>Periksa kembali isian Anda.</strong>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @yield('content')
    </main>
</div>
</body>
</html>