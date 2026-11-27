@extends('layouts.app')

@section('title', 'Kelas '.$className)

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">KELOLA KELAS · ANGGOTA</span><h1>Kelas {{ $className }}</h1><p>{{ $members->count() }} siswa terdaftar. Centang siswa untuk memindahkannya ke kelas lain.</p></div>
        <div class="button-group">
            <a class="button button-quiet" href="{{ route('admin.attendance-report', ['class_name' => $className]) }}">Rekap presensi</a>
            <a class="button button-quiet" href="{{ route('admin.classes.index') }}">Kembali ke Kelola Kelas</a>
        </div>
    </section>

    <section class="content-section manage-lists">
        <div class="section-heading"><div><span class="section-kicker">ANGGOTA KELAS</span><h2>Daftar siswa</h2></div><span class="section-count">{{ $members->count() }} SISWA</span></div>
        @if ($members->isEmpty())
            <div class="empty-state"><strong>Belum ada siswa di kelas {{ $className }}.</strong><span>Pindahkan siswa dari halaman Kelola Kelas atau impor data siswa.</span></div>
        @else
            @include('admin.classes.partials.move-form', ['currentClass' => $className, 'classNames' => $classes])
        @endif
    </section>
@endsection
