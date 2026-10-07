@extends('layouts.app')

@section('title', $topic['title'])

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">MATERI {{ $topic['number'] }}</span><h1>{{ $topic['title'] }}</h1><p>{{ $topic['description'] }}</p></div>
        <a class="button button-quiet" href="{{ route('materi.index') }}">Kembali ke materi</a>
    </section>
    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">DAFTAR TOPIK</span><h2>{{ $topic['title'] }}</h2></div></div>
        @foreach ($topic['topics'] as $subtopic)
            @if (isset($topic['topic_routes'][$subtopic]))
                <a class="list-row" href="{{ route($topic['topic_routes'][$subtopic]) }}">
                    <div><strong>{{ $subtopic }}</strong><small>Materi</small></div>
                    <span class="row-mark">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                </a>
            @else
                <article class="list-row">
                    <div><strong>{{ $subtopic }}</strong><small>Materi</small></div>
                    <span class="row-mark">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                </article>
            @endif
        @endforeach
        @if ($topic['title'] === 'SEARCHING')
            <a class="list-row" href="{{ route('materi.searching.simulator') }}">
                <div><strong>Simulasi</strong><small>Uji pemahaman algoritma pencarian</small></div>
                <span class="text-link">Lihat simulasi <span aria-hidden="true">&#8594;</span></span>
            </a>
            <a class="list-row" href="{{ route('materi.searching.quiz') }}">
                <div><strong>Latihan soal LOTS</strong><small>10 soal LOTS beserta skor dan pembahasan</small></div>
                <span class="text-link">Mulai latihan <span aria-hidden="true">&#8594;</span></span>
            </a>
            <a class="list-row" href="{{ route('materi.searching.hots-quiz') }}">
                <div><strong>Latihan soal HOTS</strong><small>10 soal HOTS beserta skor dan pembahasan</small></div>
                <span class="text-link">Mulai latihan <span aria-hidden="true">&#8594;</span></span>
            </a>
        @endif
    </section>
    <section class="content-section">
        <div class="control-buttons">
            <a class="button button-primary" href="{{ route('materi.searching.analysis') }}">Analisis</a>
        </div>
    </section>
@endsection