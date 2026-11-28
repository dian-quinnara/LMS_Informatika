@extends('layouts.app')

@section('title', 'Latihan & Kuis')

@section('content')
    @include('student.partials.heading', ['kicker' => 'LATIHAN & KUIS', 'title' => 'Latihan & Kuis', 'description' => 'Latihan setelah mempelajari materi dan kuis dari guru: pilihan ganda dan benar/salah, soal LOTS dan HOTS, lengkap dengan pembahasan.'])

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">LATIHAN PER MATERI</span><h2>Latihan soal</h2></div></div>
        @foreach ($topics as $topic)
            @foreach ($topic['practice_rows'] as $practice)
                <a class="session-row" href="{{ $practice['url'] }}">
                    <div>
                        <strong>{{ $practice['label'] }}</strong>
                        <small>{{ $topic['title'] }} · {{ $practice['count'] }} soal · {{ implode(' & ', $practice['categories']) }} · {{ implode(', ', $practice['types']) }}</small>
                    </div>
                    @if ($practice['activity'])
                        <span class="{{ $practice['activity']->last_score >= $passing ? 'status-badge' : 'draft-badge' }}">Nilai {{ $practice['activity']->last_score }} · terbaik {{ $practice['activity']->best_score }}</span>
                    @else
                        <span class="text-link">Kerjakan <span aria-hidden="true">&#8594;</span></span>
                    @endif
                </a>
            @endforeach
        @endforeach
    </section>

    <section class="content-section" id="simulations">
        <div class="section-heading"><div><span class="section-kicker">KUIS DARI GURU</span><h2>Kuis aktif</h2></div><span class="section-count">{{ $quizzes->count() }} KUIS</span></div>
        <div class="simulation-grid">
            @forelse ($quizzes as $quiz)
                <article class="simulation-item">
                    <span class="item-tag">{{ $quiz->subject?->code }}</span>
                    <h3>{{ $quiz->title }}</h3>
                    <p>{{ $quiz->description ?: $quiz->subject?->name }}</p>
                    <div class="item-meta"><span>{{ $quiz->questions_count }} soal</span><span>{{ $quiz->duration_minutes }} menit</span><span>{{ $quiz->max_attempts ? $quiz->user_attempts_count.'/'.$quiz->max_attempts.' percobaan' : $quiz->user_attempts_count.' percobaan' }}</span>@if ($quiz->user_best_score !== null)<span>terbaik {{ number_format((float) $quiz->user_best_score, 0) }}%</span>@endif</div>
                    <a class="text-link" href="{{ route('simulations.show', $quiz) }}">{{ $quiz->user_attempts_count > 0 ? 'Lihat kuis' : 'Kerjakan kuis' }} <span aria-hidden="true">&#8594;</span></a>
                </article>
            @empty
                <div class="empty-state"><strong>Belum ada kuis aktif.</strong><span>Kuis yang diterbitkan guru akan muncul di sini.</span></div>
            @endforelse
        </div>
    </section>

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">HASIL &amp; PEMBAHASAN</span><h2>Kuis terakhir</h2></div></div>
        @forelse ($recentAttempts as $attempt)
            <a class="list-row" href="{{ route('attempts.show', $attempt) }}"><div><strong>{{ $attempt->simulation?->title }}</strong><small>{{ $attempt->submitted_at?->format('d/m/Y H:i') }} · lihat pembahasan</small></div><span class="score-badge">{{ number_format((float) $attempt->score, 0) }}%</span></a>
        @empty
            <div class="empty-state"><strong>Belum ada kuis yang dikerjakan.</strong><span>Hasil dan pembahasan kuis akan tampil di sini.</span></div>
        @endforelse
    </section>
@endsection
