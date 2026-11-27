@if (session('student_credential'))
    @php($credential = session('student_credential'))
    <section class="token-reveal" role="status">
        <div>
            <span class="section-kicker">AKUN LOGIN SISWA</span>
            <p>{{ $credential['name'] }} · login dengan email <strong>{{ $credential['email'] }}</strong></p>
            <small>{{ $credential['password'] === null ? 'Kata sandi: kata sandi default sekolah. ' : 'Kata sandi acak hanya ditampilkan sekali. ' }}Siswa wajib mengganti kata sandi saat login pertama.</small>
        </div>
        @if ($credential['password'] !== null)
            <div class="button-group">
                <code>{{ $credential['password'] }}</code>
                <button class="button button-quiet" type="button" data-copy-token="{{ $credential['password'] }}">Salin</button>
            </div>
        @endif
    </section>
    @include('admin.partials.copy-token-script')
@endif
