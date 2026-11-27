@extends('layouts.app')

@section('title', 'Kelola presensi')

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">KELOLA PRESENSI · KELAS X</span><h1>Kelola presensi</h1><p>Buat sesi presensi harian per kelas, bagikan token, lalu pantau siswa yang sudah dan belum hadir.</p></div>
        <a class="button button-quiet" href="{{ route('admin.attendance-report') }}">Rekap presensi</a>
    </section>

    @if (session('attendance_session'))
        @php($newSession = session('attendance_session'))
        <section class="token-reveal" role="status">
            <div>
                <span class="section-kicker">TOKEN PRESENSI BARU</span>
                <p>Kelas {{ $newSession['class_name'] }}@if (! empty($newSession['title'])) · {{ $newSession['title'] }}@endif · {{ $newSession['starts_at'] }} – {{ $newSession['expires_at'] }}</p>
                <small>Status sesi: {{ $newSession['status'] }}@if (! empty($newSession['id'])) · <a class="text-link" href="{{ route('admin.attendance-sessions.show', $newSession['id']) }}">Lihat detail sesi</a>@endif</small>
            </div>
            <div class="button-group">
                <code>{{ $newSession['token'] }}</code>
                <button class="button button-quiet" type="button" data-copy-token="{{ $newSession['token'] }}">Salin token</button>
            </div>
        </section>
    @endif

    @include('admin.partials.class-attendance')
@endsection
