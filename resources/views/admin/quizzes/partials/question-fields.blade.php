@php($question ??= null)
<label class="field-label" for="bank-material">Topik / materi</label>
<select id="bank-material" name="material" required>
    <option value="">Pilih materi yang ada</option>
    @foreach ($materialOptions as $group => $options)
        <optgroup label="{{ $group }}">
            @foreach ($options as $value => $label)
                <option value="{{ $value }}" @selected(old('material', $question?->materialKey()) === $value)>{{ $label }}</option>
            @endforeach
        </optgroup>
    @endforeach
</select>
<div class="form-inline">
    <div>
        <label class="field-label" for="bank-category">Kategori</label>
        <select id="bank-category" name="category" required>
            @foreach (\App\Models\BankQuestion::CATEGORIES as $category)
                <option value="{{ $category }}" @selected(old('category', $question?->category) === $category)>{{ $category }}{{ $category === 'LOTS' ? ' · berpikir tingkat rendah' : ' · berpikir tingkat tinggi' }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="field-label" for="bank-points">Poin</label>
        <input id="bank-points" name="points" type="number" min="1" max="100" value="{{ old('points', $question?->points ?? 1) }}" required>
    </div>
</div>
<label class="field-label" for="bank-prompt">Pertanyaan</label>
<textarea id="bank-prompt" name="prompt" rows="3" required placeholder="Tulis pertanyaan">{{ old('prompt', $question?->prompt) }}</textarea>
<label class="field-label" for="bank-attachment">File soal: PDF, Word, atau gambar <span class="optional">OPSIONAL · MAKS. 10 MB</span></label>
<input id="bank-attachment" name="attachment" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/*">
@if ($question?->attachment_path)
    <small class="optional">File saat ini: <a class="text-link" href="{{ route('admin.question-bank.attachment', $question) }}" target="_blank" rel="noopener">{{ $question->attachment_name }}</a>. Unggah file baru untuk mengganti.</small>
    <label class="check-line"><input type="checkbox" name="remove_attachment" value="1"> Hapus file soal</label>
@endif
<div class="choice-grid">
    @foreach ($choiceKeys as $choice)
        <div><label class="field-label" for="bank-choice-{{ strtolower($choice) }}">Pilihan {{ $choice }}</label><input id="bank-choice-{{ strtolower($choice) }}" name="choices[{{ $choice }}]" value="{{ old('choices.'.$choice, $question?->choices[$choice] ?? '') }}" maxlength="255" required></div>
    @endforeach
</div>
<label class="field-label" for="bank-answer">Kunci jawaban</label>
<select id="bank-answer" name="correct_answer">
    @foreach ($choiceKeys as $choice)
        <option value="{{ $choice }}" @selected(old('correct_answer', $question?->correct_answer) === $choice)>{{ $choice }}</option>
    @endforeach
</select>
<label class="field-label" for="bank-explanation">Pembahasan <span class="optional">OPSIONAL</span></label>
<textarea id="bank-explanation" name="explanation" rows="3" placeholder="Jelaskan mengapa jawaban tersebut benar">{{ old('explanation', $question?->explanation) }}</textarea>
