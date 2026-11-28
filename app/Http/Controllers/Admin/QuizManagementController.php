<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankQuestion;
use App\Models\Simulation;
use App\Models\Subject;
use App\Support\StoredAttachment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menu 6 · Kelola Soal & Kuis: bank soal per materi (LOTS/HOTS), kunci & pembahasan,
 * kuis dari bank soal, serta waktu dan batas percobaan.
 */
class QuizManagementController extends Controller
{
    private const CHOICE_KEYS = ['A', 'B', 'C', 'D'];

    public function index(Request $request): View
    {
        $materialOptions = BankQuestion::materialOptions();
        $material = (string) $request->query('material', '');
        $filters = [
            'material' => $materialOptions->contains(fn (array $options) => array_key_exists($material, $options)) ? $material : null,
            'category' => in_array($request->query('category'), BankQuestion::CATEGORIES, true) ? $request->query('category') : null,
        ];

        $questions = BankQuestion::query()
            ->with('material:id,title,subject_id')
            ->withCount('quizQuestions')
            ->when($filters['material'], function (Builder $query, string $key): void {
                [$type, $value] = explode(':', $key, 2);
                $type === 'topic' ? $query->where('topic', $value) : $query->where('material_id', (int) $value);
            })
            ->when($filters['category'], fn (Builder $query, string $category) => $query->where('category', $category))
            ->orderBy('topic')
            ->orderBy('material_id')
            ->latest()
            ->get();

        return view('admin.quizzes.index', [
            'materialOptions' => $materialOptions,
            'subjects' => Subject::query()->orderBy('name')->get(['id', 'name']),
            'questions' => $questions,
            'filters' => $filters,
            'categoryCounts' => BankQuestion::query()->selectRaw('category, count(*) as total')->groupBy('category')->pluck('total', 'category'),
            'quizzes' => Simulation::query()->with('subject:id,name')->withCount(['questions', 'attempts'])->latest()->get(),
            'choiceKeys' => self::CHOICE_KEYS,
        ]);
    }

    public function storeQuestion(Request $request): RedirectResponse
    {
        $question = new BankQuestion([...$this->validateQuestion($request), 'created_by' => $request->user()->id]);
        $this->applyAttachment($question, $request);
        $question->save();

        return redirect()->route('admin.quizzes.index')->with('status', 'Soal berhasil ditambahkan ke bank soal.');
    }

    public function editQuestion(BankQuestion $bankQuestion): View
    {
        return view('admin.quizzes.question-form', [
            'question' => $bankQuestion,
            'materialOptions' => BankQuestion::materialOptions(),
            'choiceKeys' => self::CHOICE_KEYS,
        ]);
    }

    public function updateQuestion(Request $request, BankQuestion $bankQuestion): RedirectResponse
    {
        $oldPath = $bankQuestion->attachment_path;
        $bankQuestion->fill($this->validateQuestion($request));
        $this->applyAttachment($bankQuestion, $request);
        $bankQuestion->save();

        if ($oldPath !== $bankQuestion->attachment_path) {
            BankQuestion::deleteAttachmentIfUnused($oldPath);
        }

        return redirect()->route('admin.quizzes.index')->with('status', 'Soal di bank soal diperbarui. Kuis yang sudah dibuat tetap memakai salinan soal lama.');
    }

    public function destroyQuestion(BankQuestion $bankQuestion): RedirectResponse
    {
        // Soal kuis yang sudah disalin tetap ada (bank_question_id menjadi null), sehingga nilai siswa tidak hilang.
        $path = $bankQuestion->attachment_path;
        $bankQuestion->delete();
        BankQuestion::deleteAttachmentIfUnused($path);

        return redirect()->route('admin.quizzes.index')->with('status', 'Soal dihapus dari bank soal.');
    }

