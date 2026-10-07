@extends('layouts.app')

@section('title', 'Mengerjakan simulasi')

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">{{ $attempt->simulation->subject->name }} · {{ $attempt->simulation->duration_minutes }} MENIT</span><h1>{{ $attempt->simulation->title }}</h1><p>Pilih satu jawaban untuk setiap pertanyaan.</p></div>
        <span class="role-pill">{{ $attempt->simulation->questions->count() }} SOAL</span>
    </section>
    <form method="POST" action="{{ route('attempts.submit', $attempt) }}" class="question-stack">
        @csrf
        @foreach ($attempt->simulation->questions as $question)
            <fieldset class="question-panel">
                <legend><span class="question-number">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>{{ $question->prompt }}</legend>
                <div class="answer-options">
                    @foreach ($question->choices as $key => $choice)
                        <label class="answer-option"><input type="radio" name="answers[{{ $question->id }}]" value="{{ $key }}"><span class="answer-key">{{ $key }}</span><span>{{ $choice }}</span></label>
                    @endforeach
                </div>
            </fieldset>
        @endforeach
        <button class="button button-primary" type="submit">Kirim jawaban <span aria-hidden="true">&#8594;</span></button>
    </form>
@endsection