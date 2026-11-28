@extends('layouts.app')

@section('title', 'Ringkasan')

@section('content')
    @if ($user->role === 'admin')
        <section class="page-heading">
            <div><span class="section-kicker">DASHBOARD ADMIN</span><h1>Selamat datang, {{ $user->name }}</h1><p>Ringkasan siswa, kelas, presensi, materi, dan hasil kuis hari ini.</p></div>
            <a class="button button-primary" href="{{ route('admin.attendance.index') }}">Buat sesi presensi <span aria-hidden="true">&#8594;</span></a>
        </section>
        <section class="stat-grid" aria-label="Ringkasan data">
            <article class="stat-tile stat-green"><span>JUMLAH SISWA</span><strong>{{ $stats['students'] }}</strong><small>akun siswa terdaftar</small></article>
            <article class="stat-tile stat-yellow"><span>JUMLAH KELAS</span><strong>{{ $stats['classes'] }}</strong><small>kelas XE1–XE9</small></article>
            <article class="stat-tile stat-coral"><span>PRESENSI HARI INI</span><strong>{{ $stats['attendance_today'] }}</strong><small>kehadiran tercatat</small></article>
            <article class="stat-tile stat-blue"><span>SESI AKTIF</span><strong>{{ $stats['active_sessions'] }}</strong><small>sesi presensi berlangsung</small></article>
            <article class="stat-tile stat-green"><span>MATERI TERSEDIA</span><strong>{{ $stats['materials'] }}</strong><small>materi terbit untuk siswa</small></article>
            <article class="stat-tile stat-yellow"><span>RATA-RATA KUIS</span><strong>{{ $quizSummary['average'] }}</strong><small>{{ $quizSummary['attempts'] }} pengerjaan · {{ $quizSummary['passed'] }} tuntas (KKM {{ $quizSummary['passing_score'] }})</small></article>
        </section>

        <section class="content-section">
            <div class="section-heading"><div><span class="section-kicker">BERLANGSUNG SEKARANG</span><h2>Sesi presensi aktif</h2></div><a class="text-link" href="{{ route('admin.attendance.index') }}">Kelola presensi <span aria-hidden="true">&#8594;</span></a></div>
            @forelse ($activeSessions as $session)
                <article class="session-row">
                    <div><strong>{{ $session->class_name }}@if ($session->title) · {{ $session->title }}@endif</strong><small>{{ $session->starts_at->format('H:i') }} – {{ $session->expires_at->format('H:i') }} · {{ $session->attendances_count }} siswa hadir</small></div>
                    @include('admin.partials.session-status', ['session' => $session])
                    <a class="text-link" href="{{ route('admin.attendance-sessions.show', $session) }}">Detail <span aria-hidden="true">&#8594;</span></a>
                </article>
            @empty
                <div class="empty-state"><strong>Tidak ada sesi presensi yang sedang berlangsung.</strong><span>Buat sesi baru dari menu Kelola Presensi.</span></div>
            @endforelse
        </section>

        <section class="content-section">
            <div class="section-heading"><div><span class="section-kicker">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span><h2>Presensi hari ini per kelas</h2></div><a class="text-link" href="{{ route('admin.attendance-report') }}">Rekap presensi <span aria-hidden="true">&#8594;</span></a></div>
            @foreach ($todayByClass as $row)
                <article class="session-row">
                    <div><strong>Kelas {{ $row['class'] }}</strong><small>{{ $row['students'] }} siswa terdaftar</small></div>
                    <span class="{{ $row['students'] > 0 && $row['present'] >= $row['students'] ? 'status-badge' : 'draft-badge' }}">{{ $row['present'] }}/{{ $row['students'] }} hadir</span>
                    <a class="text-link" href="{{ route('admin.classes.show', $row['class']) }}">Anggota <span aria-hidden="true">&#8594;</span></a>
                </article>
            @endforeach
        </section>

        <section class="content-section">
            <div class="section-heading"><div><span class="section-kicker">HASIL KUIS</span><h2>Pengerjaan terbaru</h2></div><a class="text-link" href="{{ route('admin.results.index') }}">Hasil &amp; analisis <span aria-hidden="true">&#8594;</span></a></div>
            @forelse ($latestAttempts as $attempt)
                <div class="list-row"><div><strong>{{ $attempt->user?->name ?? 'Siswa' }}</strong><small>{{ $attempt->simulation?->title }} · {{ $attempt->user?->class_name ?? 'tanpa kelas' }} · {{ $attempt->submitted_at?->format('d/m/Y H:i') }}</small></div><span class="score-badge">{{ number_format((float) $attempt->score, 0) }}%</span></div>
            @empty
                <div class="empty-state"><strong>Belum ada kuis yang dikerjakan.</strong><span>Nilai siswa akan tampil di sini setelah kuis diselesaikan.</span></div>
            @endforelse
        </section>
    @endif
@endsection