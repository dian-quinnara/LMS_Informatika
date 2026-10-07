<?php

namespace App\Http\Controllers;

use App\Models\AttendanceToken;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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

    public function storeSession(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_name' => ['required', 'string', Rule::in(config('attendance.classes', []))],
            'starts_at' => ['required', 'date'],
            'expires_at' => ['required', 'date', 'after:starts_at'],
        ]);

        $token = $this->generateUniqueToken();
        $tokenHash = hash_hmac('sha256', $token, (string) config('app.key'));

        $attendanceToken = AttendanceToken::query()->create([
            'class_name' => $validated['class_name'],
            'created_by' => $request->user()->id,
            'token' => $token,
            'token_hash' => $tokenHash,
            'starts_at' => $validated['starts_at'],
            'expires_at' => $validated['expires_at'],
        ]);

        return back()
            ->with('status', 'Sesi presensi '.$attendanceToken->displayName().' berhasil dibuat. Bagikan token ini kepada siswa.')
            ->with('attendance_session', [
                'class_name' => $attendanceToken->class_name,
                'token' => $token,
                'starts_at' => $attendanceToken->starts_at->format('d M Y, H:i'),
                'expires_at' => $attendanceToken->expires_at->format('H:i'),
                'status' => $attendanceToken->sessionStatus(),
            ]);
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = Str::upper(Str::random(10));
        } while (AttendanceToken::query()->where('token', $token)->exists());

        return $token;
    }
}
