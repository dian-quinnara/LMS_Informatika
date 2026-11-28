@extends('layouts.app')

@section('title', $topic['title'])

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">MATERI {{ $topic['number'] }}</span><h1>{{ $topic['title'] }}</h1><p>{{ $topic['description'] }}</p></div>
        <a class="button button-quiet" href="{{ route('materi.index') }}">Kembali ke materi</a>
    </section>

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">{{ isset($topic['explanations']) ? 'PENJELASAN SINGKAT' : 'DAFTAR TOPIK' }}</span><h2>{{ $topic['title'] }}</h2></div></div>
        @foreach ($topic['topics'] as $subtopic)
            @if (isset($topic['topic_routes'][$subtopic]))
                <a class="list-row" href="{{ route($topic['topic_routes'][$subtopic]) }}">
                    <div><strong>{{ $subtopic }}</strong><small>Materi</small></div>
                    <span class="row-mark">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                </a>
            @else
                <article class="list-row">
                    <div><strong>{{ $subtopic }}</strong><small>{{ $topic['explanations'][$subtopic] ?? 'Materi' }}</small></div>
                    <span class="row-mark">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                </article>
            @endif
        @endforeach
    </section>

    @if (! empty($topic['cases']))
        <section class="content-section">
            <div class="section-heading"><div><span class="section-kicker">CONTOH KASUS</span><h2>Penerapan dalam kehidupan sehari-hari</h2></div></div>
            @foreach ($topic['cases'] as $case)
                <article class="explanation-panel">
                    <strong>{{ $case['title'] }}</strong>
                    <p>{{ $case['problem'] }}</p>
                    <p>{!! nl2br(e($case['solution'])) !!}</p>
                </article>
            @endforeach
        </section>
    @endif

    @if ($entry && ($entry['simulation'] || $entry['practices']))
        <section class="content-section">
            <div class="section-heading"><div><span class="section-kicker">PRAKTIK INTERAKTIF</span><h2>Simulasi &amp; latihan</h2></div></div>
            @if ($entry['simulation'])
                <a class="list-row" href="{{ $entry['simulation']['url'] }}">
                    <div><strong>Simulasi</strong><small>{{ $entry['simulation']['description'] }}</small></div>
                    <span class="text-link">Lihat simulasi <span aria-hidden="true">&#8594;</span></span>
                </a>
            @endif
            @foreach ($entry['practices'] as $practice)
                <a class="list-row" href="{{ $practice['url'] }}">
                    <div><strong>{{ $practice['label'] }}</strong><small>{{ $practice['count'] }} soal {{ implode(' & ', $practice['categories']) }} · {{ implode(', ', $practice['types']) }} · skor dan pembahasan</small></div>
                    <span class="text-link">Mulai latihan <span aria-hidden="true">&#8594;</span></span>
                </a>
            @endforeach
        </section>
    @endif

    @if ($slug === 'searching')
        <section class="content-section">
            <div class="control-buttons">
                <a class="button button-primary" href="{{ route('materi.searching.analysis') }}">Analisis</a>
            </div>
        </section>
    @endif
@endsection
