@extends('layouts.app')

@section('title', 'Kelola konten')

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">RUANG ADMIN</span><h1>Kelola pembelajaran</h1><p>Susun mata pelajaran, bahan ajar, dan latihan untuk siswa.</p></div>
        <a class="button button-quiet" href="{{ route('dashboard') }}">Kembali ke ringkasan</a>
    </section>

    @if (session('attendance_token'))
        <section class="token-reveal" role="status">
            <div><span class="section-kicker">TOKEN PRESENSI BARU</span><p>Salin dan bagikan sekarang. Token tidak ditampilkan lagi setelah sesi ini.</p><small>Berlaku sampai {{ session('attendance_expires_at') }}</small></div>
            <code>{{ session('attendance_token') }}</code>
        </section>
    @endif

    <div class="management-grid">
        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index">01</span><div><span class="section-kicker">STRUKTUR KELAS</span><h2>Mata pelajaran</h2></div></div>
            <form method="POST" action="{{ route('admin.subjects.store') }}" class="form-stack">
                @csrf
                <label class="field-label" for="subject-code">Kode kelas</label>
                <input id="subject-code" name="code" value="{{ old('code') }}" placeholder="Contoh: MTK-10" required>
                <label class="field-label" for="subject-name">Nama mata pelajaran</label>
                <input id="subject-name" name="name" value="{{ old('name') }}" placeholder="Matematika Dasar" required>
                <label class="field-label" for="subject-description">Deskripsi <span class="optional">OPSIONAL</span></label>
                <textarea id="subject-description" name="description" rows="3" placeholder="Gambaran singkat mata pelajaran">{{ old('description') }}</textarea>
                <button class="button button-primary" type="submit">Simpan mata pelajaran</button>
            </form>
        </section>

        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index panel-index-yellow">02</span><div><span class="section-kicker">BAHAN AJAR</span><h2>Tambah materi</h2></div></div>
            <form method="POST" action="{{ route('admin.materials.store') }}" class="form-stack">
                @csrf
                <label class="field-label" for="material-subject">Mata pelajaran</label>
                <select id="material-subject" name="subject_id" required><option value="">Pilih kelas</option>@foreach ($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select>
                <label class="field-label" for="material-title">Judul materi</label>
                <input id="material-title" name="title" placeholder="Judul bahan ajar" required>
                <label class="field-label" for="material-summary">Ringkasan</label>
                <input id="material-summary" name="summary" placeholder="Satu kalimat pengantar">
                <label class="field-label" for="material-content">Isi materi</label>
                <textarea id="material-content" name="content" rows="4" placeholder="Tulis materi belajar di sini"></textarea>
                <label class="field-label" for="material-url">Tautan sumber <span class="optional">OPSIONAL</span></label>
                <input id="material-url" name="resource_url" type="url" placeholder="https://">
                <button class="button button-primary" type="submit">Terbitkan materi</button>
            </form>
        </section>

        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index panel-index-coral">03</span><div><span class="section-kicker">LATIHAN TERARAH</span><h2>Buat simulasi</h2></div></div>
            <form method="POST" action="{{ route('admin.simulations.store') }}" class="form-stack">
                @csrf
                <label class="field-label" for="simulation-subject">Mata pelajaran</label>
                <select id="simulation-subject" name="subject_id" required><option value="">Pilih kelas</option>@foreach ($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select>
                <label class="field-label" for="simulation-title">Judul simulasi</label>
                <input id="simulation-title" name="title" placeholder="Latihan Bab 1" required>
                <label class="field-label" for="simulation-description">Deskripsi</label>
                <textarea id="simulation-description" name="description" rows="3" placeholder="Topik yang akan diujikan"></textarea>
                <label class="field-label" for="duration">Durasi (menit)</label>
                <input id="duration" name="duration_minutes" type="number" min="1" max="240" value="20" required>
                <label class="check-line"><input type="checkbox" name="is_published" value="1"> Publikasikan setelah soal ditambahkan</label>
                <button class="button button-primary" type="submit">Simpan simulasi</button>
            </form>
        </section>

        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index panel-index-blue">04</span><div><span class="section-kicker">PENILAIAN</span><h2>Tambah soal</h2></div></div>
            <form method="POST" action="{{ route('admin.questions.store') }}" class="form-stack">
                @csrf
                <label class="field-label" for="question-simulation">Simulasi</label>
                <select id="question-simulation" name="simulation_id" required>
                    <option value="">Pilih simulasi</option>@foreach ($simulations as $simulation)<option value="{{ $simulation->id }}">{{ $simulation->title }}</option>@endforeach
                </select>
                <label class="field-label" for="question-prompt">Pertanyaan</label>
                <textarea id="question-prompt" name="prompt" rows="3" required placeholder="Tulis pertanyaan"></textarea>
                <div class="choice-grid">
                    @foreach (['A', 'B', 'C', 'D'] as $choice)
                        <div><label class="field-label" for="choice-{{ strtolower($choice) }}">Pilihan {{ $choice }}</label><input id="choice-{{ strtolower($choice) }}" name="choices[{{ $choice }}]" required></div>
                    @endforeach
                </div>
                <div class="form-inline">
                    <div><label class="field-label" for="correct-answer">Jawaban benar</label><select id="correct-answer" name="correct_answer"><option>A</option><option>B</option><option>C</option><option>D</option></select></div>
                    <div><label class="field-label" for="points">Poin</label><input id="points" name="points" type="number" min="1" max="100" value="1" required></div>
                </div>
                <button class="button button-primary" type="submit" @disabled($simulations->isEmpty())>Simpan soal</button>
            </form>
        </section>

        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index panel-index-yellow">05</span><div><span class="section-kicker">KEHADIRAN KELAS</span><h2>Buat sesi presensi</h2></div></div>
            <form method="POST" action="{{ route('admin.attendance-tokens.store') }}" class="form-stack">
                @csrf
                <label class="field-label" for="attendance-subject">Mata pelajaran</label>
                <select id="attendance-subject" name="subject_id" required><option value="">Pilih kelas</option>@foreach ($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select>
                <label class="field-label" for="starts-at">Mulai · {{ config('app.timezone') }}</label>
                <input id="starts-at" name="starts_at" type="datetime-local" value="{{ old('starts_at', now()->addMinutes(5)->format('Y-m-d\\TH:i')) }}" required>
                <label class="field-label" for="expires-at">Berakhir · {{ config('app.timezone') }}</label>
                <input id="expires-at" name="expires_at" type="datetime-local" value="{{ old('expires_at', now()->addHour()->format('Y-m-d\\TH:i')) }}" required>
                <button class="button button-primary" type="submit" @disabled($subjects->isEmpty())>Buat dan tampilkan token</button>
            </form>
        </section>
    </div>

    <section class="content-section manage-lists">
        <div class="section-heading"><div><span class="section-kicker">KONTEN TERSIMPAN</span><h2>Ringkasan kelas</h2></div></div>
        @forelse ($subjects as $subject)
            <div class="list-row"><div><strong>{{ $subject->name }}</strong><small>{{ $subject->code }} · {{ $subject->materials_count }} materi · {{ $subject->simulations_count }} simulasi</small></div><span class="row-mark">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span></div>
        @empty
            <div class="empty-state"><strong>Belum ada mata pelajaran.</strong><span>Gunakan formulir di atas untuk membuat kelas pertama.</span></div>
        @endforelse
    </section>

    <section class="content-section manage-lists">
        <div class="section-heading"><div><span class="section-kicker">SESI TERJADWAL</span><h2>Token presensi</h2></div></div>
        @forelse ($attendanceTokens as $attendanceToken)
            <article class="session-row">
                <div><strong>{{ $attendanceToken->subject->name }}</strong><small>{{ $attendanceToken->starts_at->format('d M Y, H:i') }}–{{ $attendanceToken->expires_at->format('H:i') }} · token disimpan aman</small></div>
                @if ($attendanceToken->expires_at->isPast())
                    <span class="draft-badge">Berakhir</span>
                @elseif ($attendanceToken->starts_at->isFuture())
                    <span class="status-badge">Terjadwal</span>
                @else
                    <span class="active-badge">Berlangsung</span>
                @endif
            </article>
        @empty
            <div class="empty-state"><strong>Belum ada sesi presensi.</strong><span>Buat sesi dari formulir di atas. Token hanya tampil sekali setelah dibuat.</span></div>
        @endforelse
    </section>

    <section class="content-section manage-lists">
        <div class="section-heading"><div><span class="section-kicker">LATIHAN DAN MATERI</span><h2>Konten terbaru</h2></div></div>
        @foreach ($simulations as $simulation)
            <div class="list-row"><div><strong>{{ $simulation->title }}</strong><small>{{ $simulation->subject->name }} · {{ $simulation->questions_count }} soal</small></div><span class="{{ $simulation->is_published ? 'status-badge' : 'draft-badge' }}">{{ $simulation->is_published ? 'Terbit' : 'Draf' }}</span></div>
        @endforeach
        @foreach ($materials as $material)
            <div class="list-row"><div><strong>{{ $material->title }}</strong><small>{{ $material->subject->name }} · materi belajar</small></div><span class="item-tag">MATERI</span></div>
        @endforeach
    </section>
@endsection