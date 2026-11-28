<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $siteName = \App\Models\Setting::value('site_name') ?: 'Ruang Belajar';
        $schoolName = \App\Models\Setting::value('school_name');
    @endphp
    <title>@yield('title', $siteName) | {{ $siteName }}</title>
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
                @if ($siteName === 'Ruang Belajar')
                    <span>ruang<span class="brand-light">belajar</span></span>
                @else
                    <span>{{ $siteName }}</span>
                @endif
            </a>
            <p class="nav-label">MENU UTAMA</p>
            <nav class="primary-nav" aria-label="Navigasi utama">
                @if (auth()->user()->role === 'admin')
                    @php
                        $adminMenu = [
                            ['Dashboard Admin', 'dashboard', ['dashboard']],
                            ['Kelola Siswa', 'admin.students.index', ['admin.students.*', 'admin.student-classes.*']],
                            ['Kelola Kelas', 'admin.classes.index', ['admin.classes.*']],
                            ['Kelola Presensi', 'admin.attendance.index', ['admin.attendance.*', 'admin.attendance-sessions.*', 'admin.attendance-report*', 'admin.attendance-tokens.*']],
                            ['Kelola Materi & Simulasi', 'admin.content.index', ['admin.content.*', 'admin.manage', 'admin.subjects.*', 'admin.materials.*', 'admin.simulations.*']],
                            ['Kelola Soal & Kuis', 'admin.quizzes.index', ['admin.quizzes.*', 'admin.question-bank.*', 'admin.questions.*']],
                            ['Hasil & Analisis Belajar', 'admin.results.index', ['admin.results.*']],
                            ['Pengumuman', 'admin.announcements.index', ['admin.announcements.*']],
                            ['Pengaturan Sistem', 'admin.settings.index', ['admin.settings.*']],
                        ];
                    @endphp
                    @foreach ($adminMenu as [$menuLabel, $menuRoute, $menuPatterns])
                        <a class="nav-link {{ request()->routeIs(...$menuPatterns) ? 'active' : '' }}" href="{{ route($menuRoute) }}"><span class="nav-symbol">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span> {{ $menuLabel }}</a>
                    @endforeach
                @else
                    @php
                        $unreadAnnouncements = \App\Models\Announcement::unreadCountFor(auth()->user());
                        $studentMenu = [
                            ['Beranda', 'dashboard', ['dashboard']],
                            ['Presensi', 'student.attendance', ['student.attendance']],
                            ['Materi & Simulasi', 'materi.index', ['materi.index', 'materi.computational-thinking', 'materi.searching', 'materi.searching.sequential', 'materi.searching.binary', 'materi.searching.simulator', 'materi.sorting', 'materi.pseudocode', 'materi.struktur-data', 'materi.simulation', 'materials.*']],
                            ['Latihan & Kuis', 'student.practice', ['student.practice', 'materi.practice*', 'materi.searching.quiz*', 'materi.searching.hots-quiz*', 'materi.searching.analysis', 'simulations.*', 'attempts.*']],
                            ['Progres Belajar', 'student.progress', ['student.progress']],
                            ['Pengumuman', 'student.announcements', ['student.announcements']],
                            ['Profil Saya', 'student.profile', ['student.profile', 'password.edit']],
                        ];
                    @endphp
                    @foreach ($studentMenu as [$menuLabel, $menuRoute, $menuPatterns])
                        <a class="nav-link {{ request()->routeIs(...$menuPatterns) ? 'active' : '' }}" href="{{ route($menuRoute) }}"><span class="nav-symbol">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span> {{ $menuLabel }}@if ($menuRoute === 'student.announcements' && $unreadAnnouncements > 0)<span class="unread-mark" title="{{ $unreadAnnouncements }} pengumuman belum dibaca" aria-label="{{ $unreadAnnouncements }} pengumuman belum dibaca">*</span>@endif</a>
                    @endforeach
                @endif
            </nav>
            <div class="sidebar-bottom">
                <div class="profile-chip">
                    <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    <span class="profile-copy"><strong>{{ auth()->user()->name }}</strong><small>{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Siswa'.(auth()->user()->class_name ? ' · '.auth()->user()->class_name : '') }}</small></span>
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
                <div><span class="eyebrow">{{ $schoolName !== '' ? mb_strtoupper($schoolName) : 'PORTAL PEMBELAJARAN' }}</span><span class="topbar-date">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span></div>
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