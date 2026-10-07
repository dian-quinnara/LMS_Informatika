@extends('layouts.app')

@section('title', 'Hasil simulasi')

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">HASIL SIMULASI · {{ $attempt->simulation->subject->name }}</span><h1>{{ $attempt->simulation->title }}</h1><p>Dikerjakan {{ $attempt->submitted_at?->format('d M Y, H:i') }}</p></div>
        <a class="button button-quiet" href="{{ route('dashboard') }}">Kembali ke ringkasan</a>
    </section>
    <section class="result-banner"><span>NILAI AKHIR</span><strong>{{ number_format((float) $attempt->score, 0) }}<small>%</small></strong><p>{{ $attempt->answers->where('is_correct', true)->count() }} dari {{ $attempt->answers->count() }} jawaban benar</p></section>
    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">TINJAUAN</span><h2>Jawabanmu</h2></div></div>
        @foreach ($attempt->answers as $answer)
            <article class="list-row result-row"><div><strong>{{ $answer->question->prompt }}</strong><small>Jawabanmu: {{ $answer->answer ? ($answer->question->choices[$answer->answer] ?? 'Pilihan tidak tersedia') : 'Tidak dijawab' }}</small></div><span class="{{ $answer->is_correct ? 'status-badge' : 'draft-badge' }}">{{ $answer->is_correct ? 'Benar' : 'Belum tepat' }}</span></article>
        @endforeach
    </section>
@endsection