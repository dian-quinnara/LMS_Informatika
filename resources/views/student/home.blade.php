@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
    @include('student.partials.heading', ['kicker' => 'BERANDA', 'title' => 'Halo, '.$user->name, 'description' => 'Ringkasan presensi, materi, latihan, dan progres belajarmu hari ini.'])

    <section class="stat-grid" aria-label="Ringkasan belajar">
        <article class="stat-tile stat-green"><span>PRESENSI HARI INI</span><strong>{{ $attendedToday > 0 ? 'Hadir' : 'Belum' }}</strong><small>{{ $attendedToday > 0 ? $attendedToday.' sesi tercatat hari ini' : 'belum ada presensi hari ini' }}</small></article>
        <article class="stat-tile stat-yellow"><span>MATERI DIPELAJARI</span><strong>{{ $summary['studied'] }}/{{ $summary['topics'] }}</strong><small>topik materi interaktif</small></article>
        <article class="stat-tile stat-coral"><span>SIMULASI DICOBA</span><strong>{{ $summary['simulations'] }}/{{ $summary['simulation_total'] }}</strong><small>simulasi interaktif</small></article>
        <article class="stat-tile stat-blue"><span>RATA-RATA NILAI</span><strong>{{ $summary['average'] ?? '–' }}</strong><small>{{ $summary['review'] > 0 ? $summary['review'].' materi perlu dipelajari ulang' : 'latihan dan kuis' }}</small></article>
    </section>

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">STATUS PRESENSI HARI INI</span><h2>Sesi presensi</h2></div><a class="text-link" href="{{ route('student.attendance') }}">Buka presensi <span aria-hidden="true">&#8594;</span></a></div>
        @forelse ($todaySessions as $session)
            <article class="session-row">
                <div><strong>{{ $session->displayName() }}</strong><small>{{ $session->starts_at->format('H:i') }}–{{ $session->expires_at->format('H:i') }}</small></div>
                @if (in_array($session->id, $attendedIds))
                    <span class="status-badge">Hadir</span>
                @elseif ($session->starts_at->isFuture())
                    <span class="draft-badge">Akan dibuka</span>
                @else
                    <span class="active-badge">Belum presensi</span>
                @endif
            </article>
        @empty
            <div class="empty-state"><strong>Tidak ada sesi presensi hari ini.</strong><span>Sesi dari admin untuk kelasmu akan muncul di sini.</span></div>
        @endforelse
    </section>

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">LANJUTKAN BELAJAR</span><h2>Materi terakhir dibuka</h2></div><a class="text-link" href="{{ route('materi.index') }}">Semua materi <span aria-hidden="true">&#8594;</span></a></div>
        @if ($lastMaterial)
            <a class="list-row" href="{{ $lastMaterial->url ?? route('materi.index') }}">
                <div><strong>{{ $lastMaterial->label }}</strong><small>Dibuka {{ $lastMaterial->last_activity_at?->locale('id')->diffForHumans() }} · {{ $lastMaterial->visits }} kali</small></div>
                <span class="text-link">Lanjutkan <span aria-hidden="true">&#8594;</span></span>
            </a>
        @else
            <div class="empty-state"><strong>Belum ada materi yang dibuka.</strong><span>Mulai dari menu Materi &amp; Simulasi.</span></div>
        @endif
    </section>

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">BELUM DIKERJAKAN</span><h2>Latihan &amp; kuis</h2></div><a class="text-link" href="{{ route('student.practice') }}">Latihan &amp; kuis <span aria-hidden="true">&#8594;</span></a></div>
        @foreach ($pendingQuizzes as $quiz)
            <a class="list-row" href="{{ route('simulations.show', $quiz) }}">
                <div><strong>{{ $quiz->title }}</strong><small>Kuis dari guru · {{ $quiz->questions_count }} soal · {{ $quiz->duration_minutes }} menit</small></div>
                <span class="text-link">Kerjakan <span aria-hidden="true">&#8594;</span></span>
            </a>
        @endforeach
        @foreach ($pendingPractices->take(4) as $practice)
            <a class="list-row" href="{{ $practice['url'] }}">
                <div><strong>{{ $practice['label'] }}</strong><small>{{ $practice['topic'] }} · {{ $practice['count'] }} soal {{ implode(' & ', $practice['categories']) }}</small></div>
                <span class="text-link">Kerjakan <span aria-hidden="true">&#8594;</span></span>
            </a>
        @endforeach
        @if ($pendingQuizzes->isEmpty() && $pendingPractices->isEmpty())
            <div class="empty-state"><strong>Semua latihan sudah dikerjakan.</strong><span>Lihat nilaimu di menu Progres Belajar.</span></div>
        @endif
    </section>

    @if ($announcements->isNotEmpty())
        <section class="content-section">
            <div class="section-heading"><div><span class="section-kicker">INFORMASI DARI SEKOLAH</span><h2>Pengumuman</h2></div><a class="text-link" href="{{ route('student.announcements') }}">Semua pengumuman <span aria-hidden="true">&#8594;</span></a></div>
            @foreach ($announcements as $announcement)
                @include('student.partials.announcement-row')
            @endforeach
        </section>
    @endif
@endsection