    /**
     * Buat kuis baru dengan menyalin soal terpilih dari bank soal.
     */
    public function storeQuiz(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:240'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:20'],
            'question_ids' => ['required', 'array', 'min:1', 'max:100'],
            'question_ids.*' => ['integer', 'distinct', 'exists:bank_questions,id'],
        ], [
            'question_ids.required' => 'Pilih minimal satu soal dari bank soal.',
        ]);
        $published = $request->boolean('is_published');

        $quiz = DB::transaction(function () use ($validated, $published): Simulation {
            $quiz = Simulation::query()->create([
                'subject_id' => $validated['subject_id'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'duration_minutes' => $validated['duration_minutes'],
                'max_attempts' => $validated['max_attempts'] ?? null,
                'is_published' => $published,
                'published_at' => $published ? now() : null,
            ]);

            $bank = BankQuestion::query()->whereIn('id', $validated['question_ids'])->get()->keyBy('id');
            foreach (array_values($validated['question_ids']) as $position => $id) {
                $source = $bank[$id];
                $quiz->questions()->create([
                    'bank_question_id' => $source->id,
                    'category' => $source->category,
                    'prompt' => $source->prompt,
                    'choices' => $source->choices,
                    'correct_answer' => $source->correct_answer,
                    'explanation' => $source->explanation,
                    'points' => $source->points,
                    'position' => $position + 1,
                    // Soal kuis memakai file lampiran yang sama; file dihapus hanya bila tidak dipakai lagi.
                    'attachment_path' => $source->attachment_path,
                    'attachment_name' => $source->attachment_name,
                    'attachment_mime' => $source->attachment_mime,
                ]);
            }

            return $quiz;
        });

        return redirect()->route('admin.quizzes.index')->with('status', 'Kuis "'.$quiz->title.'" dibuat dengan '.count($validated['question_ids']).' soal'.($published ? ' dan diterbitkan.' : ' sebagai draf.'));
    }

    /**
     * Atur waktu, batas percobaan, dan status publikasi kuis.
     */
    public function updateQuiz(Request $request, Simulation $simulation): RedirectResponse
    {
        $validated = $request->validate([
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:240'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);
        $published = $request->boolean('is_published');
        $simulation->update([
            'duration_minutes' => $validated['duration_minutes'],
            'max_attempts' => $validated['max_attempts'] ?? null,
            'is_published' => $published,
            'published_at' => $published ? ($simulation->published_at ?? now()) : null,
        ]);

        return back()->with('status', 'Pengaturan kuis "'.$simulation->title.'" disimpan.');
    }

    public function questionAttachment(BankQuestion $bankQuestion): StreamedResponse
    {
        return StoredAttachment::stream($bankQuestion->attachment_path, $bankQuestion->attachment_name, $bankQuestion->attachment_mime);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateQuestion(Request $request): array
    {
        $materialKeys = BankQuestion::materialOptions()->flatMap(fn (array $options) => array_keys($options))->all();
        $validated = $request->validate([
            'material' => ['required', 'string', Rule::in($materialKeys)],
            'category' => ['required', Rule::in(BankQuestion::CATEGORIES)],
            'prompt' => ['required', 'string', 'max:2000'],
            'choices' => ['required', 'array:'.implode(',', self::CHOICE_KEYS), 'size:4'],
            'choices.*' => ['required', 'string', 'max:255'],
            'correct_answer' => ['required', Rule::in(self::CHOICE_KEYS)],
            'explanation' => ['nullable', 'string', 'max:3000'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
            'attachment' => ['nullable', 'file', 'mimes:'.BankQuestion::ATTACHMENT_MIMES, 'max:'.BankQuestion::ATTACHMENT_MAX_KB],
        ], [
            'material.required' => 'Pilih materi untuk soal ini.',
            'material.in' => 'Pilih materi dari daftar yang tersedia.',
            'category.in' => 'Kategori harus LOTS atau HOTS.',
            'attachment.mimes' => 'File soal harus berupa PDF, Word (doc/docx), atau gambar (jpg, png, gif, webp).',
            'attachment.max' => 'Ukuran file soal maksimal 10 MB.',
        ]);
        [$type, $value] = explode(':', $validated['material'], 2);
        $validated['topic'] = $type === 'topic' ? $value : null;
        $validated['material_id'] = $type === 'material' ? (int) $value : null;
        $validated['choices'] = array_map('trim', $validated['choices']);
        unset($validated['material'], $validated['attachment']);

        return $validated;
    }

    /**
     * Ganti atau hapus lampiran soal. File lama dihapus oleh pemanggil bila tidak dipakai kuis.
     */
    private function applyAttachment(BankQuestion $question, Request $request): void
    {
        if ($request->hasFile('attachment')) {
            $question->fill(StoredAttachment::store($request->file('attachment'), 'question-bank'));
        } elseif ($request->boolean('remove_attachment')) {
            $question->fill(['attachment_path' => null, 'attachment_name' => null, 'attachment_mime' => null]);
        }
    }
}
