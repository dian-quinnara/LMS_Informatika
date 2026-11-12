<?php

namespace App\Http\Controllers;

use App\Models\AttendanceToken;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AttendanceTokenController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'starts_at' => ['required', 'date', 'after_or_equal:now'],
            'expires_at' => ['required', 'date', 'after:starts_at'],
        ]);
        $token = Str::upper(Str::random(10));
        $tokenHash = hash_hmac('sha256', $token, (string) config('app.key'));
        $subject = Subject::query()->findOrFail($validated['subject_id']);

        AttendanceToken::query()->create([
            'subject_id' => $subject->id,
            'created_by' => $request->user()->id,
            'token_hash' => $tokenHash,
            'starts_at' => $validated['starts_at'],
            'expires_at' => $validated['expires_at'],
        ]);

        return back()
            ->with('status', 'Sesi presensi '.$subject->name.' berhasil dibuat. Bagikan token ini kepada siswa.')
            ->with('attendance_token', $token)
            ->with('attendance_expires_at', $validated['expires_at']);
    }

    /**
     * Buat sesi presensi untuk salah satu kelas X (XE1–XE9).
     */
    public function storeSession(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_name' => ['required', 'string', Rule::in(config('attendance.grade_x_classes', []))],
            'title' => ['nullable', 'string', 'max:120'],
            'starts_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after:starts_at'],
        ], [
            'class_name.required' => 'Pilih kelas untuk sesi presensi.',
            'class_name.in' => 'Kelas harus salah satu dari XE1–XE9.',
            'title.max' => 'Judul sesi maksimal 120 karakter.',
            'starts_at.required' => 'Waktu mulai wajib diisi.',
            'starts_at.date' => 'Waktu mulai tidak valid.',
            'expires_at.required' => 'Waktu berakhir wajib diisi.',
            'expires_at.date' => 'Waktu berakhir tidak valid.',
            'expires_at.after' => 'Waktu berakhir harus setelah waktu mulai.',
        ]);

        $startsAt = Carbon::parse($validated['starts_at']);
        $expiresAt = Carbon::parse($validated['expires_at']);

        // Cegah sesi ganda akibat formulir terkirim dua kali.
        $duplicate = AttendanceToken::query()
            ->where('class_name', $validated['class_name'])
            ->where('starts_at', $startsAt)
            ->where('expires_at', $expiresAt)
            ->exists();

        if ($duplicate) {
            return back()
                ->withErrors(['class_name' => 'Sesi presensi kelas '.$validated['class_name'].' dengan waktu yang sama sudah dibuat.'])
                ->withInput();
        }

        $token = $this->generateUniqueToken();

        $attendanceToken = AttendanceToken::query()->create([
            'class_name' => $validated['class_name'],
            'title' => $validated['title'] ?? null,
            'created_by' => $request->user()->id,
            'token' => $token,
            'token_hash' => $this->hashToken($token),
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
        ]);

        return redirect()
            ->route('dashboard')
            ->with('status', 'Sesi presensi '.$attendanceToken->displayName().' berhasil dibuat. Bagikan token ini kepada siswa.')
            ->with('attendance_session', [
                'id' => $attendanceToken->id,
                'class_name' => $attendanceToken->class_name,
                'title' => $attendanceToken->title,
                'token' => $token,
                'starts_at' => $attendanceToken->starts_at->format('d M Y, H:i'),
                'expires_at' => $attendanceToken->expires_at->format('d M Y, H:i'),
                'status' => $attendanceToken->sessionStatus(),
            ]);
    }

    /**
     * Detail sesi presensi kelas X: token, siswa hadir, dan siswa belum presensi.
     */
    public function show(AttendanceToken $attendanceSession): View
    {
        abort_unless(in_array($attendanceSession->class_name, config('attendance.grade_x_classes', []), true), 404);

        $attendances = $attendanceSession->attendances()
            ->with('user:id,name,email,class_name')
            ->orderBy('created_at')
            ->get();
        $roster = $this->rosterFor($attendanceSession->class_name);
        $absentStudents = $roster->whereNotIn('id', $attendances->pluck('user_id')->all())->values();

        return view('admin.attendance-session', [
            'session' => $attendanceSession,
            'attendances' => $attendances,
            'roster' => $roster,
            'absentStudents' => $absentStudents,
        ]);
    }

    /**
     * Laporan presensi per kelas dan rentang tanggal.
     */
    public function report(Request $request): View
    {
        $classes = config('attendance.grade_x_classes', []);
        $validated = $request->validate([
            'class_name' => ['nullable', 'string', Rule::in($classes)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ], [
            'class_name.in' => 'Kelas harus salah satu dari XE1–XE9.',
            'date_from.date' => 'Tanggal awal tidak valid.',
            'date_to.date' => 'Tanggal akhir tidak valid.',
            'date_to.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
        ]);

        $className = $validated['class_name'] ?? null;
        $dateFrom = Carbon::parse($validated['date_from'] ?? today())->startOfDay();
        $dateTo = Carbon::parse($validated['date_to'] ?? $dateFrom)->endOfDay();
        $sessions = collect();
        $roster = collect();
        $studentSummary = collect();

        if ($className !== null) {
            $roster = $this->rosterFor($className);
            $sessions = AttendanceToken::query()
                ->where('class_name', $className)
                ->whereBetween('starts_at', [$dateFrom, $dateTo])
                ->with([
                    'attendances' => fn ($query) => $query->orderBy('created_at'),
                    'attendances.user:id,name,email,class_name',
                ])
                ->orderBy('starts_at')
                ->get();

            $sessions->each(function (AttendanceToken $session) use ($roster): void {
                $session->setAttribute(
                    'absent_students',
                    $roster->whereNotIn('id', $session->attendances->pluck('user_id')->all())->values(),
                );
            });

            $studentSummary = $roster->map(fn (User $student): array => [
                'student' => $student,
                'attended' => $sessions
                    ->filter(fn (AttendanceToken $session): bool => $session->attendances->contains('user_id', $student->id))
                    ->count(),
            ]);
        }

        return view('admin.attendance-report', [
            'classes' => $classes,
            'className' => $className,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'sessions' => $sessions,
            'roster' => $roster,
            'studentSummary' => $studentSummary,
        ]);
    }

    /**
     * Tetapkan kelas X untuk akun siswa (roster kelas dikelola admin).
     */
    public function assignClass(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'student')],
            'class_name' => ['nullable', 'string', Rule::in(config('attendance.grade_x_classes', []))],
        ], [
            'user_id.required' => 'Pilih siswa terlebih dahulu.',
            'user_id.exists' => 'Siswa tidak ditemukan.',
            'class_name.in' => 'Kelas harus salah satu dari XE1–XE9.',
        ]);

        $student = User::query()->findOrFail($validated['user_id']);
        $student->forceFill(['class_name' => $validated['class_name'] ?? null])->save();

        return back()->with('status', $student->class_name
            ? $student->name.' sekarang terdaftar di kelas '.$student->class_name.'.'
            : 'Kelas '.$student->name.' dikosongkan.');
    }

    /**
     * @return Collection<int, User>
     */
    private function rosterFor(string $className): Collection
    {
        return User::query()
            ->where('role', 'student')
            ->where('class_name', $className)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'class_name'])
            ->toBase();
    }

    private function hashToken(string $token): string
    {
        return hash_hmac('sha256', $token, (string) config('app.key'));
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = Str::upper(Str::random(10));
        } while (AttendanceToken::query()
            ->where('token', $token)
            ->orWhere('token_hash', $this->hashToken($token))
            ->exists());

        return $token;
    }
}
