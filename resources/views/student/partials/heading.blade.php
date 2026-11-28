{{-- Judul halaman menu siswa + identitas (nama, NIS, kelas). Kelas hanya diatur admin. --}}
<section class="page-heading">
    <div>
        <span class="section-kicker">{{ $kicker ?? 'PORTAL SISWA' }} · {{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
        <h1>{{ $title }}</h1><p>{{ $description }}</p>
    </div>
    <div class="info-stat" aria-label="Identitas siswa">
        <span class="info-label">{{ $user->name }}{{ $user->nis ? ' · NIS '.$user->nis : '' }}</span>
        <strong>{{ $user->class_name ? 'Kelas '.$user->class_name : 'Kelas belum diatur' }}</strong>
    </div>
</section>
@if ($user->class_name === null)
    <div class="warning-message" role="alert">
        <strong>Kelasmu belum diatur.</strong>
        <p>Hubungi admin agar akunmu dimasukkan ke kelas XE1–XE9 sebelum melakukan presensi. Kelas tidak dapat dipilih sendiri oleh siswa.</p>
    </div>
@endif
