@php($material ??= null)
<label class="field-label" for="material-subject">Topik / mata pelajaran</label>
<select id="material-subject" name="subject_id" required>
    <option value="">Pilih topik</option>
    @foreach ($subjects as $subject)
        <option value="{{ $subject->id }}" @selected((int) old('subject_id', $material?->subject_id) === $subject->id)>{{ $subject->name }}</option>
    @endforeach
</select>
<label class="field-label" for="material-title">Judul materi</label>
<input id="material-title" name="title" value="{{ old('title', $material?->title) }}" maxlength="255" placeholder="Judul bahan ajar" required>
<label class="field-label" for="material-summary">Ringkasan <span class="optional">OPSIONAL</span></label>
<input id="material-summary" name="summary" value="{{ old('summary', $material?->summary) }}" maxlength="255" placeholder="Satu kalimat pengantar">
<label class="field-label" for="material-content">Isi materi</label>
<textarea id="material-content" name="content" rows="5" placeholder="Tulis materi belajar di sini">{{ old('content', $material?->content) }}</textarea>
<div class="form-inline">
    <div>
        <label class="field-label" for="material-order">Urutan</label>
        <input id="material-order" name="sort_order" type="number" min="0" max="9999" value="{{ old('sort_order', $material?->sort_order ?? 0) }}">
    </div>
    <div>
        <label class="field-label" for="material-url">Tautan sumber <span class="optional">OPSIONAL</span></label>
        <input id="material-url" name="resource_url" type="url" value="{{ old('resource_url', $material?->resource_url) }}" placeholder="https://">
    </div>
</div>
<label class="field-label" for="material-attachment">Gambar, video, atau dokumen <span class="optional">OPSIONAL · MAKS. 20 MB</span></label>
<input id="material-attachment" name="attachment" type="file" accept=".jpg,.jpeg,.png,.gif,.webp,.mp4,.webm,.pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx">
@if ($material?->attachment_path)
    <small class="optional">Lampiran saat ini: <a class="text-link" href="{{ route('admin.content.materials.attachment', $material) }}" target="_blank" rel="noopener">{{ $material->attachment_name }}</a>. Unggah file baru untuk mengganti.</small>
    <label class="check-line"><input type="checkbox" name="remove_attachment" value="1"> Hapus lampiran</label>
@endif
<label class="check-line"><input type="checkbox" name="is_published" value="1" @checked(old('is_published', $material?->is_published ?? true))> Terbitkan untuk siswa</label>
