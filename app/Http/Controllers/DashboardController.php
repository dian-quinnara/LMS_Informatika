<?php

namespace App\Http\Controllers;

use App\Models\AttendanceToken;
use App\Models\Material;
use App\Models\Simulation;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
        $classManagement = $user->role === 'admin' ? $this->classManagementData($request) : [];
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
            'subjects' => $subjects->count(),
            'materials' => Material::query()->count(),
            'simulations' => Simulation::query()->count(),
        ] : [
            'subjects' => $subjects->count(),
            'simulations' => $simulations->count(),
            'completed' => $user->attempts()->where('status', 'completed')->count(),
            'attendance' => $user->attendances()->count(),
        ];

        return view('dashboard', [
            ...compact('user', 'studentSection', 'subjects', 'learningTopics', 'simulations', 'attendanceTokens', 'attendanceHistory', 'attendedTokenIds', 'attempts', 'stats'),
            ...$classManagement,
        ]);
    }

    /**
     * Data halaman Kelola Kelas: sesi presensi kelas X, filter, dan roster siswa.
     *
     * @return array<string, mixed>
     */
    private function classManagementData(Request $request): array
    {
        $classes = config('attendance.grade_x_classes', []);
        $statusLabels = AttendanceToken::STATUS_LABELS;
        $filters = [
            'class' => in_array($request->query('class'), $classes, true) ? $request->query('class') : null,
            'status' => array_key_exists((string) $request->query('status'), $statusLabels) ? $request->query('status') : null,
            'date_from' => $this->validDate($request->query('date_from')),
            'date_to' => $this->validDate($request->query('date_to')),
        ];

        $attendanceSessions = AttendanceToken::query()
            ->gradeX()
            ->withCount('attendances')
            ->when($filters['class'], fn (Builder $query, string $class) => $query->where('class_name', $class))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->withStatus($status))
            ->when($filters['date_from'], fn (Builder $query, string $date) => $query->where('starts_at', '>=', Carbon::parse($date)->startOfDay()))
            ->when($filters['date_to'], fn (Builder $query, string $date) => $query->where('starts_at', '<=', Carbon::parse($date)->endOfDay()))
            ->orderByDesc('starts_at')
            ->limit(50)
            ->get();

        $students = User::query()
            ->where('role', 'student')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'class_name']);

        return [
            'gradeXClasses' => $classes,
            'statusLabels' => $statusLabels,
            'sessionFilters' => $filters,
            'attendanceSessions' => $attendanceSessions,
            'classStudents' => $students,
            'rosterCounts' => $students->whereNotNull('class_name')->countBy('class_name'),
        ];
    }

    private function validDate(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        try {
            Carbon::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        return $value;
    }
}
