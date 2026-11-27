@extends('layouts.app')

@section('title', 'Edit pengumuman')

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">PENGUMUMAN · EDIT</span><h1>{{ $announcement->title }}</h1><p>Perbarui isi, sasaran kelas, atau status tampil pengumuman.</p></div>
        <a class="button button-quiet" href="{{ route('admin.announcements.index') }}">Kembali ke Pengumuman</a>
    </section>

    <section class="form-panel">
        <div class="panel-heading"><span class="panel-index">01</span><div><span class="section-kicker">INFORMASI SISWA</span><h2>Edit pengumuman</h2></div></div>
        <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}" class="form-stack">
            @csrf
            @method('PUT')
            @include('admin.announcements.partials.fields')
            <button class="button button-primary" type="submit">Simpan perubahan</button>
        </form>
    </section>
@endsection
