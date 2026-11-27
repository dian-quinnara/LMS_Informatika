@php($announcement ??= null)
<label class="field-label" for="announcement-title">Judul</label>
<input id="announcement-title" name="title" value="{{ old('title', $announcement?->title) }}" maxlength="255" placeholder="Contoh: Jadwal kuis Searching" required>
<label class="field-label" for="announcement-body">Isi pengumuman</label>
<textarea id="announcement-body" name="body" rows="5" maxlength="5000" required>{{ old('body', $announcement?->body) }}</textarea>
<label class="field-label" for="announcement-class">Ditujukan untuk</label>
<select id="announcement-class" name="class_name">
    <option value="">Semua kelas</option>
    @foreach ($classes as $class)
        <option value="{{ $class }}" @selected(old('class_name', $announcement?->class_name) === $class)>Kelas {{ $class }}</option>
    @endforeach
</select>
<label class="check-line"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $announcement?->is_published ?? true))> Tampilkan kepada siswa</label>
