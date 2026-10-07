<?php

namespace App\Http\Controllers;

use App\Models\AttendanceToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:10', 'alpha_num:ascii'],
        ]);
        $normalizedToken = strtoupper($validated['token']);
        $tokenHash = hash_hmac('sha256', $normalizedToken, (string) config('app.key'));
        $attendanceToken = AttendanceToken::query()
            ->with('subject')
            ->where('token_hash', $tokenHash)
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>=', now())
            ->first();

        if ($attendanceToken === null) {
            return back()->withErrors(['token' => 'Token tidak valid atau sesi presensi belum/sudah berakhir.'])->withInput();
        }

        $attendance = $attendanceToken->attendances()->firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'subject_id' => $attendanceToken->subject_id,
                'attended_on' => today(),
                'status' => 'present',
            ],
        );

        $message = $attendance->wasRecentlyCreated
            ? 'Presensi '.$attendanceToken->subject->name.' berhasil dicatat.'
            : 'Presensi untuk sesi ini sudah pernah dicatat.';

        return back()->with('status', $message);
    }
}
