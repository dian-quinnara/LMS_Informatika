@extends('layouts.app')

@section('title', $simulation['label'])

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">SIMULASI {{ $topic['title'] }}</span><h1>{{ $simulation['label'] }}</h1><p>{{ $simulation['description'] }}</p></div>
        <a class="button button-quiet" href="{{ route('materi.'.$slug) }}"><span aria-hidden="true">&#8592;</span> Kembali ke {{ ucwords(strtolower($topic['title'])) }}</a>
    </section>

    <section class="content-section">
        <article class="simulator-panel" id="ordering-activity" data-steps='@json($simulation['steps'])'>
            <div class="simulator-section">
                <div class="section-heading"><div><span class="section-kicker">PETUNJUK</span><h2>Susun urutan yang benar</h2></div><span class="section-count" id="ordering-status">{{ count($simulation['steps']) }} LANGKAH</span></div>
                <div class="explanation-panel"><p>{{ $simulation['instruction'] }}</p></div>
            </div>
            <div class="simulator-section">
                <div class="question-stack" id="ordering-list" @if (! empty($simulation['monospace'])) style="font-family: ui-monospace, Consolas, monospace" @endif></div>
            </div>
            <div class="simulator-section">
                <div class="control-buttons">
                    <button class="button button-primary" type="button" id="ordering-check">Periksa urutan</button>
                    <button class="button button-quiet" type="button" id="ordering-shuffle">Acak ulang</button>
                    <button class="button button-quiet" type="button" id="ordering-answer">Lihat jawaban</button>
                </div>
            </div>
            <div class="simulator-section">
                <div class="explanation-panel" id="ordering-feedback" role="status" aria-live="polite"><p>Belum diperiksa.</p></div>
            </div>
        </article>
    </section>
    <script src="{{ asset('js/ordering-activity.js') }}"></script>
@endsection
