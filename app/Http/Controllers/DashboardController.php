<?php

namespace App\Http\Controllers;

use App\Models\AttendanceToken;
use App\Models\Material;
use App\Models\Simulation;
use App\Models\Subject;
use App\Models\User;
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
        $attendanceTokens = AttendanceToken::query()
            ->with('subject')
            ->where('expires_at', '>=', now())
            ->orderBy('starts_at')
            ->get();
        $attendanceSessions = AttendanceToken::query()
            ->with('subject')
            ->latest()
            ->get();
        $attendanceClasses = config('attendance.classes', []);
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

        return view('dashboard', compact('user', 'studentSection', 'subjects', 'learningTopics', 'simulations', 'attendanceTokens', 'attendanceSessions', 'attendanceClasses', 'attendanceHistory', 'attendedTokenIds', 'attempts', 'stats'));
    }
}
