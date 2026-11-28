@extends('layouts.app')

@section('title', 'Progres Belajar')

@section('content')
    @include('student.partials.heading', ['kicker' => 'PROGRES BELAJAR', 'title' => 'Progres belajar', 'description' => 'Materi yang sudah dan belum dipelajari, simulasi yang sudah dicoba, nilai latihan dan kuis, serta materi yang perlu dipelajari ulang.'])

    @php
        $review = $topics->where('needs_review', true);
    @endphp

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">PERLU DIULANG</span><h2>Materi yang perlu dipelajari ulang</h2></div><span class="section-count">KKM {{ $passing }}</span></div>
        @foreach ($review as $topic)
            <a class="session-row" href="{{ $topic['url'] }}">
                <div><strong>{{ $topic['title'] }}</strong><small>Nilai latihan terakhir di bawah KKM. Baca ulang penjelasan, coba simulasinya, lalu kerjakan latihan lagi.</small></div>
                <span class="draft-badge">Rata-rata {{ $topic['average'] }}</span>
            </a>
        @endforeach
        @foreach ($quizReview as $row)
            <article class="session-row">
                <div><strong>{{ $row['quiz'] }}</strong><small>Nilai kuis terakhir di bawah KKM.</small></div>
                <span class="draft-badge">Nilai {{ number_format($row['last'], 0) }}</span>
            </article>
        @endforeach
        @if ($review->isEmpty() && $quizReview->isEmpty())
            <div class="empty-state"><strong>Belum ada materi yang perlu diulang.</strong><span>Materi dengan nilai latihan di bawah {{ $passing }} akan muncul di sini.</span></div>
        @endif
    </section>

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">MATERI</span><h2>Sudah dan belum dipelajari</h2></div><span class="section-count">{{ $topics->where('status', 'sudah')->count() }}/{{ $topics->count() }} SELESAI</span></div>
        @foreach ($topics as $topic)
            <a class="session-row" href="{{ $topic['url'] }}">
                <div><strong>{{ $topic['title'] }}</strong><small>{{ $topic['visited'] }}/{{ $topic['total'] }} bagian dibuka · {{ implode(', ', $topic['items']) }}</small></div>
                <span class="{{ $topic['status'] === 'sudah' ? 'status-badge' : ($topic['status'] === 'sebagian' ? 'active-badge' : 'draft-badge') }}">{{ match ($topic['status']) { 'sudah' => 'Sudah dipelajari', 'sebagian' => 'Sebagian', default => 'Belum dipelajari' } }}</span>
            </a>
        @endforeach
    </section>

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">SIMULASI</span><h2>Simulasi yang sudah dicoba</h2></div></div>
        @foreach ($topics->filter(fn ($topic) => $topic['simulation'] !== null) as $topic)
            <a class="session-row" href="{{ $topic['simulation']['url'] }}">
                <div><strong>{{ $topic['simulation']['label'] }}</strong><small>{{ $topic['simulation_activity'] ? 'Terakhir dicoba '.$topic['simulation_activity']->last_activity_at?->format('d/m/Y H:i').' · '.$topic['simulation_activity']->visits.' kali' : 'Belum pernah dicoba' }}</small></div>
                <span class="{{ $topic['simulation_activity'] ? 'status-badge' : 'draft-badge' }}">{{ $topic['simulation_activity'] ? 'Sudah dicoba' : 'Belum dicoba' }}</span>
            </a>
        @endforeach
    </section>

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">NILAI</span><h2>Nilai latihan dan kuis</h2></div></div>
        @foreach ($topics as $topic)
            @foreach ($topic['practice_rows'] as $practice)
                <article class="session-row">
                    <div><strong>{{ $practice['label'] }}</strong><small>{{ $topic['title'] }}{{ $practice['activity'] ? ' · dikerjakan '.$practice['activity']->visits.' kali' : ' · belum dikerjakan' }}</small></div>
                    @if ($practice['activity'])
                        <span class="{{ $practice['activity']->last_score >= $passing ? 'status-badge' : 'draft-badge' }}">Terakhir {{ $practice['activity']->last_score }} · terbaik {{ $practice['activity']->best_score }}</span>
                    @else
                        <span class="draft-badge">Belum ada nilai</span>
                    @endif
                </article>
            @endforeach
        @endforeach
        @foreach ($quizResults as $row)
            <article class="session-row">
                <div><strong>{{ $row['quiz'] }}</strong><small>Kuis dari guru · {{ $row['attempts'] }} percobaan</small></div>
                <span class="{{ $row['last'] >= $passing ? 'status-badge' : 'draft-badge' }}">Terakhir {{ number_format($row['last'], 0) }} · terbaik {{ number_format($row['best'], 0) }}</span>
            </article>
        @endforeach
    </section>
@endsection
