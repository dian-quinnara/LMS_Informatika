{{-- Kelola Presensi: sesi presensi kelas X (XE1–XE9). Memakai komponen gaya yang sudah ada. --}}
<section class="content-section" id="kelas-x">
    <div class="section-heading">
        <div><span class="section-kicker">KELAS X · XE1–XE9</span><h2>Presensi kelas</h2></div>
        <a class="text-link" href="{{ route('admin.classes.index') }}">Atur kelas siswa <span aria-hidden="true">&#8594;</span></a>
    </div>
    <div class="management-grid">
        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index">01</span><div><span class="section-kicker">KEHADIRAN KELAS</span><h2>Buat sesi presensi</h2></div></div>
            <form method="POST" action="{{ route('admin.attendance-sessions.store') }}" class="form-stack" data-single-submit>
                @csrf
                <label class="field-label" for="session-class">Kelas</label>
                <select id="session-class" name="class_name" required>
                    <option value="">Pilih kelas</option>
                    @foreach ($gradeXClasses as $class)
                        <option value="{{ $class }}" @selected(old('class_name') === $class)>{{ $class }} · {{ $rosterCounts[$class] ?? 0 }} siswa</option>
                    @endforeach
                </select>
                <label class="field-label" for="session-title">Judul sesi <span class="optional">OPSIONAL</span></label>
                <input id="session-title" name="title" value="{{ old('title') }}" maxlength="120" placeholder="Contoh: Informatika – Pertemuan 3">
                <label class="field-label" for="session-starts-at">Mulai · {{ config('app.timezone') }}</label>
                <input id="session-starts-at" name="starts_at" type="datetime-local" value="{{ old('starts_at', now()->format('Y-m-d\\TH:i')) }}" required>
                <label class="field-label" for="session-expires-at">Berakhir · {{ config('app.timezone') }}</label>
                <input id="session-expires-at" name="expires_at" type="datetime-local" value="{{ old('expires_at', now()->addMinutes(90)->format('Y-m-d\\TH:i')) }}" required>
                <button class="button button-primary" type="submit">Buat sesi dan token</button>
            </form>
        </section>

        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index panel-index-yellow">02</span><div><span class="section-kicker">REKAP PRESENSI</span><h2>Laporan &amp; unduhan</h2></div></div>
            <form method="GET" action="{{ route('admin.attendance-report.download') }}" class="form-stack">
                <label class="field-label" for="download-class">Kelas</label>
                <select id="download-class" name="class_name" required>
                    <option value="">Pilih kelas</option>
                    @foreach ($gradeXClasses as $class)
                        <option value="{{ $class }}">{{ $class }} · {{ $rosterCounts[$class] ?? 0 }} siswa</option>
                    @endforeach
                </select>
                <label class="field-label" for="download-date-from">Dari tanggal</label>
                <input id="download-date-from" name="date_from" type="date" value="{{ now()->startOfMonth()->format('Y-m-d') }}" required>
                <label class="field-label" for="download-date-to">Sampai tanggal</label>
                <input id="download-date-to" name="date_to" type="date" value="{{ now()->format('Y-m-d') }}" required>
                <small class="optional">File CSV dapat dibuka di Excel: satu baris per siswa, satu kolom per sesi.</small>
                <button class="button button-primary" type="submit">Unduh laporan presensi</button>
                <a class="text-link" href="{{ route('admin.attendance-report') }}">Lihat rekap di layar <span aria-hidden="true">&#8594;</span></a>
            </form>
        </section>
    </div>
</section>

<section class="content-section manage-lists">
    <div class="section-heading"><div><span class="section-kicker">SESI TERSIMPAN</span><h2>Sesi presensi</h2></div><span class="section-count">{{ $attendanceSessions->count() }} SESI</span></div>
    <form method="GET" action="{{ route('admin.attendance.index') }}" class="form-panel form-stack">
        <div class="form-inline">
            <div>
                <label class="field-label" for="filter-class">Kelas</label>
                <select id="filter-class" name="class">
                    <option value="">Semua kelas X</option>
                    @foreach ($gradeXClasses as $class)
                        <option value="{{ $class }}" @selected($sessionFilters['class'] === $class)>{{ $class }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label" for="filter-status">Status</label>
                <select id="filter-status" name="status">
                    <option value="">Semua status</option>
                    @foreach ($statusLabels as $key => $label)
                        <option value="{{ $key }}" @selected($sessionFilters['status'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label" for="filter-date-from">Dari tanggal</label>
                <input id="filter-date-from" name="date_from" type="date" value="{{ $sessionFilters['date_from'] }}">
            </div>
            <div>
                <label class="field-label" for="filter-date-to">Sampai tanggal</label>
                <input id="filter-date-to" name="date_to" type="date" value="{{ $sessionFilters['date_to'] }}">
            </div>
        </div>
        <div class="button-group">
            <button class="button button-primary" type="submit">Terapkan filter</button>
            <a class="button button-quiet" href="{{ route('admin.attendance.index') }}">Atur ulang</a>
        </div>
    </form>
    @forelse ($attendanceSessions as $session)
        <article class="session-row">
            <div>
                <strong>{{ $session->class_name }}@if ($session->title) · {{ $session->title }}@endif</strong>
                <small>{{ $session->starts_at->format('d M Y, H:i') }} – {{ $session->expires_at->format('d M Y, H:i') }} · token {{ $session->token ?? 'tersimpan aman' }} · {{ $session->attendances_count }}/{{ $rosterCounts[$session->class_name] ?? 0 }} hadir</small>
            </div>
            @include('admin.partials.session-status', ['session' => $session])
            <a class="text-link" href="{{ route('admin.attendance-sessions.show', $session) }}">Detail <span aria-hidden="true">&#8594;</span></a>
        </article>
    @empty
        <div class="empty-state"><strong>Belum ada sesi presensi kelas X.</strong><span>Buat sesi dari formulir di atas atau ubah filter.</span></div>
    @endforelse
    @if ($attendanceSessions->count() >= 50)
        <small class="optional">Menampilkan 50 sesi terbaru. Gunakan filter untuk mempersempit hasil.</small>
    @endif
</section>

@include('admin.partials.copy-token-script')
