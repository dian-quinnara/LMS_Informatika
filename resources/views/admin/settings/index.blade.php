@extends('layouts.app')

@section('title', 'Pengaturan sistem')

@section('content')
    <section class="page-heading">
        <div><span class="section-kicker">PENGATURAN SISTEM</span><h1>Pengaturan sistem</h1><p>Identitas website, profil admin, keamanan akun, dan pemeliharaan data.</p></div>
    </section>

    <div class="management-grid">
        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index">01</span><div><span class="section-kicker">IDENTITAS WEBSITE</span><h2>Nama &amp; sekolah</h2></div></div>
            <form method="POST" action="{{ route('admin.settings.identity') }}" class="form-stack">
                @csrf
                @method('PUT')
                <label class="field-label" for="site-name">Nama website</label>
                <input id="site-name" name="site_name" value="{{ old('site_name', $settings['site_name']) }}" maxlength="60" required>
                <label class="field-label" for="school-name">Nama sekolah <span class="optional">OPSIONAL</span></label>
                <input id="school-name" name="school_name" value="{{ old('school_name', $settings['school_name']) }}" maxlength="120" placeholder="Tampil di bagian atas halaman">
                <label class="field-label" for="site-tagline">Slogan <span class="optional">OPSIONAL</span></label>
                <input id="site-tagline" name="site_tagline" value="{{ old('site_tagline', $settings['site_tagline']) }}" maxlength="160">
                <button class="button button-primary" type="submit">Simpan identitas</button>
            </form>
        </section>

        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index panel-index-yellow">02</span><div><span class="section-kicker">PROFIL ADMIN</span><h2>Data akun</h2></div></div>
            <form method="POST" action="{{ route('admin.settings.profile') }}" class="form-stack">
                @csrf
                @method('PUT')
                <label class="field-label" for="admin-name">Nama</label>
                <input id="admin-name" name="name" value="{{ old('name', auth()->user()->name) }}" maxlength="255" required>
                <label class="field-label" for="admin-email">Email login</label>
                <input id="admin-email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" maxlength="255" required>
                <label class="field-label" for="admin-current-password">Kata sandi saat ini</label>
                <input id="admin-current-password" name="current_password" type="password" autocomplete="current-password" required>
                <button class="button button-primary" type="submit">Simpan profil</button>
            </form>
        </section>

        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index panel-index-coral">03</span><div><span class="section-kicker">KEAMANAN AKUN</span><h2>Kata sandi</h2></div></div>
            <div class="form-stack">
                <small class="optional">Gunakan kata sandi minimal 8 karakter berisi huruf dan angka. Jangan memakai kata sandi default sekolah.</small>
                <a class="button button-primary" href="{{ route('password.edit') }}">Ganti kata sandi admin</a>
                <small class="optional">Login dibatasi 5 percobaan per menit. Akun siswa yang dinonaktifkan langsung dikeluarkan dari sesi.</small>
            </div>
        </section>

        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index panel-index-blue">04</span><div><span class="section-kicker">PEMELIHARAAN</span><h2>Backup data</h2></div></div>
            <div class="form-stack">
                <small class="optional">Unduh salinan data aplikasi (JSON). Kata sandi dan token presensi tidak ikut diekspor. Untuk cadangan lengkap server gunakan juga mysqldump dari Laragon/hosting.</small>
                <a class="button button-primary" href="{{ route('admin.settings.backup') }}">Unduh backup data</a>
            </div>
        </section>
    </div>

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">PENGELOLAAN DATA</span><h2>Isi database</h2></div></div>
        @foreach ($counts as $table => $count)
            <div class="list-row"><div><strong>{{ $table }}</strong><small>tabel aplikasi</small></div><span class="row-mark">{{ $count }}</span></div>
        @endforeach
    </section>

    <section class="content-section">
        <div class="section-heading"><div><span class="section-kicker">INFORMASI SISTEM</span><h2>Lingkungan aplikasi</h2></div></div>
        @foreach ($environment as $label => $value)
            <div class="list-row"><div><strong>{{ $label }}</strong><small>{{ $value }}</small></div></div>
        @endforeach
    </section>
@endsection
