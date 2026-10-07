@extends('layouts.app')

@section('title', 'Daftar')

@section('content')
    <section class="auth-wrap auth-register">
        <div class="auth-intro">
            <span class="eyebrow">MULAI DI SINI</span>
            <h1>Ruang baru untuk <em>rasa ingin tahu.</em></h1>
            <p>Catat progresmu, buka materi, dan uji pemahaman kapan saja.</p>
            <div class="intro-stamp"><span>02</span><span>Perjalanan belajar dimulai dari sini</span></div>
        </div>
        <div class="auth-panel">
            <span class="section-kicker">BUAT AKUN</span>
            <h2>Daftar ke Ruang Belajar</h2>
            <form method="POST" action="{{ route('register.store') }}" class="form-stack">
                @csrf
                <label class="field-label" for="name">Nama lengkap</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required autofocus>
                <label class="field-label" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                <label class="field-label" for="password">Kata sandi</label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                <label class="field-label" for="password_confirmation">Ulangi kata sandi</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                <button class="button button-primary button-wide" type="submit">Buat akun <span aria-hidden="true">&#8594;</span></button>
            </form>
            <p class="auth-switch">Sudah terdaftar? <a href="{{ route('login') }}">Masuk</a></p>
        </div>
    </section>
@endsection