@extends('layouts.app')

@section('title', 'Laporan presensi')

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">KELOLA KELAS · LAPORAN</span><h1>Laporan presensi</h1><p>Rekap kehadiran kelas X berdasarkan kelas dan tanggal sesi.</p></div>
        <a class="button button-quiet" href="{{ route('dashboard') }}#kelas-x">Kembali ke Kelola Kelas</a>
    </section>

    <form method="GET" action="{{ route('admin.attendance-report') }}" class="form-panel form-stack">
        <div class="form-inline">
            <div>
                <label class="field-label" for="report-class">Kelas</label>
                <select id="report-class" name="class_name" required>
                    <option value="">Pilih kelas</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class }}" @selected($className === $class)>{{ $class }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label" for="report-date-from">Dari tanggal</label>
                <input id="report-date-from" name="date_from" type="date" value="{{ $dateFrom->format('Y-m-d') }}" required>
            </div>
            <div>
                <label class="field-label" for="report-date-to">Sampai tanggal <span class="optional">OPSIONAL</span></label>
                <input id="report-date-to" name="date_to" type="date" value="{{ $dateTo->format('Y-m-d') }}">
            </div>
        </div>
        <button class="button button-primary" type="submit">Tampilkan laporan</button>
    </form>

    @if ($className === null)
        <section class="content-section">
            <div class="empty-state"><strong>Pilih kelas untuk melihat laporan.</strong><span>Laporan dihitung dari sesi dan presensi yang tersimpan di database.</span></div>
        </section>
    @else
        <section class="content-section">
            <div class="section-heading">
                <div><span class="section-kicker">KELAS {{ $className }} · {{ $dateFrom->format('d M Y') }}@if (! $dateFrom->isSameDay($dateTo)) – {{ $dateTo->format('d M Y') }}@endif</span><h2>Rekap siswa</h2></div>
                <span class="section-count">{{ $sessions->count() }} SESI · {{ $roster->count() }} SISWA</span>
            </div>
            @forelse ($studentSummary as $row)
                <article class="session-row">
                    <div><strong>{{ $row['student']->name }}</strong><small>{{ $row['student']->email }}</small></div>
                    <span class="{{ $sessions->isNotEmpty() && $row['attended'] === $sessions->count() ? 'status-badge' : 'draft-badge' }}">Hadir {{ $row['attended'] }}/{{ $sessions->count() }} sesi</span>
                </article>
            @empty
                <div class="empty-state"><strong>Belum ada siswa di kelas {{ $className }}.</strong><span>Atur kelas siswa dari halaman Kelola Kelas agar roster terisi.</span></div>
            @endforelse
        </section>

        @forelse ($sessions as $session)
            <section class="content-section">
                <div class="section-heading">
                    <div>
                        <span class="section-kicker">{{ $session->starts_at->locale('id')->translatedFormat('l, d F Y') }} · {{ $session->starts_at->format('H:i') }} – {{ $session->expires_at->format('H:i') }}</span>
                        <h2>{{ $session->title ?: 'Sesi presensi '.$session->class_name }}</h2>
                    </div>
                    <a class="text-link" href="{{ route('admin.attendance-sessions.show', $session) }}">{{ $session->attendances->count() }}/{{ $roster->count() }} hadir · {{ $session->sessionStatus() }} <span aria-hidden="true">&#8594;</span></a>
                </div>
                @foreach ($session->attendances as $attendance)
                    <article class="session-row">
                        <div><strong>{{ $attendance->user->name }}</strong><small>{{ $attendance->user->email }}</small></div>
                        <span class="status-badge">Hadir · {{ $attendance->created_at->format('H:i:s') }}</span>
                    </article>
                @endforeach
                @foreach ($session->absent_students as $student)
                    <article class="session-row">
                        <div><strong>{{ $student->name }}</strong><small>{{ $student->email }}</small></div>
                        <span class="draft-badge">Belum presensi</span>
                    </article>
                @endforeach
                @if ($session->attendances->isEmpty() && $session->absent_students->isEmpty())
                    <div class="empty-state"><strong>Tidak ada data siswa untuk sesi ini.</strong><span>Roster kelas {{ $className }} masih kosong.</span></div>
                @endif
            </section>
        @empty
            <section class="content-section">
                <div class="empty-state"><strong>Tidak ada sesi presensi pada periode ini.</strong><span>Ubah kelas atau rentang tanggal laporan.</span></div>
            </section>
        @endforelse
    @endif
@endsection
