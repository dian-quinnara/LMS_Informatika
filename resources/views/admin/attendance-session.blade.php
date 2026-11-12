@extends('layouts.app')

@section('title', 'Detail sesi presensi')

@section('content')
    <section class="page-heading">
        <div>
            <span class="section-kicker">KELOLA KELAS · {{ $session->class_name }}</span>
            <h1>{{ $session->title ?: 'Sesi presensi '.$session->class_name }}</h1>
            <p>{{ $session->starts_at->locale('id')->translatedFormat('l, d F Y, H:i') }} – {{ $session->expires_at->format('d M Y, H:i') }} · {{ config('app.timezone') }}</p>
        </div>
        <a class="button button-quiet" href="{{ route('dashboard') }}#kelas-x">Kembali ke Kelola Kelas</a>
    </section>

    <section class="token-reveal">
        <div>
            <span class="section-kicker">TOKEN PRESENSI</span>
            <p>Kelas {{ $session->class_name }} · {{ $attendances->count() }}/{{ $roster->count() }} siswa hadir</p>
            <small>Status sesi: {{ $session->sessionStatus() }}</small>
        </div>
        @if ($session->token)
            <div class="button-group">
                <code>{{ $session->token }}</code>
                <button class="button button-quiet" type="button" data-copy-token="{{ $session->token }}">Salin token</button>
            </div>
        @else
            <small>Token disimpan aman dan tidak dapat ditampilkan ulang.</small>
        @endif
    </section>

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">SUDAH PRESENSI</span><h2>Siswa hadir</h2></div><span class="section-count">{{ $attendances->count() }} SISWA</span></div>
        @forelse ($attendances as $attendance)
            <article class="session-row">
                <div><strong>{{ $attendance->user->name }}</strong><small>{{ $attendance->user->email }}</small></div>
                <span class="status-badge">Hadir · {{ $attendance->created_at->format('d M Y, H:i:s') }}</span>
            </article>
        @empty
            <div class="empty-state"><strong>Belum ada siswa yang presensi.</strong><span>Siswa yang mengirim token dengan benar akan tampil di sini.</span></div>
        @endforelse
    </section>

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">BELUM PRESENSI</span><h2>Siswa belum hadir</h2></div><span class="section-count">{{ $absentStudents->count() }} SISWA</span></div>
        @forelse ($absentStudents as $student)
            <article class="session-row">
                <div><strong>{{ $student->name }}</strong><small>{{ $student->email }}</small></div>
                <span class="draft-badge">Belum presensi</span>
            </article>
        @empty
            <div class="empty-state">
                @if ($roster->isEmpty())
                    <strong>Belum ada siswa di kelas {{ $session->class_name }}.</strong><span>Atur kelas siswa dari halaman Kelola Kelas.</span>
                @else
                    <strong>Semua siswa sudah presensi.</strong><span>Seluruh siswa kelas {{ $session->class_name }} tercatat hadir.</span>
                @endif
            </div>
        @endforelse
    </section>

    @include('admin.partials.copy-token-script')
@endsection
