@extends('layouts.app')

@section('title', 'Kelola materi & simulasi')

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">KELOLA MATERI &amp; SIMULASI</span><h1>Materi &amp; simulasi</h1><p>Susun materi per topik, unggah media pendukung, atur urutan dan status publikasi, serta kelola simulasi.</p></div>
        <a class="button button-quiet" href="{{ route('admin.quizzes.index') }}">Kelola soal &amp; kuis</a>
    </section>

    <div class="management-grid">
        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index">01</span><div><span class="section-kicker">KELOMPOK MATERI</span><h2>Tambah topik</h2></div></div>
            <form method="POST" action="{{ route('admin.subjects.store') }}" class="form-stack">
                @csrf
                <label class="field-label" for="subject-code">Kode topik</label>
                <input id="subject-code" name="code" value="{{ old('code') }}" maxlength="30" placeholder="Contoh: IF-SORTING" required>
                <label class="field-label" for="subject-name">Nama topik</label>
                <input id="subject-name" name="name" value="{{ old('name') }}" placeholder="Sorting" required>
                <label class="field-label" for="subject-description">Deskripsi <span class="optional">OPSIONAL</span></label>
                <textarea id="subject-description" name="description" rows="3" placeholder="Gambaran singkat topik">{{ old('description') }}</textarea>
                <button class="button button-primary" type="submit">Simpan topik</button>
            </form>
        </section>

        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index panel-index-yellow">02</span><div><span class="section-kicker">BAHAN AJAR</span><h2>Tambah materi</h2></div></div>
            <form method="POST" action="{{ route('admin.content.materials.store') }}" class="form-stack" enctype="multipart/form-data" data-single-submit>
                @csrf
                @include('admin.content.partials.material-fields', ['material' => null])
                <button class="button button-primary" type="submit" @disabled($subjects->isEmpty())>Simpan materi</button>
            </form>
        </section>

        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index panel-index-coral">03</span><div><span class="section-kicker">LATIHAN TERARAH</span><h2>Buat simulasi</h2></div></div>
            <form method="POST" action="{{ route('admin.simulations.store') }}" class="form-stack">
                @csrf
                <label class="field-label" for="simulation-subject">Topik</label>
                <select id="simulation-subject" name="subject_id" required><option value="">Pilih topik</option>@foreach ($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}</option>@endforeach</select>
                <label class="field-label" for="simulation-title">Judul simulasi</label>
                <input id="simulation-title" name="title" placeholder="Latihan Bab 1" required>
                <label class="field-label" for="simulation-description">Deskripsi</label>
                <textarea id="simulation-description" name="description" rows="3" placeholder="Topik yang akan diujikan"></textarea>
                <label class="field-label" for="duration">Durasi (menit)</label>
                <input id="duration" name="duration_minutes" type="number" min="1" max="240" value="20" required>
                <label class="check-line"><input type="checkbox" name="is_published" value="1"> Publikasikan setelah soal ditambahkan</label>
                <button class="button button-primary" type="submit" @disabled($subjects->isEmpty())>Simpan simulasi</button>
            </form>
        </section>
    </div>

    @forelse ($subjects as $subject)
        <section class="content-section manage-lists">
            <div class="section-heading"><div><span class="section-kicker">TOPIK · {{ $subject->code }}</span><h2>{{ $subject->name }}</h2></div><span class="section-count">{{ $subject->materials_count }} MATERI · {{ $subject->simulations_count }} SIMULASI</span></div>
            @forelse ($subject->materials as $material)
                <article class="session-row">
                    <div>
                        <strong>{{ str_pad((string) $material->sort_order, 2, '0', STR_PAD_LEFT) }} · {{ $material->title }}</strong>
                        <small>{{ $material->summary ?: 'Tanpa ringkasan' }}@if ($material->attachment_name) · lampiran: {{ $material->attachment_name }}@endif @if ($material->slug) · materi bawaan @endif</small>
                    </div>
                    <span class="{{ $material->is_published ? 'status-badge' : 'draft-badge' }}">{{ $material->is_published ? 'Terbit' : 'Draf' }}</span>
                    <div class="button-group">
                        <a class="text-link" href="{{ route('admin.content.materials.edit', $material) }}">Edit <span aria-hidden="true">&#8594;</span></a>
                        <form method="POST" action="{{ route('admin.content.materials.publication', $material) }}">
                            @csrf
                            @method('PATCH')
                            <button class="button button-quiet" type="submit">{{ $material->is_published ? 'Jadikan draf' : 'Terbitkan' }}</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="empty-state"><strong>Belum ada materi di topik ini.</strong><span>Tambahkan materi dari formulir di atas.</span></div>
            @endforelse
        </section>
    @empty
        <section class="content-section"><div class="empty-state"><strong>Belum ada topik.</strong><span>Buat topik pertama untuk mengelompokkan materi.</span></div></section>
    @endforelse

    <section class="content-section manage-lists">
        <div class="section-heading"><div><span class="section-kicker">SIMULASI &amp; LATIHAN</span><h2>Daftar simulasi</h2></div><span class="section-count">{{ $simulations->count() }} SIMULASI</span></div>
        @foreach ($interactiveSimulations as $interactive)
            <article class="session-row">
                <div><strong>{{ $interactive['title'] }}</strong><small>{{ $interactive['description'] }} · simulasi interaktif bawaan</small></div>
                <span class="active-badge">Interaktif</span>
            </article>
        @endforeach
        @forelse ($simulations as $simulation)
            <article class="session-row">
                <div><strong>{{ $simulation->title }}</strong><small>{{ $simulation->subject?->name }} · {{ $simulation->questions_count }} soal · {{ $simulation->duration_minutes }} menit · {{ $simulation->max_attempts ? 'maks. '.$simulation->max_attempts.'x' : 'percobaan tanpa batas' }} · {{ $simulation->attempts_count }} pengerjaan</small></div>
                <span class="{{ $simulation->is_published ? 'status-badge' : 'draft-badge' }}">{{ $simulation->is_published ? 'Terbit' : 'Draf' }}</span>
                <a class="text-link" href="{{ route('admin.content.simulations.edit', $simulation) }}">Edit <span aria-hidden="true">&#8594;</span></a>
            </article>
        @empty
            <div class="empty-state"><strong>Belum ada simulasi.</strong><span>Buat simulasi di atas atau buat kuis dari bank soal.</span></div>
        @endforelse
    </section>
@endsection
