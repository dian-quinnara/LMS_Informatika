<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Attempt;
use App\Models\Attendance;
use App\Models\AttendanceToken;
use App\Models\Material;
use App\Models\Simulation;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $availableStudentSections = ['attendance', 'materials', 'simulations'];
        $requestedSection = $request->query('section', 'attendance');
        $studentSection = in_array($requestedSection, $availableStudentSections, true)
            ? $requestedSection
            : 'attendance';
        $subjects = Subject::query()->withCount(['materials', 'simulations'])->orderBy('name')->get();
        $learningTopics = config('learning_topics');
        $simulations = Simulation::query()
            ->with('subject')
            ->withCount('questions')
            ->where('is_published', true)
            ->whereHas('questions')
            ->latest()
            ->get();
        // Siswa hanya melihat sesi umum (per mata pelajaran) dan sesi kelasnya sendiri.
        $attendanceTokens = AttendanceToken::query()
            ->with('subject')
            ->where('expires_at', '>=', now())
            ->where(function (Builder $query) use ($user): void {
                $query->whereNull('class_name');

                if ($user->class_name !== null) {
                    $query->orWhere('class_name', $user->class_name);
                }
            })
            ->orderBy('starts_at')
            ->get();
        $adminSummary = $user->role === 'admin' ? $this->adminSummary() : [];
        $announcements = $user->role === 'admin' ? collect() : Announcement::query()
            ->visibleTo($user->class_name)
            ->latest()
            ->take(5)
            ->get();
        $attendanceHistory = $user->attendances()
            ->with(['subject', 'attendanceToken'])
            ->latest()
            ->take(10)
            ->get();
        $attendedTokenIds = $user->attendances()
            ->whereIn('attendance_token_id', $attendanceTokens->modelKeys())
            ->pluck('attendance_token_id')
            ->all();
        $attempts = $user->attempts()->with('simulation.subject')->latest()->take(5)->get();
        $stats = $user->role === 'admin' ? [
            'students' => User::query()->where('role', 'student')->count(),
            'classes' => count(config('attendance.grade_x_classes', [])),
            'attendance_today' => Attendance::query()->whereDate('attended_on', today())->count(),
            'active_sessions' => AttendanceToken::query()->gradeX()->withStatus(AttendanceToken::STATUS_ACTIVE)->count(),
            'materials' => Material::query()->where('is_published', true)->count(),
            'simulations' => Simulation::query()->count(),
        ] : [
            'subjects' => $subjects->count(),
            'simulations' => $simulations->count(),
            'completed' => $user->attempts()->where('status', 'completed')->count(),
            'attendance' => $user->attendances()->count(),
        ];

        return view('dashboard', [
            ...compact('user', 'studentSection', 'subjects', 'learningTopics', 'simulations', 'attendanceTokens', 'attendanceHistory', 'attendedTokenIds', 'attempts', 'stats', 'announcements'),
            ...$adminSummary,
        ]);
    }

    /**
     * Ringkasan Dashboard Admin: presensi hari ini, sesi aktif, dan hasil kuis.
     *
     * @return array<string, mixed>
     */
    private function adminSummary(): array
    {
        $classes = config('attendance.grade_x_classes', []);
        $studentCounts = User::query()->where('role', 'student')->whereIn('class_name', $classes)
            ->selectRaw('class_name, count(*) as total')->groupBy('class_name')->pluck('total', 'class_name');
        $todayCounts = Attendance::query()->whereDate('attended_on', today())
            ->join('users', 'users.id', '=', 'attendances.user_id')
            ->whereIn('users.class_name', $classes)
            ->selectRaw('users.class_name as class_name, count(distinct attendances.user_id) as total')
            ->groupBy('users.class_name')->pluck('total', 'class_name');
        $completedAttempts = Attempt::query()->where('status', 'completed');

        return [
            'todayByClass' => collect($classes)->map(fn (string $class): array => [
                'class' => $class,
                'students' => (int) ($studentCounts[$class] ?? 0),
                'present' => (int) ($todayCounts[$class] ?? 0),
            ]),
            'activeSessions' => AttendanceToken::query()->gradeX()->withStatus(AttendanceToken::STATUS_ACTIVE)
                ->withCount('attendances')->orderBy('expires_at')->get(),
            'quizSummary' => [
                'attempts' => (clone $completedAttempts)->count(),
                'average' => round((float) (clone $completedAttempts)->avg('score'), 1),
                'passed' => (clone $completedAttempts)->where('score', '>=', config('students.passing_score', 70))->count(),
                'passing_score' => (int) config('students.passing_score', 70),
            ],
            'latestAttempts' => Attempt::query()->where('status', 'completed')->with(['user:id,name,class_name', 'simulation:id,title'])
                ->latest('submitted_at')->take(5)->get(),
        ];
    }
}
