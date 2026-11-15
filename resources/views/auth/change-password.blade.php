@extends('layouts.app')

@section('title', 'Ganti kata sandi')

@section('content')
    <section class="page-heading">
        <div>
            <span class="section-kicker">{{ $user->must_change_password ? 'LOGIN PERTAMA' : 'AKUN' }}</span>
            <h1>Ganti kata sandi</h1>
            <p>
                @if ($user->must_change_password)
                    Akunmu memakai kata sandi awal dari admin. Buat kata sandi baru sebelum melanjutkan.
                @else
                    Perbarui kata sandi akunmu.
                @endif
            </p>
        </div>
    </section>

    <div class="management-grid">
        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index">01</span><div><span class="section-kicker">KEAMANAN AKUN</span><h2>Kata sandi baru</h2></div></div>
            <form method="POST" action="{{ route('password.update') }}" class="form-stack">
                @csrf
                @method('PUT')
                <label class="field-label" for="current-password">Kata sandi saat ini</label>
                <input id="current-password" name="current_password" type="password" autocomplete="current-password" required autofocus>
                <label class="field-label" for="new-password">Kata sandi baru</label>
                <input id="new-password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                <label class="field-label" for="new-password-confirmation">Ulangi kata sandi baru</label>
                <input id="new-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                <small class="optional">Minimal 8 karakter, berisi huruf dan angka.</small>
                <button class="button button-primary" type="submit">Simpan kata sandi</button>
            </form>
        </section>
    </div>
@endsection
