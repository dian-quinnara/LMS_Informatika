@extends('layouts.app')

@section('title', 'Pengumuman')

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">PENGUMUMAN</span><h1>Pengumuman</h1><p>Buat, edit, dan hapus pengumuman yang tampil di halaman siswa.</p></div>
    </section>

    <div class="management-grid">
        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index">01</span><div><span class="section-kicker">INFORMASI SISWA</span><h2>Buat pengumuman</h2></div></div>
            <form method="POST" action="{{ route('admin.announcements.store') }}" class="form-stack" data-single-submit>
                @csrf
                @include('admin.announcements.partials.fields', ['announcement' => null])
                <button class="button button-primary" type="submit">Simpan pengumuman</button>
            </form>
        </section>
    </div>

    <section class="content-section manage-lists">
        <div class="section-heading"><div><span class="section-kicker">TERSIMPAN</span><h2>Daftar pengumuman</h2></div><span class="section-count">{{ $announcements->count() }} PENGUMUMAN</span></div>
        @forelse ($announcements as $announcement)
            <article class="session-row">
                <div>
                    <strong>{{ $announcement->title }}</strong>
                    <small>{{ $announcement->class_name ? 'Kelas '.$announcement->class_name : 'Semua kelas' }} · {{ $announcement->created_at->format('d M Y, H:i') }}{{ $announcement->creator ? ' · '.$announcement->creator->name : '' }}</small>
                    <small>{{ \Illuminate\Support\Str::limit($announcement->body, 160) }}</small>
                </div>
                <span class="{{ $announcement->is_published ? 'status-badge' : 'draft-badge' }}">{{ $announcement->is_published ? 'Tampil' : 'Draf' }}</span>
                <div class="button-group">
                    <a class="text-link" href="{{ route('admin.announcements.edit', $announcement) }}">Edit <span aria-hidden="true">&#8594;</span></a>
                    <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" onsubmit="return confirm('Hapus pengumuman ini?')">
                        @csrf
                        @method('DELETE')
                        <button class="button button-quiet" type="submit">Hapus</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="empty-state"><strong>Belum ada pengumuman.</strong><span>Pengumuman yang tampil akan muncul di halaman presensi siswa.</span></div>
        @endforelse
    </section>
@endsection
