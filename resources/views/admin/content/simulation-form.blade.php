@extends('layouts.app')

@section('title', 'Edit simulasi')

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">KELOLA SIMULASI · EDIT</span><h1>{{ $simulation->title }}</h1><p>{{ $simulation->questions_count }} soal. Atur topik, durasi, batas percobaan, dan status publikasi.</p></div>
        <a class="button button-quiet" href="{{ route('admin.content.index') }}">Kembali ke Materi &amp; Simulasi</a>
    </section>

    <section class="form-panel">
        <div class="panel-heading"><span class="panel-index panel-index-coral">01</span><div><span class="section-kicker">LATIHAN TERARAH</span><h2>Edit simulasi</h2></div></div>
        <form method="POST" action="{{ route('admin.content.simulations.update', $simulation) }}" class="form-stack">
            @csrf
            @method('PUT')
            <label class="field-label" for="simulation-subject">Topik</label>
            <select id="simulation-subject" name="subject_id" required>
                @foreach ($subjects as $subject)
                    <option value="{{ $subject->id }}" @selected((int) old('subject_id', $simulation->subject_id) === $subject->id)>{{ $subject->name }}</option>
                @endforeach
            </select>
            <label class="field-label" for="simulation-title">Judul</label>
            <input id="simulation-title" name="title" value="{{ old('title', $simulation->title) }}" maxlength="255" required>
            <label class="field-label" for="simulation-description">Deskripsi</label>
            <textarea id="simulation-description" name="description" rows="3">{{ old('description', $simulation->description) }}</textarea>
            <div class="form-inline">
                <div>
                    <label class="field-label" for="simulation-duration">Durasi (menit)</label>
                    <input id="simulation-duration" name="duration_minutes" type="number" min="1" max="240" value="{{ old('duration_minutes', $simulation->duration_minutes) }}" required>
                </div>
                <div>
                    <label class="field-label" for="simulation-attempts">Batas percobaan <span class="optional">KOSONG = TANPA BATAS</span></label>
                    <input id="simulation-attempts" name="max_attempts" type="number" min="1" max="20" value="{{ old('max_attempts', $simulation->max_attempts) }}">
                </div>
            </div>
            <label class="check-line"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $simulation->is_published))> Terbitkan untuk siswa @if ($simulation->questions_count === 0)(belum ada soal, siswa belum bisa mengerjakan)@endif</label>
            <button class="button button-primary" type="submit">Simpan perubahan</button>
        </form>
    </section>
@endsection
