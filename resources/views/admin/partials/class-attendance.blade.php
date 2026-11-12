{{-- Kelola Kelas: sesi presensi kelas X (XE1–XE9). Memakai komponen gaya yang sudah ada. --}}
<section class="content-section" id="kelas-x">
    <div class="section-heading">
        <div><span class="section-kicker">KELAS X · XE1–XE9</span><h2>Presensi kelas</h2></div>
        <a class="text-link" href="{{ route('admin.attendance-report') }}">Laporan presensi <span aria-hidden="true">&#8594;</span></a>
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
            <div class="panel-heading"><span class="panel-index panel-index-yellow">02</span><div><span class="section-kicker">ROSTER KELAS</span><h2>Atur kelas siswa</h2></div></div>
            <form method="POST" action="{{ route('admin.student-classes.update') }}" class="form-stack">
                @csrf
                @method('PATCH')
                <label class="field-label" for="roster-student">Siswa</label>
                <select id="roster-student" name="user_id" required>
                    <option value="">Pilih siswa</option>
                    @foreach ($classStudents as $student)
                        <option value="{{ $student->id }}">{{ $student->name }} · {{ $student->class_name ?? 'belum ada kelas' }}</option>
                    @endforeach
                </select>
                <label class="field-label" for="roster-class">Kelas</label>
                <select id="roster-class" name="class_name">
                    <option value="">Tanpa kelas</option>
                    @foreach ($gradeXClasses as $class)
                        <option value="{{ $class }}">{{ $class }}</option>
                    @endforeach
                </select>
                <small class="optional">Siswa hanya dapat presensi pada sesi kelasnya sendiri. {{ $classStudents->whereNull('class_name')->count() }} siswa belum memiliki kelas.</small>
                <button class="button button-primary" type="submit" @disabled($classStudents->isEmpty())>Simpan kelas siswa</button>
            </form>
        </section>
    </div>
</section>

<section class="content-section manage-lists">
    <div class="section-heading"><div><span class="section-kicker">SESI TERSIMPAN</span><h2>Sesi presensi</h2></div><span class="section-count">{{ $attendanceSessions->count() }} SESI</span></div>
    <form method="GET" action="{{ route('dashboard') }}" class="form-panel form-stack">
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
            <a class="button button-quiet" href="{{ route('dashboard') }}#kelas-x">Atur ulang</a>
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
