<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Menu 4 · Kelola Presensi: buat sesi harian per kelas, token, dan daftar sesi.
 */
class AttendanceManagementController extends Controller
{
    public function index(Request $request): View
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

        $rosterCounts = User::query()
            ->where('role', 'student')
            ->whereIn('class_name', $classes)
            ->selectRaw('class_name, count(*) as total')
            ->groupBy('class_name')
            ->pluck('total', 'class_name');

        return view('admin.attendance', [
            'gradeXClasses' => $classes,
            'statusLabels' => $statusLabels,
            'sessionFilters' => $filters,
            'attendanceSessions' => $attendanceSessions,
            'rosterCounts' => $rosterCounts,
        ]);
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
