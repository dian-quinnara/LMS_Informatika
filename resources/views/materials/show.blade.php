@extends('layouts.app')

@section('title', $material->title)

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">{{ $material->subject->name }} · {{ $material->subject->code }}</span><h1>{{ $material->title }}</h1><p>{{ $material->summary }}</p></div>
        <a class="button button-quiet" href="{{ route('dashboard') }}#materials">Kembali ke materi</a>
    </section>
    <article class="reading-panel">
        <span class="section-kicker">MATERI BELAJAR</span>
        <div class="reading-content">{{ $material->content ?: 'Belum ada isi materi yang ditambahkan.' }}</div>
        @if ($material->resource_url)
            <a class="button button-primary" href="{{ $material->resource_url }}" target="_blank" rel="noopener noreferrer">Buka sumber tambahan <span aria-hidden="true">&#8599;</span></a>
        @endif
    </article>
@endsection