@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
    @include('student.partials.heading', ['kicker' => 'PROFIL SAYA', 'title' => 'Profil saya', 'description' => 'Identitas akun dan pengaturan keamanan. Data kelas hanya dapat diubah oleh admin.'])

    <div class="management-grid">
        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index">01</span><div><span class="section-kicker">IDENTITAS SISWA</span><h2>Data diri</h2></div></div>
            <div class="form-stack">
                <div class="list-row"><div><strong>Nama</strong><small>{{ $user->name }}</small></div></div>
                <div class="list-row"><div><strong>NIS</strong><small>{{ $user->nis ?? 'Belum diatur' }}</small></div></div>
                <div class="list-row"><div><strong>Kelas</strong><small>{{ $user->class_name ?? 'Belum diatur' }}</small></div><span class="draft-badge">Diatur admin</span></div>
                <div class="list-row"><div><strong>Email login</strong><small>{{ $user->email }}</small></div></div>
                <small class="optional">Jika nama, NIS, kelas, atau email tidak sesuai, hubungi admin sekolah. Siswa tidak dapat mengubah kelasnya sendiri.</small>
            </div>
        </section>

        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index panel-index-coral">02</span><div><span class="section-kicker">PENGATURAN AKUN</span><h2>Keamanan</h2></div></div>
            <div class="form-stack">
                <small class="optional">Gunakan kata sandi minimal 8 karakter berisi huruf dan angka, berbeda dari kata sandi default sekolah dan tidak memuat NIS.</small>
                <a class="button button-primary" href="{{ route('password.edit') }}">Ganti kata sandi</a>
                <div class="list-row"><div><strong>Akun dibuat</strong><small>{{ $user->created_at?->format('d M Y') }}</small></div></div>
                <div class="list-row"><div><strong>Aktivitas</strong><small>{{ $attendanceCount }} presensi · {{ $completedAttempts }} kuis selesai</small></div></div>
            </div>
        </section>
    </div>
@endsection
