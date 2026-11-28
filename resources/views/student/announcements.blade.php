@extends('layouts.app')

@section('title', 'Pengumuman')

@section('content')
    @include('student.partials.heading', ['kicker' => 'PENGUMUMAN', 'title' => 'Pengumuman', 'description' => 'Informasi dari admin: jadwal presensi, informasi kuis, materi baru, dan kegiatan pembelajaran.'])

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">INFORMASI DARI SEKOLAH</span><h2>Semua pengumuman</h2></div><span class="section-count">{{ $announcements->count() }} PENGUMUMAN</span></div>
        @forelse ($announcements as $announcement)
            @include('student.partials.announcement-row')
        @empty
            <div class="empty-state"><strong>Belum ada pengumuman.</strong><span>Pengumuman untuk semua kelas atau kelasmu akan tampil di sini.</span></div>
        @endforelse
    </section>
@endsection
