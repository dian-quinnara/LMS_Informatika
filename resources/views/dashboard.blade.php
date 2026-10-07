@extends('layouts.app')

@section('title', $user->role === 'admin' ? 'Ringkasan' : match ($studentSection) {
    'materials' => 'Materi belajar Informatika',
    'simulations' => 'Simulasi',
    default => 'Presensi siswa',
})

@section('content')
    @if ($user->role === 'admin')
        <section class="page-heading">
            <div><span class="section-kicker">PANEL ADMIN</span><h1>Selamat datang, {{ $user->name }}</h1><p>Aktivitas ruang belajar Anda, dirangkum di satu tempat.</p></div>
            <a class="button button-primary" href="{{ route('admin.manage') }}">Kelola konten <span aria-hidden="true">&#8594;</span></a>
        </section>
        @if (session('attendance_session'))
            @php($newSession = session('attendance_session'))
            <section class="token-reveal" role="status">
                <div>
                    <span class="section-kicker">TOKEN PRESENSI BARU</span>
                    <p>Kelas {{ $newSession['class_name'] }} · {{ $newSession['starts_at'] }}–{{ $newSession['expires_at'] }}</p>
                    <small>Status sesi: {{ $newSession['status'] }}</small>
                </div>
                <code>{{ $newSession['token'] }}</code>
            </section>
        @endif
        <section class="stat-grid" aria-label="Ringkasan data">
            <article class="stat-tile stat-green"><span>SISWA TERDAFTAR</span><strong>{{ $stats['students'] }}</strong><small>akun aktif di ruang belajar</small></article>
            <article class="stat-tile stat-yellow"><span>MATA PELAJARAN</span><strong>{{ $stats['subjects'] }}</strong><small>kelas yang tersedia</small></article>
            <article class="stat-tile stat-coral"><span>MATERI BELAJAR</span><strong>{{ $stats['materials'] }}</strong><small>sumber belajar diterbitkan</small></article>
            <article class="stat-tile stat-blue"><span>SIMULASI</span><strong>{{ $stats['simulations'] }}</strong><small>latihan tersimpan</small></article>
        </section>
        <section class="content-section">
            <div class="section-heading"><div><span class="section-kicker">RUANG KELAS</span><h2>Mata pelajaran</h2></div><a class="text-link" href="{{ route('admin.manage') }}">Atur konten <span aria-hidden="true">&#8594;</span></a></div>
            @forelse ($subjects as $subject)
                <div class="list-row"><div><strong>{{ $subject->name }}</strong><small>{{ $subject->code }} · {{ $subject->materials_count }} materi · {{ $subject->simulations_count }} simulasi</small></div><span class="row-mark">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span></div>
            @empty
                <div class="empty-state"><strong>Belum ada mata pelajaran.</strong><span>Tambahkan kelas pertama untuk mulai menyusun ruang belajar.</span></div>
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
        </section>
        @if ($studentSection === 'attendance')
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
                    <small>10 karakter · waktu aplikasi {{ config('app.timezone') }}</small>
                </form>
            </div>
            <div class="attendance-sessions">
                <div class="section-heading"><div><span class="section-kicker">JADWAL TERSEDIA</span><h2>Sesi presensi</h2></div></div>
                @forelse ($attendanceTokens as $attendanceToken)
                    <article class="session-row">
                        <div><strong>{{ $attendanceToken->subject->name }}</strong><small>{{ $attendanceToken->starts_at->format('d M, H:i') }}–{{ $attendanceToken->expires_at->format('H:i') }}</small></div>
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
                <article class="list-row"><div><strong>{{ $attendance->subject->name }}</strong><small>{{ $attendance->attended_on->format('d M Y') }}@if ($attendance->attendanceToken) · sesi {{ $attendance->attendanceToken->starts_at->format('H:i') }}@endif</small></div><span class="status-badge">Hadir</span></article>
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