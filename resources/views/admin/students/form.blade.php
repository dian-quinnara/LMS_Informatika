@extends('layouts.app')

@section('title', $student->exists ? 'Edit siswa' : 'Tambah siswa')

@section('content')
    <section class="page-heading">
        <div>
            <span class="section-kicker">KELOLA SISWA · {{ $student->exists ? 'EDIT AKUN' : 'AKUN BARU' }}</span>
            <h1>{{ $student->exists ? $student->name : 'Tambah siswa' }}</h1>
            <p>{{ $student->exists ? 'Perbarui identitas, NIS, email login, dan kelas siswa.' : 'Akun baru memakai kata sandi awal yang wajib diganti saat login pertama.' }}</p>
        </div>
        <a class="button button-quiet" href="{{ route('admin.students.index') }}">Kembali ke Kelola Siswa</a>
    </section>

    @include('admin.partials.student-credential')

    <div class="management-grid">
        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index">01</span><div><span class="section-kicker">DATA SISWA</span><h2>{{ $student->exists ? 'Edit data' : 'Data akun' }}</h2></div></div>
            <form method="POST" action="{{ $student->exists ? route('admin.students.update', $student) : route('admin.students.store') }}" class="form-stack" data-single-submit>
                @csrf
                @if ($student->exists) @method('PUT') @endif
                <label class="field-label" for="student-name">Nama lengkap</label>
                <input id="student-name" name="name" value="{{ old('name', $student->name) }}" maxlength="255" required>
                <label class="field-label" for="student-nis">NIS</label>
                <input id="student-nis" name="nis" value="{{ old('nis', $student->nis) }}" maxlength="30" required>
                <label class="field-label" for="student-email">Email login</label>
                <input id="student-email" name="email" type="email" value="{{ old('email', $student->email) }}" maxlength="255" required>
                <label class="field-label" for="student-class">Kelas</label>
                <select id="student-class" name="class_name">
                    <option value="">Belum ada kelas</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class }}" @selected(old('class_name', $student->class_name) === $class)>{{ $class }}</option>
                    @endforeach
                </select>
                @unless ($student->exists)
                    <small class="optional">{{ $defaultPasswordSet ? 'Kata sandi awal: kata sandi default sekolah (dari .env).' : 'Kata sandi awal dibuat acak dan ditampilkan sekali setelah disimpan.' }}</small>
                @endunless
                <button class="button button-primary" type="submit">{{ $student->exists ? 'Simpan perubahan' : 'Buat akun siswa' }}</button>
            </form>
        </section>

        @if ($student->exists)
            <section class="form-panel">
                <div class="panel-heading"><span class="panel-index panel-index-coral">02</span><div><span class="section-kicker">AKUN &amp; KEAMANAN</span><h2>Reset kata sandi</h2></div></div>
                <form method="POST" action="{{ route('admin.students.password', $student) }}" class="form-stack">
                    @csrf
                    <label class="field-label" for="reset-mode">Kata sandi baru</label>
                    <select id="reset-mode" name="mode">
                        @if ($defaultPasswordSet)<option value="default">Kata sandi default sekolah</option>@endif
                        <option value="random">Acak (ditampilkan sekali)</option>
                    </select>
                    <label class="check-line"><input type="checkbox" name="confirm" value="1" required> Saya yakin mereset kata sandi {{ $student->name }}</label>
                    <small class="optional">Siswa wajib mengganti kata sandi saat login berikutnya. Status: {{ $student->must_change_password ? 'masih memakai kata sandi awal' : 'sudah memakai kata sandi sendiri' }}.</small>
                    <button class="button button-primary" type="submit">Reset kata sandi</button>
                </form>

                <form method="POST" action="{{ route('admin.students.status', $student) }}" class="form-stack">
                    @csrf
                    @method('PATCH')
                    <span class="field-label">Status akun: {{ $student->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    <button class="button button-quiet" type="submit">{{ $student->is_active ? 'Nonaktifkan akun' : 'Aktifkan akun' }}</button>
                </form>
            </section>
        @endif
    </div>
@endsection
