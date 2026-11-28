@extends('layouts.app')

@section('title', 'Materi & Simulasi')

@section('content')
    @include('student.partials.heading', ['kicker' => 'MATERI & SIMULASI', 'title' => 'Materi & Simulasi', 'description' => 'Materi Informatika yang dipelajari lewat visualisasi dan praktik interaktif: penjelasan singkat, simulasi, contoh kasus, dan latihan.'])

    <section class="content-section" id="materials">
        <div class="section-heading"><div><span class="section-kicker">BELAJAR INTERAKTIF</span><h2>Materi belajar Informatika</h2></div><span class="section-count">{{ $topics->count() }} MATERI</span></div>
        @foreach ($topics as $topic)
            <a class="material-row" href="{{ $topic['url'] }}">
                <div>
                    <span class="item-tag">MATERI {{ $topic['number'] }}</span>
                    <h3>{{ $topic['title'] }}</h3>
                    <p>{{ $topic['description'] }}</p>
                    <small>{{ implode(' · ', $topic['topics']) }}{{ $topic['simulation'] ? ' · simulasi' : '' }}{{ $topic['practices'] ? ' · latihan' : '' }}</small>
                </div>
                <span class="{{ $topic['status'] === 'sudah' ? 'status-badge' : ($topic['status'] === 'sebagian' ? 'active-badge' : 'text-link') }}">{{ match ($topic['status']) { 'sudah' => 'Sudah dipelajari', 'sebagian' => 'Lanjutkan', default => 'Mulai Belajar' } }} @if ($topic['status'] === 'belum')<span aria-hidden="true">&#8594;</span>@endif</span>
            </a>
        @endforeach
    </section>

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">PRAKTIK INTERAKTIF</span><h2>Simulasi</h2></div></div>
        @foreach ($topics->filter(fn ($topic) => $topic['simulation'] !== null) as $topic)
            <a class="list-row" href="{{ $topic['simulation']['url'] }}">
                <div><strong>{{ $topic['simulation']['label'] }}</strong><small>{{ $topic['simulation']['description'] }}</small></div>
                <span class="{{ $topic['simulation_activity'] ? 'status-badge' : 'text-link' }}">{{ $topic['simulation_activity'] ? 'Sudah dicoba' : 'Coba simulasi' }}</span>
            </a>
        @endforeach
    </section>
@endsection
