@extends('layouts.app')

@section('title', $simulation->title)

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">{{ $simulation->subject->name }} · SIMULASI</span><h1>{{ $simulation->title }}</h1><p>{{ $simulation->description ?: 'Latihan untuk mengukur pemahamanmu.' }}</p></div>
        <a class="button button-quiet" href="{{ route('dashboard') }}">Kembali</a>
    </section>
    <section class="simulation-detail">
        <div class="detail-fact"><span>JUMLAH SOAL</span><strong>{{ $simulation->questions_count }}</strong></div>
        <div class="detail-fact"><span>BATAS WAKTU</span><strong>{{ $simulation->duration_minutes }} menit</strong></div>
        <div class="detail-action"><p>Jawaban akan dinilai otomatis setelah dikirim.</p><form method="POST" action="{{ route('simulations.begin', $simulation) }}">@csrf<button class="button button-primary" type="submit">Mulai simulasi <span aria-hidden="true">&#8594;</span></button></form></div>
    </section>
@endsection