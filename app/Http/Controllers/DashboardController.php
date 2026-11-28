<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Attendance;
use App\Models\AttendanceToken;
use App\Models\Material;
use App\Models\Simulation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Beranda: Dashboard Admin untuk admin, Beranda siswa untuk siswa.
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            // Tautan lama ?section=... diarahkan ke menu siswa yang baru.
            $legacy = ['attendance' => 'student.attendance', 'materials' => 'materi.index', 'simulations' => 'student.practice'];
            if (is_string($request->query('section')) && isset($legacy[$request->query('section')])) {
                return redirect()->route($legacy[$request->query('section')]);
            }

            return app(StudentPortalController::class)->home($request);
        }

        $stats = [
            'students' => User::query()->where('role', 'student')->count(),
            'classes' => count(config('attendance.grade_x_classes', [])),
            'attendance_today' => Attendance::query()->whereDate('attended_on', today())->count(),
            'active_sessions' => AttendanceToken::query()->gradeX()->withStatus(AttendanceToken::STATUS_ACTIVE)->count(),
            'materials' => Material::query()->where('is_published', true)->count(),
            'simulations' => Simulation::query()->count(),
        ];

        return view('dashboard', [
            'user' => $user,
            'stats' => $stats,
            ...$this->adminSummary(),
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
