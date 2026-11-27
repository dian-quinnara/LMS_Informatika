<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Answer;
use App\Models\Attempt;
use App\Models\Simulation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Menu 7 · Hasil & Analisis Belajar: nilai kuis, hasil per kelas, keberhasilan soal,
 * materi tersulit, riwayat simulasi, dan siswa yang perlu pendampingan.
 */
class LearningResultsController extends Controller
{
    public function index(Request $request): View
    {
        $classes = config('attendance.grade_x_classes', []);
        $passing = (int) config('students.passing_score', 70);
        $filters = [
            'class' => in_array($request->query('class'), $classes, true) ? $request->query('class') : null,
            'quiz' => $request->integer('quiz') ?: null,
        ];

        $scope = function (Builder $query) use ($filters): void {
            $query->when($filters['quiz'], fn (Builder $query, int $quiz) => $query->where('simulation_id', $quiz))
                ->when($filters['class'], fn (Builder $query, string $class) => $query->whereHas('user', fn (Builder $query) => $query->where('class_name', $class)));
        };

        $completed = Attempt::query()
            ->where('status', 'completed')
            ->tap($scope)
            ->with(['user:id,name,nis,class_name', 'simulation:id,title,subject_id'])
            ->latest('submitted_at')
            ->get();

        $answers = Answer::query()
            ->whereHas('attempt', fn (Builder $query) => $query->where('status', 'completed')->tap($scope))
            ->with(['question:id,simulation_id,bank_question_id,category,prompt', 'question.simulation:id,title,subject_id', 'question.simulation.subject:id,name', 'question.bankQuestion:id,material_id', 'question.bankQuestion.material:id,title'])
            ->get(['id', 'attempt_id', 'question_id', 'is_correct']);

        return view('admin.results.index', [
            'classes' => $classes,
            'quizzes' => Simulation::query()->orderBy('title')->get(['id', 'title']),
            'filters' => $filters,
            'passing' => $passing,
            'summary' => [
                'attempts' => $completed->count(),
                'students' => $completed->pluck('user_id')->unique()->count(),
                'average' => round((float) $completed->avg('score'), 1),
                'passed' => $completed->where('score', '>=', $passing)->count(),
            ],
            'scores' => $completed->take(100),
            'byClass' => $this->byClass($completed, $classes),
            'questionStats' => $this->questionStats($answers),
            'difficultMaterials' => $this->difficultMaterials($answers),
            'activity' => Attempt::query()->tap($scope)->with(['user:id,name,class_name', 'simulation:id,title'])->latest()->take(30)->get(),
            'needsMentoring' => $this->needsMentoring($completed, $passing),
        ]);
    }

    /**
     * @param  Collection<int, Attempt>  $completed
     * @param  list<string>  $classes
     */
    private function byClass(Collection $completed, array $classes): Collection
    {
        $grouped = $completed->groupBy(fn (Attempt $attempt) => $attempt->user?->class_name);

        return collect($classes)->map(fn (string $class): array => [
            'class' => $class,
            'attempts' => $grouped->get($class, collect())->count(),
            'students' => $grouped->get($class, collect())->pluck('user_id')->unique()->count(),
            'average' => round((float) $grouped->get($class, collect())->avg('score'), 1),
        ]);
    }

    /**
     * Tingkat keberhasilan setiap soal (persentase jawaban benar), dari yang tersulit.
     *
     * @param  Collection<int, Answer>  $answers
     */
    private function questionStats(Collection $answers): Collection
    {
        return $answers->groupBy('question_id')->map(function (Collection $group): array {
            $question = $group->first()->question;

            return [
                'prompt' => $question?->prompt ?? 'Soal dihapus',
                'quiz' => $question?->simulation?->title,
                'category' => $question?->category,
                'total' => $group->count(),
                'correct' => $group->where('is_correct', true)->count(),
                'rate' => round($group->where('is_correct', true)->count() / max(1, $group->count()) * 100),
            ];
        })->sortBy('rate')->values()->take(50);
    }

    /**
     * Materi dengan persentase jawaban benar terendah (soal dari bank soal memakai materinya,
     * soal lain dikelompokkan per topik kuis).
     *
     * @param  Collection<int, Answer>  $answers
     */
    private function difficultMaterials(Collection $answers): Collection
    {
        return $answers->groupBy(function (Answer $answer): string {
            $question = $answer->question;

            return $question?->bankQuestion?->material?->title
                ?? 'Topik '.($question?->simulation?->subject?->name ?? 'lainnya');
        })->map(fn (Collection $group, string $material): array => [
            'material' => $material,
            'total' => $group->count(),
            'wrong' => $group->where('is_correct', false)->count(),
            'rate' => round($group->where('is_correct', true)->count() / max(1, $group->count()) * 100),
        ])->sortBy('rate')->values()->take(10);
    }

    /**
     * Siswa dengan rata-rata nilai di bawah KKM.
     *
     * @param  Collection<int, Attempt>  $completed
     */
    private function needsMentoring(Collection $completed, int $passing): Collection
    {
        return $completed->groupBy('user_id')->map(fn (Collection $group): array => [
            'student' => $group->first()->user,
            'attempts' => $group->count(),
            'average' => round((float) $group->avg('score'), 1),
            'lowest' => round((float) $group->min('score'), 1),
        ])->filter(fn (array $row): bool => $row['student'] !== null && $row['average'] < $passing)
            ->sortBy('average')
            ->values();
    }
}
