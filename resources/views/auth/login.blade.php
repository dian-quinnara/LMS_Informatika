@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <section class="auth-wrap">
        <div class="auth-intro">
            <span class="eyebrow">RUANG BELAJAR</span>
            <h1>Belajar dengan arah. <em>Tumbuh dengan percaya diri.</em></h1>
            <p>Satu ruang untuk materi, kelas, kehadiran, dan latihanmu.</p>
            <div class="intro-stamp"><span>01</span><span>Belajar, satu langkah setiap hari</span></div>
        </div>
        <div class="auth-panel">
            <span class="section-kicker">SELAMAT DATANG KEMBALI</span>
            <h2>Masuk ke akunmu</h2>
            <form method="POST" action="{{ route('login.store') }}" class="form-stack">
                @csrf
                <label class="field-label" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                <label class="field-label" for="password">Kata sandi</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <label class="check-line"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
                <button class="button button-primary button-wide" type="submit">Masuk <span aria-hidden="true">&#8594;</span></button>
            </form>
            <p class="auth-switch">Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a></p>
        </div>
    </section>
@endsection