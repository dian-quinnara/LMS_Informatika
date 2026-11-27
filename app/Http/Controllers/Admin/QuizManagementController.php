<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankQuestion;
use App\Models\Material;
use App\Models\Simulation;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Menu 6 · Kelola Soal & Kuis: bank soal per materi (LOTS/HOTS), kunci & pembahasan,
 * kuis dari bank soal, serta waktu dan batas percobaan.
 */
class QuizManagementController extends Controller
{
    private const CHOICE_KEYS = ['A', 'B', 'C', 'D'];

    public function index(Request $request): View
    {
        $filters = [
            'material' => $request->integer('material') ?: null,
            'category' => in_array($request->query('category'), BankQuestion::CATEGORIES, true) ? $request->query('category') : null,
        ];

        $questions = BankQuestion::query()
            ->with('material:id,title,subject_id')
            ->withCount('quizQuestions')
            ->when($filters['material'], fn (Builder $query, int $id) => $query->where('material_id', $id))
            ->when($filters['category'], fn (Builder $query, string $category) => $query->where('category', $category))
            ->orderBy('material_id')
            ->latest()
            ->get();

        return view('admin.quizzes.index', [
            'materials' => Material::query()->with('subject:id,name')->orderBy('subject_id')->orderBy('sort_order')->get(['id', 'title', 'subject_id']),
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
        BankQuestion::query()->create([...$this->validateQuestion($request), 'created_by' => $request->user()->id]);

        return redirect()->route('admin.quizzes.index')->with('status', 'Soal berhasil ditambahkan ke bank soal.');
    }

    public function editQuestion(BankQuestion $bankQuestion): View
    {
        return view('admin.quizzes.question-form', [
            'question' => $bankQuestion,
            'materials' => Material::query()->with('subject:id,name')->orderBy('subject_id')->orderBy('sort_order')->get(['id', 'title', 'subject_id']),
            'choiceKeys' => self::CHOICE_KEYS,
        ]);
    }

    public function updateQuestion(Request $request, BankQuestion $bankQuestion): RedirectResponse
    {
        $bankQuestion->update($this->validateQuestion($request));

        return redirect()->route('admin.quizzes.index')->with('status', 'Soal di bank soal diperbarui. Kuis yang sudah dibuat tetap memakai salinan soal lama.');
    }

    public function destroyQuestion(BankQuestion $bankQuestion): RedirectResponse
    {
        // Soal kuis yang sudah disalin tetap ada (bank_question_id menjadi null), sehingga nilai siswa tidak hilang.
        $bankQuestion->delete();

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

    /**
     * @return array<string, mixed>
     */
    private function validateQuestion(Request $request): array
    {
        $validated = $request->validate([
            'material_id' => ['required', 'integer', 'exists:materials,id'],
            'category' => ['required', Rule::in(BankQuestion::CATEGORIES)],
            'prompt' => ['required', 'string', 'max:2000'],
            'choices' => ['required', 'array:'.implode(',', self::CHOICE_KEYS), 'size:4'],
            'choices.*' => ['required', 'string', 'max:255'],
            'correct_answer' => ['required', Rule::in(self::CHOICE_KEYS)],
            'explanation' => ['nullable', 'string', 'max:3000'],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
        ], [
            'material_id.required' => 'Pilih materi untuk soal ini.',
            'category.in' => 'Kategori harus LOTS atau HOTS.',
        ]);
        $validated['choices'] = array_map('trim', $validated['choices']);

        return $validated;
    }
}
