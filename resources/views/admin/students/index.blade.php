@extends('layouts.app')

@section('title', 'Kelola siswa')

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">KELOLA SISWA</span><h1>Kelola siswa</h1><p>Tambah, edit, dan nonaktifkan akun siswa. Atur NIS, kelas, dan reset kata sandi.</p></div>
        <div class="button-group">
            <a class="button button-quiet" href="{{ route('admin.students.import') }}">Impor dari Excel</a>
            <a class="button button-primary" href="{{ route('admin.students.create') }}">Tambah siswa <span aria-hidden="true">&#8594;</span></a>
        </div>
    </section>

    @include('admin.partials.student-credential')

    <section class="stat-grid" aria-label="Ringkasan siswa">
        <article class="stat-tile stat-green"><span>TOTAL SISWA</span><strong>{{ $students->total() }}</strong><small>sesuai filter saat ini</small></article>
        <article class="stat-tile stat-yellow"><span>BELUM ADA KELAS</span><strong>{{ $withoutClass }}</strong><small>perlu dimasukkan ke XE1–XE9</small></article>
        <article class="stat-tile stat-coral"><span>NONAKTIF</span><strong>{{ $inactiveCount }}</strong><small>tidak dapat login</small></article>
        <article class="stat-tile stat-blue"><span>KATA SANDI DEFAULT</span><strong>{{ $defaultPasswordSet ? 'Aktif' : 'Acak' }}</strong><small>{{ $defaultPasswordSet ? 'dari .env sekolah' : 'dibuat acak per siswa' }}</small></article>
    </section>

    <section class="content-section manage-lists">
        <div class="section-heading"><div><span class="section-kicker">DAFTAR SISWA</span><h2>Siswa berdasarkan kelas</h2></div><span class="section-count">{{ $students->total() }} SISWA</span></div>
        <form method="GET" action="{{ route('admin.students.index') }}" class="form-panel form-stack">
            <div class="form-inline">
                <div>
                    <label class="field-label" for="student-filter-class">Kelas</label>
                    <select id="student-filter-class" name="class">
                        <option value="">Semua kelas</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class }}" @selected($filters['class'] === $class)>{{ $class }} · {{ $classCounts[$class] ?? 0 }} siswa</option>
                        @endforeach
                        <option value="none" @selected($filters['class'] === 'none')>Belum ada kelas</option>
                    </select>
                </div>
                <div>
                    <label class="field-label" for="student-filter-status">Status akun</label>
                    <select id="student-filter-status" name="status">
                        <option value="">Semua status</option>
                        <option value="active" @selected($filters['status'] === 'active')>Aktif</option>
                        <option value="inactive" @selected($filters['status'] === 'inactive')>Nonaktif</option>
                    </select>
                </div>
            </div>
            <label class="field-label" for="student-filter-q">Cari nama, NIS, atau email</label>
            <input id="student-filter-q" name="q" value="{{ $filters['q'] }}" maxlength="100" placeholder="Contoh: 8001 atau nama siswa">
            <div class="button-group">
                <button class="button button-primary" type="submit">Terapkan filter</button>
                <a class="button button-quiet" href="{{ route('admin.students.index') }}">Atur ulang</a>
            </div>
        </form>

        @forelse ($students as $student)
            <article class="session-row">
                <div>
                    <strong>{{ $student->name }}</strong>
                    <small>NIS {{ $student->nis ?? '—' }} · {{ $student->email }} · {{ $student->class_name ? 'Kelas '.$student->class_name : 'belum ada kelas' }}{{ $student->must_change_password ? ' · belum mengganti kata sandi awal' : '' }}</small>
                </div>
                <span class="{{ $student->is_active ? 'status-badge' : 'draft-badge' }}">{{ $student->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                <div class="button-group">
                    <a class="text-link" href="{{ route('admin.students.edit', $student) }}">Edit <span aria-hidden="true">&#8594;</span></a>
                    <form method="POST" action="{{ route('admin.students.status', $student) }}">
                        @csrf
                        @method('PATCH')
                        <button class="button button-quiet" type="submit">{{ $student->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="empty-state"><strong>Tidak ada siswa yang cocok.</strong><span>Ubah filter, tambah siswa baru, atau impor data dari Excel.</span></div>
        @endforelse

        @if ($students->hasPages())
            <div class="button-group">
                @if ($students->previousPageUrl())<a class="button button-quiet" href="{{ $students->previousPageUrl() }}">&#8592; Sebelumnya</a>@endif
                <span class="section-count">HALAMAN {{ $students->currentPage() }} / {{ $students->lastPage() }}</span>
                @if ($students->nextPageUrl())<a class="button button-quiet" href="{{ $students->nextPageUrl() }}">Berikutnya &#8594;</a>@endif
            </div>
        @endif
    </section>
@endsection
