@extends('layouts.app')

@section('title', $user->role === 'admin' ? 'Ringkasan' : match ($studentSection) {
    'materials' => 'Materi belajar Informatika',
    'simulations' => 'Simulasi',
    default => 'Presensi siswa',
})

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
    @else
        <section class="page-heading">
            <div>
                <span class="section-kicker">PORTAL SISWA · {{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
                @if ($studentSection === 'attendance')
                    <h1>Presensi siswa</h1><p>Masukkan token yang diberikan admin untuk mencatat kehadiran di jam pelajaran.</p>
                @elseif ($studentSection === 'materials')
                    <h1>Materi belajar Informatika</h1><p>Pilih bahan belajar dan lanjutkan dari bagian yang ingin kamu pahami.</p>
                @else
                    <h1>Simulasi</h1><p>Latihan yang tersedia untuk menguji pemahamanmu.</p>
                @endif
            </div>
            <div class="info-stat" aria-label="Identitas siswa">
                <span class="info-label">{{ $user->name }}{{ $user->nis ? ' · NIS '.$user->nis : '' }}</span>
                <strong>{{ $user->class_name ? 'Kelas '.$user->class_name : 'Kelas belum diatur' }}</strong>
            </div>
        </section>
        @if ($announcements->isNotEmpty())
            <section class="content-section">
                <div class="section-heading"><div><span class="section-kicker">INFORMASI DARI SEKOLAH</span><h2>Pengumuman</h2></div></div>
                @foreach ($announcements as $announcement)
                    <article class="list-row"><div><strong>{{ $announcement->title }}</strong><small>{{ $announcement->created_at->format('d M Y') }}{{ $announcement->class_name ? ' · Kelas '.$announcement->class_name : ' · Semua kelas' }}</small><p>{!! nl2br(e($announcement->body)) !!}</p></div></article>
                @endforeach
            </section>
        @endif
        @if ($studentSection === 'attendance')
        @if ($user->class_name === null)
            <div class="warning-message" role="alert">
                <strong>Kelasmu belum diatur.</strong>
                <p>Hubungi admin agar akunmu dimasukkan ke kelas XE1–XE9 sebelum melakukan presensi. Kelas tidak dapat dipilih sendiri oleh siswa.</p>
            </div>
        @endif
        <section class="attendance-entry">
            <div class="token-entry-panel">
                <span class="section-kicker">CATAT KEHADIRAN</span>
                <h2>Masukkan token kelas</h2>
                <p>Token hanya berlaku pada mata pelajaran dan rentang waktu yang ditentukan admin.</p>
                <form method="POST" action="{{ route('attendances.store') }}" class="token-entry-form">
                    @csrf
                    <label class="field-label" for="attendance-token">Token presensi</label>
                    <div class="token-input-row">
                        <input id="attendance-token" name="token" type="text" value="{{ old('token') }}" minlength="10" maxlength="10" pattern="[A-Za-z0-9]{10}" inputmode="text" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="CONTOH1234" required>
                        <button class="button button-primary" type="submit">Kirim presensi <span aria-hidden="true">&#8594;</span></button>
                    </div>
                    <small>{{ $user->class_name ? 'Kelas '.$user->class_name.' · ' : '' }}10 karakter · waktu aplikasi {{ config('app.timezone') }}</small>
                </form>
            </div>
            <div class="attendance-sessions">
                <div class="section-heading"><div><span class="section-kicker">JADWAL TERSEDIA</span><h2>Sesi presensi</h2></div></div>
                @forelse ($attendanceTokens as $attendanceToken)
                    <article class="session-row">
                        <div><strong>{{ $attendanceToken->displayName() }}</strong><small>{{ $attendanceToken->starts_at->format('d M Y, H:i') }}–{{ $attendanceToken->expires_at->format('d M Y, H:i') }}</small></div>
                        @if (in_array($attendanceToken->id, $attendedTokenIds))
                            <span class="status-badge">Sudah hadir</span>
                        @elseif ($attendanceToken->starts_at->isFuture())
                            <span class="draft-badge">Akan dibuka</span>
                        @else
                            <span class="active-badge">Berlangsung</span>
                        @endif
                    </article>
                @empty
                    <div class="empty-state"><strong>Belum ada sesi presensi.</strong><span>Sesi aktif dan jadwal berikutnya akan tampil di sini.</span></div>
                @endforelse
            </div>
        </section>
        <section class="content-section">
            <div class="section-heading"><div><span class="section-kicker">RIWAYAT KEHADIRAN</span><h2>Presensi terakhir</h2></div></div>
            @forelse ($attendanceHistory as $attendance)
                <article class="list-row"><div><strong>{{ $attendance->attendanceToken?->displayName() ?? $attendance->subject?->name ?? 'Sesi presensi' }}</strong><small>{{ $attendance->attended_on->format('d M Y') }}@if ($attendance->attendanceToken) · sesi {{ $attendance->attendanceToken->starts_at->format('H:i') }}@endif</small></div><span class="status-badge">Hadir</span></article>
            @empty
                <div class="empty-state"><strong>Riwayat presensi masih kosong.</strong><span>Presensi yang berhasil akan tercatat di sini.</span></div>
            @endforelse
        </section>
        @endif
        @if ($studentSection === 'simulations')
        <section class="content-section" id="simulations">
            <div class="section-heading"><div><span class="section-kicker">UJI PEMAHAMAN</span><h2>Simulasi aktif</h2></div><span class="section-count">{{ $simulations->count() }} LATIHAN</span></div>
            <div class="simulation-grid">
                @forelse ($simulations as $simulation)
                    <article class="simulation-item">
                        <span class="item-tag">{{ $simulation->subject->code }}</span>
                        <h3>{{ $simulation->title }}</h3>
                        <p>{{ $simulation->description ?: $simulation->subject->name }}</p>
                        <div class="item-meta"><span>{{ $simulation->questions_count }} soal</span><span>{{ $simulation->duration_minutes }} menit</span></div>
                        <a class="text-link" href="{{ route('simulations.show', $simulation) }}">Lihat simulasi <span aria-hidden="true">&#8594;</span></a>
                    </article>
                @empty
                    <div class="empty-state"><strong>Belum ada simulasi aktif.</strong><span>Simulasi yang sudah dipublikasikan akan muncul di sini.</span></div>
                @endforelse
            </div>
        </section>
        @if ($attempts->isNotEmpty())
            <section class="content-section">
                <div class="section-heading"><div><span class="section-kicker">JEJAK BELAJAR</span><h2>Simulasi terakhir</h2></div></div>
                @foreach ($attempts as $attempt)
                    <div class="list-row"><div><strong>{{ $attempt->simulation->title }}</strong><small>{{ $attempt->simulation->subject->name }} · {{ $attempt->created_at->format('d/m/Y') }}</small></div><span class="score-badge">{{ number_format((float) $attempt->score, 0) }}%</span></div>
                @endforeach
            </section>
        @endif
        @endif
        @if ($studentSection === 'materials')
        <section class="content-section" id="materials">
            <div class="section-heading"><div><span class="section-kicker">BACA DAN PELAJARI</span><h2>Materi belajar Informatika</h2></div></div>
            @foreach ($learningTopics as $slug => $topic)
                <a class="material-row" href="{{ route('materi.'.$slug) }}">
                    <div><span class="item-tag">MATERI {{ $topic['number'] }}</span><h3>{{ $topic['title'] }}</h3><p>{{ $topic['description'] }}</p></div>
                    <span class="text-link">Mulai Belajar <span aria-hidden="true">&#8594;</span></span>
                </a>
            @endforeach
        </section>
        @endif
    @endif
@endsection