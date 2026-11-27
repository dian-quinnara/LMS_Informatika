@extends('layouts.app')

@section('title', 'Kelola kelas')

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">KELOLA KELAS · XE1–XE9</span><h1>Kelola kelas</h1><p>Lihat jumlah dan anggota setiap kelas, lalu pindahkan siswa ke kelas lain bila diperlukan.</p></div>
        <a class="button button-quiet" href="{{ route('admin.students.index') }}">Kelola siswa</a>
    </section>

    <section class="stat-grid" aria-label="Ringkasan kelas">
        <article class="stat-tile stat-green"><span>JUMLAH KELAS</span><strong>{{ $classes->count() }}</strong><small>kelas X aktif</small></article>
        <article class="stat-tile stat-yellow"><span>SISWA BERKELAS</span><strong>{{ $classes->sum('total') }}</strong><small>sudah masuk kelas</small></article>
        <article class="stat-tile stat-coral"><span>BELUM ADA KELAS</span><strong>{{ $unassigned->count() }}</strong><small>perlu ditempatkan</small></article>
        <article class="stat-tile stat-blue"><span>RATA-RATA</span><strong>{{ $classes->count() ? round($classes->sum('total') / $classes->count(), 1) : 0 }}</strong><small>siswa per kelas</small></article>
    </section>

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">DAFTAR KELAS</span><h2>Jumlah siswa per kelas</h2></div></div>
        @foreach ($classes as $class)
            <article class="session-row">
                <div><strong>Kelas {{ $class['name'] }}</strong><small>{{ $class['total'] }} siswa · {{ $class['active'] }} akun aktif</small></div>
                <span class="{{ $class['total'] > 0 ? 'status-badge' : 'draft-badge' }}">{{ $class['total'] }} siswa</span>
                <a class="text-link" href="{{ route('admin.classes.show', $class['name']) }}">Lihat anggota <span aria-hidden="true">&#8594;</span></a>
            </article>
        @endforeach
    </section>

    <section class="content-section manage-lists">
        <div class="section-heading"><div><span class="section-kicker">PERLU DITEMPATKAN</span><h2>Siswa tanpa kelas</h2></div><span class="section-count">{{ $unassigned->count() }} SISWA</span></div>
        @if ($unassigned->isEmpty())
            <div class="empty-state"><strong>Semua siswa sudah memiliki kelas.</strong><span>Siswa baru dari impor atau tambah manual akan muncul di sini bila kelasnya kosong.</span></div>
        @else
            @include('admin.classes.partials.move-form', ['members' => $unassigned, 'currentClass' => null, 'classNames' => $classes->pluck('name')])
        @endif
    </section>
@endsection
