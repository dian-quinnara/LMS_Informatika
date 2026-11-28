@extends('layouts.app')

@section('title', 'Edit soal')

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">BANK SOAL · {{ $question->category }}</span><h1>Edit soal</h1><p>Perubahan hanya berlaku untuk kuis yang dibuat setelah ini; kuis lama memakai salinan soal sebelumnya.</p></div>
        <a class="button button-quiet" href="{{ route('admin.quizzes.index') }}">Kembali ke Soal &amp; Kuis</a>
    </section>

    <section class="form-panel">
        <div class="panel-heading"><span class="panel-index panel-index-blue">01</span><div><span class="section-kicker">BANK SOAL</span><h2>Edit soal</h2></div></div>
        <form method="POST" action="{{ route('admin.question-bank.update', $question) }}" class="form-stack" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.quizzes.partials.question-fields')
            <button class="button button-primary" type="submit">Simpan perubahan</button>
        </form>
        <form method="POST" action="{{ route('admin.question-bank.destroy', $question) }}" class="form-stack" onsubmit="return confirm('Hapus soal ini dari bank soal?')">
            @csrf
            @method('DELETE')
            <button class="button button-quiet" type="submit">Hapus dari bank soal</button>
        </form>
    </section>
@endsection
