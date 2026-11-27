@php($question ??= null)
<label class="field-label" for="bank-material">Materi</label>
<select id="bank-material" name="material_id" required>
    <option value="">Pilih materi</option>
    @foreach ($materials as $material)
        <option value="{{ $material->id }}" @selected((int) old('material_id', $question?->material_id) === $material->id)>{{ $material->subject?->name }} · {{ $material->title }}</option>
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
