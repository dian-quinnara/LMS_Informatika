@extends('layouts.app')

@section('title', 'Presensi siswa')

@section('content')
    @include('student.partials.heading', ['kicker' => 'PRESENSI', 'title' => 'Presensi siswa', 'description' => 'Masukkan token yang diberikan admin untuk mencatat kehadiran di jam pelajaran.'])

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
            <div class="section-heading"><div><span class="section-kicker">SESI AKTIF &amp; JADWAL</span><h2>Sesi presensi</h2></div></div>
            @forelse ($attendanceTokens as $attendanceToken)
                <article class="session-row">
                    <div><strong>{{ $attendanceToken->displayName() }}</strong><small>{{ $attendanceToken->starts_at->format('d M Y, H:i') }}–{{ $attendanceToken->expires_at->format('d M Y, H:i') }}</small></div>
                    @if (in_array($attendanceToken->id, $attendedTokenIds))
                        <span class="status-badge">Sudah hadir</span>
                    @elseif ($attendanceToken->starts_at->isFuture())
                        <span class="draft-badge">Akan dibuka</span>
                    @else
                        <span class="active-badge">Berlangsung · belum presensi</span>
                    @endif
                </article>
            @empty
                <div class="empty-state"><strong>Belum ada sesi presensi.</strong><span>Sesi aktif dan jadwal berikutnya akan tampil di sini.</span></div>
            @endforelse
        </div>
    </section>

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">RIWAYAT KEHADIRAN</span><h2>Riwayat presensi pribadi</h2></div><span class="section-count">{{ $attendanceHistory->count() }} TERAKHIR</span></div>
        @forelse ($attendanceHistory as $attendance)
            <article class="list-row"><div><strong>{{ $attendance->attendanceToken?->displayName() ?? $attendance->subject?->name ?? 'Sesi presensi' }}</strong><small>{{ $attendance->attended_on->format('d M Y') }}@if ($attendance->attendanceToken) · sesi {{ $attendance->attendanceToken->starts_at->format('H:i') }}@endif · tercatat {{ $attendance->created_at->format('H:i') }}</small></div><span class="status-badge">Hadir</span></article>
        @empty
            <div class="empty-state"><strong>Riwayat presensi masih kosong.</strong><span>Presensi yang berhasil akan tercatat di sini.</span></div>
        @endforelse
    </section>
@endsection
