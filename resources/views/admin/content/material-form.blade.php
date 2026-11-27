@extends('layouts.app')

@section('title', 'Edit materi')

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">KELOLA MATERI · EDIT</span><h1>{{ $material->title }}</h1><p>Perbarui isi, urutan, lampiran, dan status publikasi materi.</p></div>
        <a class="button button-quiet" href="{{ route('admin.content.index') }}">Kembali ke Materi &amp; Simulasi</a>
    </section>

    <section class="form-panel">
        <div class="panel-heading"><span class="panel-index panel-index-yellow">01</span><div><span class="section-kicker">BAHAN AJAR</span><h2>Edit materi</h2></div></div>
        <form method="POST" action="{{ route('admin.content.materials.update', $material) }}" class="form-stack" enctype="multipart/form-data" data-single-submit>
            @csrf
            @method('PUT')
            @if ($material->slug)
                <small class="optional">Materi bawaan (slug: {{ $material->slug }}). Isinya berformat khusus untuk halaman pelajaran; ubah dengan hati-hati.</small>
            @endif
            @include('admin.content.partials.material-fields')
            <button class="button button-primary" type="submit">Simpan perubahan</button>
        </form>
    </section>
@endsection
