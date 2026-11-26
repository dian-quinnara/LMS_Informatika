@extends('layouts.app')

@section('title', 'Impor data siswa')

@section('content')
    <section class="page-heading">
        <div>
            <span class="section-kicker">KELOLA KELAS · DATA SISWA</span>
            <h1>Impor data siswa</h1>
            <p>Tambahkan akun siswa dan tetapkan kelasnya dari file Excel atau CSV.</p>
        </div>
        <a class="button button-quiet" href="{{ route('dashboard') }}#kelas-x">Kembali ke Kelola Kelas</a>
    </section>

    <div class="management-grid">
        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index">01</span><div><span class="section-kicker">LANGKAH PERTAMA</span><h2>Unduh template</h2></div></div>
            <div class="form-stack">
                <p class="field-label">Kolom: {{ implode(', ', \App\Services\StudentImporter::HEADERS) }}</p>
                <small class="optional">Kelas harus salah satu dari {{ implode(', ', $classes) }}. NIS dan email harus unik.</small>
                <div class="button-group">
                    <a class="button button-primary" href="{{ route('admin.students.import.template', 'xlsx') }}">Template Excel (.xlsx)</a>
                    <a class="button button-quiet" href="{{ route('admin.students.import.template', 'csv') }}">Template CSV</a>
                    <a class="button button-quiet" href="{{ route('admin.students.import.example') }}">Unduh contoh Excel</a>
                </div>
                <small class="optional">{{ $studentCount }} siswa terdaftar · {{ $unassignedCount }} belum memiliki kelas.</small>
            </div>
        </section>

        <section class="form-panel">
            <div class="panel-heading"><span class="panel-index panel-index-yellow">02</span><div><span class="section-kicker">LANGKAH KEDUA</span><h2>Unggah file</h2></div></div>
            <form method="POST" action="{{ route('admin.students.import.preview') }}" enctype="multipart/form-data" class="form-stack">
                @csrf
                <label class="field-label" for="student-file">File data siswa (.xlsx atau .csv, maks. 2 MB)</label>
                <input id="student-file" name="file" type="file" accept=".xlsx,.csv,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
                <small class="optional">File diperiksa dulu. Belum ada data yang disimpan sebelum Anda menekan "Simpan". {{ $defaultPasswordSet ? 'Akun baru memakai kata sandi default sekolah' : 'Akun baru mendapat kata sandi acak' }} dan wajib diganti saat login pertama. Siswa yang NIS-nya sudah terdaftar diganti emailnya sesuai file.</small>
                <button class="button button-primary" type="submit">Periksa dan tampilkan pratinjau</button>
            </form>
        </section>

        <section class="form-panel" id="kata-sandi-awal">
            <div class="panel-heading"><span class="panel-index panel-index-coral">03</span><div><span class="section-kicker">KATA SANDI AWAL</span><h2>Buat ulang kata sandi awal</h2></div></div>
            <form method="POST" action="{{ route('admin.students.initial-passwords.reset') }}" class="form-stack" data-single-submit>
                @csrf
                <small class="optional">Untuk siswa yang belum pernah login dan mengganti kata sandi, misalnya bila file kredensial hilang. Siswa yang sudah memakai kata sandi sendiri tidak terpengaruh.</small>
                <label class="field-label" for="reset-class">Kelas</label>
                <select id="reset-class" name="class_name">
                    <option value="">Semua kelas · {{ $resettableCounts->sum() }} siswa</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class }}" @selected(old('class_name') === $class) @disabled(($resettableCounts[$class] ?? 0) === 0)>{{ $class }} · {{ $resettableCounts[$class] ?? 0 }} siswa</option>
                    @endforeach
                </select>
                <label class="field-label" for="reset-mode">Jenis kata sandi awal</label>
                <select id="reset-mode" name="mode">
                    <option value="default" @selected(old('mode', $defaultPasswordSet ? 'default' : 'random') === 'default') @disabled(! $defaultPasswordSet)>Kata sandi default sekolah{{ $defaultPasswordSet ? '' : ' (belum diatur di .env)' }}</option>
                    <option value="random" @selected(old('mode', $defaultPasswordSet ? 'default' : 'random') === 'random')>Acak 10 karakter per siswa</option>
                </select>
                <label class="check-line"><input type="checkbox" name="confirm" value="1" required> Kata sandi awal lama tidak berlaku lagi setelah dibuat ulang.</label>
                <button class="button button-primary" type="submit" @disabled($resettableCounts->sum() === 0)>Buat ulang kata sandi awal</button>
            </form>
        </section>
    </div>

    @if ($passwordReset)
        <section class="content-section">
            <div class="section-heading">
                <div><span class="section-kicker">KATA SANDI AWAL BARU{{ $passwordReset['class_name'] ? ' · KELAS '.$passwordReset['class_name'] : '' }}</span><h2>Kredensial siswa</h2></div>
                <span class="section-count">{{ count($passwordReset['rows']) }} SISWA</span>
            </div>
            <section class="token-reveal" role="status">
                <div>
                    <span class="section-kicker">KATA SANDI AWAL</span>
                    <p>Siswa login dengan email masing-masing. Kata sandi acak hanya ditampilkan sekali; kata sandi default sekolah tidak ditampilkan ulang.</p>
                    <small>Siswa wajib mengganti kata sandi saat login pertama.</small>
                </div>
                <a class="button button-quiet" href="data:text/csv;charset=utf-8;base64,{{ $passwordReset['credentials_csv'] }}" download="kredensial-siswa.csv">Unduh kredensial (.csv)</a>
            </section>
            @foreach ($passwordReset['rows'] as $row)
                <article class="session-row">
                    <div><strong>{{ $row['name'] }} · {{ $row['class_name'] ?: 'tanpa kelas' }}</strong><small>NIS {{ $row['nis'] ?: '–' }} · login {{ $row['email'] }} · @if ($row['password']) kata sandi awal <code>{{ $row['password'] }}</code> @else kata sandi default sekolah @endif</small></div>
                    <span class="status-badge">Dibuat ulang</span>
                </article>
            @endforeach
        </section>
    @endif

    @if ($preview !== null)
        @php
            $validRows = collect($preview)->whereNull('errors.0');
            $invalidRows = collect($preview)->whereNotNull('errors.0');
        @endphp
        <section class="content-section">
            <div class="section-heading">
                <div><span class="section-kicker">PRATINJAU · {{ $pending['filename'] }}</span><h2>Periksa data</h2></div>
                <span class="section-count">{{ $validRows->count() }} SIAP · {{ $invalidRows->count() }} GAGAL</span>
            </div>
            @foreach ($preview as $row)
                <article class="session-row">
                    <div>
                        <strong>Baris {{ $row['line'] }} · {{ $row['name'] ?: '(tanpa nama)' }}</strong>
                        <small>NIS {{ $row['nis'] ?: '–' }} · {{ $row['email'] ?: '–' }} · Kelas {{ $row['class_name'] ?: '–' }}</small>
                        @if (! empty($row['previous_email']))
                            <small class="optional">Email login diganti dari {{ $row['previous_email'] }}</small>
                        @endif
                        @foreach ($row['errors'] as $error)
                            <small class="optional">✕ {{ $error }}</small>
                        @endforeach
                    </div>
                    @if ($row['errors'] !== [])
                        <span class="draft-badge">Gagal</span>
                    @elseif ($row['action'] === 'update')
                        <span class="active-badge">{{ ! empty($row['previous_email']) ? 'Ganti email' : 'Perbarui kelas' }}</span>
                    @else
                        <span class="status-badge">Akun baru</span>
                    @endif
                </article>
            @endforeach
            <div class="button-group">
                <form method="POST" action="{{ route('admin.students.import.store') }}" data-single-submit>
                    @csrf
                    <input type="hidden" name="import_id" value="{{ $pending['id'] }}">
                    <button class="button button-primary button-wide" type="submit" @disabled($validRows->isEmpty())>Simpan {{ $validRows->count() }} siswa valid</button>
                </form>
                <form method="POST" action="{{ route('admin.students.import.destroy') }}">
                    @csrf
                    @method('DELETE')
                    <button class="button button-quiet button-wide" type="submit">Batalkan</button>
                </form>
            </div>
            @if ($invalidRows->isNotEmpty())
                <small class="optional">Baris yang gagal tidak akan disimpan. Perbaiki di file lalu unggah ulang.</small>
            @endif
        </section>
    @endif

    @if ($result)
        <section class="content-section">
            <div class="section-heading">
                <div><span class="section-kicker">HASIL IMPOR · {{ $result['filename'] }}</span><h2>Akun baru</h2></div>
                <span class="section-count">{{ count($result['created']) }} AKUN</span>
            </div>
            @if ($result['credentials_csv'])
                <section class="token-reveal" role="status">
                    <div>
                        <span class="section-kicker">AKUN LOGIN SISWA</span>
                        <p>Siswa login dengan email masing-masing. Kata sandi acak hanya ditampilkan sekali; kata sandi default sekolah tidak ditampilkan ulang.</p>
                        <small>Siswa wajib mengganti kata sandi saat login pertama.</small>
                    </div>
                    <a class="button button-quiet" href="data:text/csv;charset=utf-8;base64,{{ $result['credentials_csv'] }}" download="kredensial-siswa.csv">Unduh kredensial (.csv)</a>
                </section>
            @endif
            @forelse ($result['created'] as $row)
                <article class="session-row">
                    <div><strong>{{ $row['name'] }} · {{ $row['class_name'] }}</strong><small>NIS {{ $row['nis'] }} · login {{ $row['email'] }} · @if ($row['password']) kata sandi awal <code>{{ $row['password'] }}</code> @else kata sandi default sekolah @endif</small></div>
                    <span class="status-badge">Dibuat</span>
                </article>
            @empty
                <div class="empty-state"><strong>Tidak ada akun baru.</strong><span>Semua baris valid adalah siswa yang sudah terdaftar.</span></div>
            @endforelse
        </section>

        @if ($result['updated'] !== [])
            <section class="content-section">
                <div class="section-heading"><div><span class="section-kicker">HASIL IMPOR</span><h2>Siswa diperbarui</h2></div><span class="section-count">{{ count($result['updated']) }} SISWA</span></div>
                @foreach ($result['updated'] as $row)
                    <article class="session-row">
                        <div><strong>{{ $row['name'] }} · {{ $row['class_name'] }}</strong><small>NIS {{ $row['nis'] }} · login {{ $row['email'] }}@if (! empty($row['previous_email'])) (sebelumnya {{ $row['previous_email'] }})@endif · @if (! empty($row['password_reset'])) @if ($row['password']) kata sandi awal <code>{{ $row['password'] }}</code> @else kata sandi awal direset ke default sekolah @endif @else kata sandi milik siswa tidak diubah @endif</small></div>
                        <span class="active-badge">{{ ! empty($row['previous_email']) ? 'Email diganti' : 'Kelas diperbarui' }}</span>
                    </article>
                @endforeach
            </section>
        @endif

        @if ($result['failed'] !== [])
            <section class="content-section">
                <div class="section-heading">
                    <div><span class="section-kicker">HASIL IMPOR</span><h2>Baris gagal</h2></div>
                    <div class="button-group">
                        <span class="section-count">{{ count($result['failed']) }} BARIS</span>
                        <a class="button button-quiet" href="{{ route('admin.students.import.errors') }}">Unduh laporan kesalahan (.csv)</a>
                    </div>
                </div>
                @foreach ($result['failed'] as $row)
                    <article class="session-row">
                        <div>
                            <strong>Baris {{ $row['line'] }} · {{ $row['name'] ?: '(tanpa nama)' }}</strong>
                            <small>{{ implode(' ', $row['errors']) }}</small>
                        </div>
                        <span class="draft-badge">Gagal</span>
                    </article>
                @endforeach
            </section>
        @endif
    @endif

    @include('admin.partials.copy-token-script')
@endsection
