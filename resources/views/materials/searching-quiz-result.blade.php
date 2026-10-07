@extends('layouts.app')

@section('title', 'Hasil '.$quiz['label'])

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">HASIL LATIHAN SEARCHING</span><h1>{{ $quiz['label'] }}</h1><p>Periksa jawaban benar dan pembahasan untuk setiap soal.</p></div>
        <div class="control-buttons">
            <a class="button button-quiet" href="{{ route($retryRoute) }}">Kerjakan lagi</a>
            <a class="button button-quiet" href="{{ route('materi.searching') }}"><span aria-hidden="true">&#8592;</span> Kembali ke Searching</a>
        </div>
    </section>
    <section class="result-banner">
        <span>TOTAL SKOR</span>
        <strong>{{ $score }}<small>%</small></strong>
        <p>{{ $correctCount }} dari {{ count($reviews) }} jawaban benar</p>
    </section>
    <section class="content-section">
        <div class="section-heading">
            <div><span class="section-kicker">TINJAUAN JAWABAN</span><h2>Pembahasan setiap soal</h2></div>
            <span class="section-count">{{ count($reviews) }} SOAL</span>
        </div>
        @foreach ($reviews as $index => $review)
            <fieldset class="question-panel" disabled>
                <legend><span class="question-number">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>{{ $review['question'] }}</legend>
                <div class="answer-options">
                    @foreach ($review['options'] as $key => $option)
                        <label class="answer-option {{ $key === $review['answer'] ? 'is-correct' : ($key === $review['selected'] ? 'is-picked' : '') }}">
                            <input type="radio" name="review[{{ $index }}]" value="{{ $key }}" @checked($key === $review['selected'])>
                            <span class="answer-key">{{ $key }}</span>
                            <span>{{ $option }}</span>
                            @if ($key === $review['answer'])
                                <span class="status-badge">Jawaban benar</span>
                            @elseif ($key === $review['selected'])
                                <span class="draft-badge">Pilihanmu</span>
                            @endif
                        </label>
                    @endforeach
                </div>
                <div class="review-summary">
                    <p>Jawabanmu: <strong>{{ $review['selected'] ?? 'Tidak dijawab' }}</strong> · Jawaban benar: <strong>{{ $review['answer'] }}</strong></p>
                    <span class="{{ $review['is_correct'] ? 'status-badge' : 'draft-badge' }}">{{ $review['is_correct'] ? 'Benar' : 'Belum tepat' }}</span>
                </div>
                <div class="explanation-panel review-explanation">
                    <strong>PEMBAHASAN</strong>
                    <p>{{ $review['explanation'] }}</p>
                </div>
            </fieldset>
        @endforeach
    </section>
@endsection
