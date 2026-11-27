{{-- Daftar siswa dengan kotak centang + pilihan kelas tujuan untuk memindahkan siswa. --}}
<form method="POST" action="{{ route('admin.classes.move') }}" class="form-stack">
    @csrf
    @method('PATCH')
    @foreach ($members as $member)
        <label class="session-row">
            <span class="check-line">
                <input type="checkbox" name="user_ids[]" value="{{ $member->id }}" @checked(in_array($member->id, old('user_ids', [])))>
                <span><strong>{{ $member->name }}</strong><br><small>NIS {{ $member->nis ?? '—' }} · {{ $member->email }}</small></span>
            </span>
            @isset($member->is_active)
                <span class="{{ $member->is_active ? 'status-badge' : 'draft-badge' }}">{{ $member->is_active ? 'Aktif' : 'Nonaktif' }}</span>
            @endisset
        </label>
    @endforeach
    <div class="form-panel form-stack">
        <label class="field-label" for="move-class">Pindahkan siswa terpilih ke</label>
        <select id="move-class" name="class_name">
            @foreach ($classNames as $class)
                @continue($class === $currentClass)
                <option value="{{ $class }}">Kelas {{ $class }}</option>
            @endforeach
            @if ($currentClass !== null)<option value="">Kosongkan kelas</option>@endif
        </select>
        <button class="button button-primary" type="submit">Pindahkan siswa</button>
    </div>
</form>
