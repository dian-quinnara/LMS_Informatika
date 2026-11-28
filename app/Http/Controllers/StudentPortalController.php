<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AttendanceToken;
use App\Models\LearningActivity;
use App\Models\Simulation;
use App\Models\User;
use App\Support\LearningCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Menu siswa: Beranda, Presensi, Materi & Simulasi, Latihan & Kuis, Progres Belajar,
 * Pengumuman, dan Profil Saya.
 */
class StudentPortalController extends Controller
{
    public function home(Request $request): View
    {
        $user = $request->user();
        $sessions = $this->sessionsFor($user)
            ->where('expires_at', '>=', now())
            ->where('starts_at', '<=', now()->endOfDay())
            ->orderBy('starts_at')
            ->get();
        $attendedIds = $this->attendedIds($user, $sessions);
        $progress = LearningCatalog::progressFor($user->id);
        $quizzes = $this->publishedQuizzes($user);
        $attempts = $user->attempts()->where('status', 'completed')->get(['id', 'score']);
        $practiceScores = $progress->flatMap(fn (array $topic) => $topic['practice_rows']->pluck('activity')->filter()->pluck('last_score'));
        $allScores = $practiceScores->merge($attempts->pluck('score')->map(fn ($score) => (float) $score));

        return view('student.home', [
            'user' => $user,
            'todaySessions' => $sessions,
            'attendedIds' => $attendedIds,
            'attendedToday' => $user->attendances()->whereDate('attended_on', today())->count(),
            'lastMaterial' => LearningActivity::tableReady()
                ? LearningActivity::query()->where('user_id', $user->id)->where('type', LearningActivity::TYPE_MATERIAL)->latest('last_activity_at')->first()
                : null,
            'pendingPractices' => $progress->flatMap(fn (array $topic) => $topic['practice_rows']
                ->filter(fn (array $practice) => $practice['activity'] === null)
                ->map(fn (array $practice) => [...$practice, 'topic' => $topic['title']])),
            'pendingQuizzes' => $quizzes->filter(fn (Simulation $quiz) => $quiz->user_attempts_count === 0),
            'summary' => [
                'studied' => $progress->where('status', 'sudah')->count(),
                'topics' => $progress->count(),
                'simulations' => $progress->filter(fn (array $topic) => $topic['simulation_activity'] !== null)->count(),
                'simulation_total' => $progress->filter(fn (array $topic) => $topic['simulation'] !== null)->count(),
                'average' => $allScores->isEmpty() ? null : (int) round($allScores->avg()),
                'review' => $progress->where('needs_review', true)->count(),
            ],
            'announcements' => Announcement::query()->visibleTo($user->class_name)->latest()->take(3)->get(),
        ]);
    }

    public function attendance(Request $request): View
    {
        $user = $request->user();
        $sessions = $this->sessionsFor($user)->with('subject')->where('expires_at', '>=', now())->orderBy('starts_at')->get();

        return view('student.attendance', [
            'user' => $user,
            'attendanceTokens' => $sessions,
            'attendedTokenIds' => $this->attendedIds($user, $sessions),
            'attendanceHistory' => $user->attendances()->with(['subject', 'attendanceToken'])->latest()->take(20)->get(),
        ]);
    }

    public function materials(Request $request): View
    {
        return view('student.materials', [
            'user' => $request->user(),
            'topics' => LearningCatalog::progressFor($request->user()->id),
        ]);
    }

    public function practice(Request $request): View
    {
        $user = $request->user();

        return view('student.practice', [
            'user' => $user,
            'topics' => LearningCatalog::progressFor($user->id),
            'quizzes' => $this->publishedQuizzes($user),
            'recentAttempts' => $user->attempts()->where('status', 'completed')->with('simulation:id,title')->latest('submitted_at')->take(10)->get(),
            'passing' => (int) config('students.passing_score', 70),
        ]);
    }

    public function progress(Request $request): View
    {
        $user = $request->user();
        $passing = (int) config('students.passing_score', 70);
        $quizResults = $user->attempts()->where('status', 'completed')->with('simulation:id,title')->latest('submitted_at')->get()
            ->groupBy('simulation_id')
            ->map(fn (EloquentCollection $group): array => [
                'quiz' => $group->first()->simulation?->title ?? 'Kuis',
                'attempts' => $group->count(),
                'last' => (float) $group->first()->score,
                'best' => (float) $group->max('score'),
            ])->values();

        return view('student.progress', [
            'user' => $user,
            'topics' => LearningCatalog::progressFor($user->id),
            'quizResults' => $quizResults,
            'quizReview' => $quizResults->filter(fn (array $row) => $row['last'] < $passing),
            'passing' => $passing,
        ]);
    }

    public function announcements(Request $request): View
    {
        return view('student.announcements', [
            'user' => $request->user(),
            'announcements' => Announcement::query()->visibleTo($request->user()->class_name)->latest()->get(),
        ]);
    }

    public function profile(Request $request): View
    {
        $user = $request->user();

        return view('student.profile', [
            'user' => $user,
            'attendanceCount' => $user->attendances()->count(),
            'completedAttempts' => $user->attempts()->where('status', 'completed')->count(),
        ]);
    }

    /**
     * Sesi presensi umum (per mata pelajaran) dan sesi kelas siswa sendiri.
     *
     * @return Builder<AttendanceToken>
     */
    private function sessionsFor(User $user): Builder
    {
        return AttendanceToken::query()->where(function (Builder $query) use ($user): void {
            $query->whereNull('class_name');

            if ($user->class_name !== null) {
                $query->orWhere('class_name', $user->class_name);
            }
        });
    }

    /**
     * @param  Collection<int, AttendanceToken>  $sessions
     * @return list<int>
     */
    private function attendedIds(User $user, Collection $sessions): array
    {
        return $user->attendances()->whereIn('attendance_token_id', $sessions->modelKeys())->pluck('attendance_token_id')->all();
    }

    /**
     * Kuis/simulasi terbit yang punya soal, beserta jumlah percobaan siswa ini.
     *
     * @return EloquentCollection<int, Simulation>
     */
    private function publishedQuizzes(User $user): EloquentCollection
    {
        return Simulation::query()
            ->with('subject:id,name,code')
            ->withCount(['questions', 'attempts as user_attempts_count' => fn (Builder $query) => $query->where('user_id', $user->id)])
            ->withMax(['attempts as user_best_score' => fn (Builder $query) => $query->where('user_id', $user->id)->where('status', 'completed')], 'score')
            ->where('is_published', true)
            ->whereHas('questions')
            ->latest()
            ->get();
    }
}
