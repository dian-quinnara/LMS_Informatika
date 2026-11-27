@extends('layouts.app')

@section('title', 'Hasil & analisis belajar')

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">HASIL &amp; ANALISIS BELAJAR</span><h1>Hasil &amp; analisis</h1><p>Nilai kuis, hasil per kelas, keberhasilan soal, materi tersulit, dan siswa yang membutuhkan pendampingan (KKM {{ $passing }}).</p></div>
        <a class="button button-quiet" href="{{ route('admin.quizzes.index') }}">Kelola soal &amp; kuis</a>
    </section>

    <form method="GET" action="{{ route('admin.results.index') }}" class="form-panel form-stack">
        <div class="form-inline">
            <div>
                <label class="field-label" for="results-class">Kelas</label>
                <select id="results-class" name="class">
                    <option value="">Semua kelas</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class }}" @selected($filters['class'] === $class)>{{ $class }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="field-label" for="results-quiz">Kuis</label>
                <select id="results-quiz" name="quiz">
                    <option value="">Semua kuis</option>
                    @foreach ($quizzes as $quiz)
                        <option value="{{ $quiz->id }}" @selected($filters['quiz'] === $quiz->id)>{{ $quiz->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="button-group">
            <button class="button button-primary" type="submit">Terapkan filter</button>
            <a class="button button-quiet" href="{{ route('admin.results.index') }}">Atur ulang</a>
        </div>
    </form>

    <section class="stat-grid" aria-label="Ringkasan hasil">
        <article class="stat-tile stat-green"><span>PENGERJAAN</span><strong>{{ $summary['attempts'] }}</strong><small>kuis selesai</small></article>
        <article class="stat-tile stat-yellow"><span>SISWA</span><strong>{{ $summary['students'] }}</strong><small>sudah mengerjakan</small></article>
        <article class="stat-tile stat-coral"><span>RATA-RATA</span><strong>{{ $summary['average'] }}</strong><small>nilai kuis</small></article>
        <article class="stat-tile stat-blue"><span>TUNTAS</span><strong>{{ $summary['passed'] }}</strong><small>nilai ≥ {{ $passing }}</small></article>
    </section>

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">PERLU PERHATIAN</span><h2>Siswa yang membutuhkan pendampingan</h2></div><span class="section-count">{{ $needsMentoring->count() }} SISWA</span></div>
        @forelse ($needsMentoring as $row)
            <article class="session-row">
                <div><strong>{{ $row['student']->name }}</strong><small>{{ $row['student']->class_name ? 'Kelas '.$row['student']->class_name : 'tanpa kelas' }} · {{ $row['attempts'] }} pengerjaan · nilai terendah {{ $row['lowest'] }}</small></div>
                <span class="draft-badge">Rata-rata {{ $row['average'] }}</span>
            </article>
        @empty
            <div class="empty-state"><strong>Tidak ada siswa di bawah KKM.</strong><span>Siswa dengan rata-rata nilai di bawah {{ $passing }} akan muncul di sini.</span></div>
        @endforelse
    </section>

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">PER KELAS</span><h2>Hasil berdasarkan kelas</h2></div></div>
        @foreach ($byClass as $row)
            <article class="session-row">
                <div><strong>Kelas {{ $row['class'] }}</strong><small>{{ $row['students'] }} siswa · {{ $row['attempts'] }} pengerjaan</small></div>
                <span class="{{ $row['attempts'] === 0 ? 'draft-badge' : ($row['average'] >= $passing ? 'status-badge' : 'draft-badge') }}">{{ $row['attempts'] === 0 ? 'Belum ada data' : 'Rata-rata '.$row['average'] }}</span>
            </article>
        @endforeach
    </section>

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">MATERI</span><h2>Materi yang paling sulit</h2></div></div>
        @forelse ($difficultMaterials as $row)
            <article class="session-row">
                <div><strong>{{ $row['material'] }}</strong><small>{{ $row['wrong'] }} dari {{ $row['total'] }} jawaban belum tepat</small></div>
                <span class="{{ $row['rate'] >= $passing ? 'status-badge' : 'draft-badge' }}">{{ $row['rate'] }}% benar</span>
            </article>
        @empty
            <div class="empty-state"><strong>Belum ada jawaban kuis.</strong><span>Analisis materi muncul setelah siswa mengerjakan kuis.</span></div>
        @endforelse
    </section>

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">BUTIR SOAL</span><h2>Tingkat keberhasilan setiap soal</h2></div><span class="section-count">DARI YANG TERSULIT</span></div>
        @forelse ($questionStats as $row)
            <article class="session-row">
                <div><strong>{{ \Illuminate\Support\Str::limit($row['prompt'], 140) }}</strong><small>{{ $row['quiz'] }}{{ $row['category'] ? ' · '.$row['category'] : '' }} · {{ $row['correct'] }}/{{ $row['total'] }} benar</small></div>
                <span class="{{ $row['rate'] >= $passing ? 'status-badge' : 'draft-badge' }}">{{ $row['rate'] }}%</span>
            </article>
        @empty
            <div class="empty-state"><strong>Belum ada data soal.</strong><span>Statistik soal dihitung dari jawaban kuis yang sudah dikirim.</span></div>
        @endforelse
    </section>

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">NILAI KUIS</span><h2>Nilai kuis siswa</h2></div><span class="section-count">{{ $scores->count() }} TERBARU</span></div>
        @forelse ($scores as $attempt)
            <div class="list-row"><div><strong>{{ $attempt->user?->name ?? 'Siswa' }}</strong><small>{{ $attempt->simulation?->title }} · {{ $attempt->user?->class_name ?? 'tanpa kelas' }} · {{ $attempt->submitted_at?->format('d/m/Y H:i') }}</small></div><span class="score-badge">{{ number_format((float) $attempt->score, 0) }}%</span></div>
        @empty
            <div class="empty-state"><strong>Belum ada nilai kuis.</strong><span>Nilai tampil setelah siswa menyelesaikan kuis.</span></div>
        @endforelse
    </section>

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">AKTIVITAS</span><h2>Riwayat aktivitas simulasi</h2></div></div>
        @forelse ($activity as $attempt)
            <article class="session-row">
                <div><strong>{{ $attempt->user?->name ?? 'Siswa' }} · {{ $attempt->simulation?->title }}</strong><small>Mulai {{ $attempt->started_at?->format('d/m/Y H:i') }}{{ $attempt->submitted_at ? ' · selesai '.$attempt->submitted_at->format('H:i') : '' }} · {{ $attempt->user?->class_name ?? 'tanpa kelas' }}</small></div>
                <span class="{{ $attempt->status === 'completed' ? 'status-badge' : 'active-badge' }}">{{ $attempt->status === 'completed' ? 'Selesai · '.number_format((float) $attempt->score, 0).'%' : 'Sedang dikerjakan' }}</span>
            </article>
        @empty
            <div class="empty-state"><strong>Belum ada aktivitas simulasi.</strong><span>Riwayat pengerjaan siswa akan muncul di sini.</span></div>
        @endforelse
    </section>
@endsection
