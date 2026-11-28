@extends('layouts.app')

@section('title', 'Kelola soal & kuis')

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">KELOLA SOAL &amp; KUIS</span><h1>Bank soal &amp; kuis</h1><p>Kelompokkan soal per materi, tandai LOTS/HOTS, simpan kunci dan pembahasan, lalu susun kuis dari bank soal.</p></div>
        <a class="button button-quiet" href="{{ route('admin.results.index') }}">Hasil &amp; analisis</a>
    </section>

    <section class="stat-grid" aria-label="Ringkasan bank soal">
        <article class="stat-tile stat-green"><span>BANK SOAL</span><strong>{{ $categoryCounts->sum() }}</strong><small>soal tersimpan</small></article>
        <article class="stat-tile stat-yellow"><span>SOAL LOTS</span><strong>{{ $categoryCounts['LOTS'] ?? 0 }}</strong><small>berpikir tingkat rendah</small></article>
        <article class="stat-tile stat-coral"><span>SOAL HOTS</span><strong>{{ $categoryCounts['HOTS'] ?? 0 }}</strong><small>berpikir tingkat tinggi</small></article>
        <article class="stat-tile stat-blue"><span>KUIS</span><strong>{{ $quizzes->count() }}</strong><small>kuis &amp; simulasi tersimpan</small></article>
    </section>

    <div class="management-grid">
        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index panel-index-blue">01</span><div><span class="section-kicker">BANK SOAL</span><h2>Tambah soal &amp; topik</h2></div></div>
            <form method="POST" action="{{ route('admin.question-bank.store') }}" class="form-stack" enctype="multipart/form-data" data-single-submit>
                @csrf
                @include('admin.quizzes.partials.question-fields', ['question' => null])
                <button class="button button-primary" type="submit">Simpan ke bank soal</button>
            </form>
        </section>

        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index panel-index-yellow">02</span><div><span class="section-kicker">SUSUN KUIS</span><h2>Buat kuis dari bank soal</h2></div></div>
            <form method="POST" action="{{ route('admin.quizzes.store') }}" class="form-stack" data-single-submit>
                @csrf
                <label class="field-label" for="quiz-subject">Topik</label>
                <select id="quiz-subject" name="subject_id" required><option value="">Pilih topik</option>@foreach ($subjects as $subject)<option value="{{ $subject->id }}" @selected((int) old('subject_id') === $subject->id)>{{ $subject->name }}</option>@endforeach</select>
                <label class="field-label" for="quiz-title">Judul kuis</label>
                <input id="quiz-title" name="title" value="{{ old('title') }}" maxlength="255" placeholder="Kuis Searching" required>
                <label class="field-label" for="quiz-description">Deskripsi <span class="optional">OPSIONAL</span></label>
                <textarea id="quiz-description" name="description" rows="2">{{ old('description') }}</textarea>
                <div class="form-inline">
                    <div><label class="field-label" for="quiz-duration">Waktu (menit)</label><input id="quiz-duration" name="duration_minutes" type="number" min="1" max="240" value="{{ old('duration_minutes', 20) }}" required></div>
                    <div><label class="field-label" for="quiz-attempts">Batas percobaan <span class="optional">KOSONG = TANPA BATAS</span></label><input id="quiz-attempts" name="max_attempts" type="number" min="1" max="20" value="{{ old('max_attempts', 1) }}"></div>
                </div>
                <span class="field-label">Pilih soal ({{ $questions->count() }} tersedia{{ $filters['material'] || $filters['category'] ? ' sesuai filter' : '' }})</span>
                @forelse ($questions as $question)
                    <label class="check-line"><input type="checkbox" name="question_ids[]" value="{{ $question->id }}" @checked(in_array($question->id, old('question_ids', [])))> [{{ $question->category }}] {{ \Illuminate\Support\Str::limit($question->prompt, 80) }} · {{ $question->materialLabel() }}@if ($question->attachment_path) · ada file @endif</label>
                @empty
                    <small class="optional">Bank soal masih kosong.</small>
                @endforelse
                <label class="check-line"><input type="checkbox" name="is_published" value="1" @checked(old('is_published'))> Terbitkan kuis untuk siswa</label>
                <button class="button button-primary" type="submit" @disabled($questions->isEmpty() || $subjects->isEmpty())>Buat kuis</button>
            </form>
        </section>
    </div>

    <section class="content-section manage-lists">
        <div class="section-heading"><div><span class="section-kicker">BANK SOAL</span><h2>Soal per materi</h2></div><span class="section-count">{{ $questions->count() }} SOAL</span></div>
        <form method="GET" action="{{ route('admin.quizzes.index') }}" class="form-panel form-stack">
            <div class="form-inline">
                <div>
                    <label class="field-label" for="filter-material">Materi</label>
                    <select id="filter-material" name="material">
                        <option value="">Semua materi</option>
                        @foreach ($materialOptions as $group => $options)
                            <optgroup label="{{ $group }}">
                                @foreach ($options as $value => $label)
                                    <option value="{{ $value }}" @selected($filters['material'] === $value)>{{ $label }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label" for="filter-category">Kategori</label>
                    <select id="filter-category" name="category">
                        <option value="">LOTS &amp; HOTS</option>
                        @foreach (\App\Models\BankQuestion::CATEGORIES as $category)
                            <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="button-group">
                <button class="button button-primary" type="submit">Terapkan filter</button>
                <a class="button button-quiet" href="{{ route('admin.quizzes.index') }}">Atur ulang</a>
            </div>
        </form>
        @forelse ($questions->groupBy(fn ($question) => $question->materialLabel()) as $materialTitle => $group)
            <div class="section-heading"><div><span class="section-kicker">MATERI</span><h2>{{ $materialTitle }}</h2></div><span class="section-count">{{ $group->count() }} SOAL</span></div>
            @foreach ($group as $question)
                <article class="session-row">
                    <div>
                        <strong>{{ \Illuminate\Support\Str::limit($question->prompt, 140) }}</strong>
                        <small>Kunci {{ $question->correct_answer }} · {{ $question->points }} poin · dipakai di {{ $question->quiz_questions_count }} kuis{{ $question->explanation ? ' · ada pembahasan' : ' · belum ada pembahasan' }}@if ($question->attachment_path) · file: <a class="text-link" href="{{ route('admin.question-bank.attachment', $question) }}" target="_blank" rel="noopener">{{ $question->attachment_name }}</a>@endif</small>
                    </div>
                    <span class="{{ $question->category === 'HOTS' ? 'active-badge' : 'status-badge' }}">{{ $question->category }}</span>
                    <a class="text-link" href="{{ route('admin.question-bank.edit', $question) }}">Edit <span aria-hidden="true">&#8594;</span></a>
                </article>
            @endforeach
        @empty
            <div class="empty-state"><strong>Belum ada soal di bank soal.</strong><span>Tambahkan soal dari formulir di atas.</span></div>
        @endforelse
    </section>

    <section class="content-section manage-lists">
        <div class="section-heading"><div><span class="section-kicker">KUIS TERSIMPAN</span><h2>Waktu &amp; batas percobaan</h2></div><span class="section-count">{{ $quizzes->count() }} KUIS</span></div>
        @forelse ($quizzes as $quiz)
            <article class="session-row">
                <div><strong>{{ $quiz->title }}</strong><small>{{ $quiz->subject?->name }} · {{ $quiz->questions_count }} soal · {{ $quiz->attempts_count }} pengerjaan</small></div>
                <form method="POST" action="{{ route('admin.quizzes.update', $quiz) }}" class="button-group">
                    @csrf
                    @method('PUT')
                    <label class="field-label" for="quiz-{{ $quiz->id }}-duration">Menit</label>
                    <input id="quiz-{{ $quiz->id }}-duration" name="duration_minutes" type="number" min="1" max="240" value="{{ $quiz->duration_minutes }}" required style="width: 72px">
                    <label class="field-label" for="quiz-{{ $quiz->id }}-attempts">Maks.</label>
                    <input id="quiz-{{ $quiz->id }}-attempts" name="max_attempts" type="number" min="1" max="20" value="{{ $quiz->max_attempts }}" placeholder="∞" style="width: 64px">
                    <label class="check-line"><input type="checkbox" name="is_published" value="1" @checked($quiz->is_published)> Terbit</label>
                    <button class="button button-quiet" type="submit">Simpan</button>
                </form>
            </article>
        @empty
            <div class="empty-state"><strong>Belum ada kuis.</strong><span>Buat kuis dari bank soal di atas.</span></div>
        @endforelse
    </section>
@endsection
