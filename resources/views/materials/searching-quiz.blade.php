@extends('layouts.app')

@section('title', $quiz['label'])

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">{{ $kicker ?? 'LATIHAN SEARCHING' }}</span><h1>{{ $quiz['label'] }}</h1><p>{{ $quiz['description'] }}</p></div>
        <a class="button button-quiet" href="{{ $backUrl ?? route('materi.searching') }}"><span aria-hidden="true">&#8592;</span> {{ $backLabel ?? 'Kembali ke Searching' }}</a>
    </section>
    <form method="POST" action="{{ $submitUrl ?? route($submitRoute) }}" class="question-stack" id="searching-quiz-form">
        @csrf
        <div class="section-heading">
            <div><span class="section-kicker">{{ $sectionKicker ?? 'SEQUENTIAL SEARCH & BINARY SEARCH' }}</span><h2>{{ $sectionTitle ?? 'sepuluh soal pilihan ganda' }}</h2></div>
            <span class="role-pill" id="quiz-progress">{{ count($quiz['questions']) }} SOAL</span>
        </div>
        @foreach ($quiz['questions'] as $index => $question)
            <fieldset class="question-panel">
                <legend><span class="question-number">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>{!! nl2br(e($question['question'])) !!}@isset($question['category']) <span class="{{ $question['category'] === 'HOTS' ? 'active-badge' : 'status-badge' }}">{{ $question['category'] }} · {{ ($question['type'] ?? 'mc') === 'tf' ? 'Benar/salah' : 'Pilihan ganda' }}</span>@endisset</legend>
                <div class="answer-options">
                    @foreach ($question['options'] as $key => $option)
                        <label class="answer-option">
                            <input type="radio" name="answers[{{ $index }}]" value="{{ $key }}">
                            <span class="answer-key">{{ $key }}</span>
                            <span>{{ $option }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach
        <button class="button button-primary" type="submit">Kirim jawaban <span aria-hidden="true">&#8594;</span></button>
    </form>
    <script src="{{ asset('js/searching-quiz.js') }}"></script>
@endsection
