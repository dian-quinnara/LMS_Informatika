<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceToken;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:10', 'alpha_num:ascii'],
        ], [
            'token.required' => 'Token presensi wajib diisi.',
            'token.size' => 'Token presensi terdiri dari 10 karakter.',
            'token.alpha_num' => 'Token presensi hanya berisi huruf dan angka.',
        ]);
        $student = $request->user();
        $normalizedToken = strtoupper($validated['token']);
        $tokenHash = hash_hmac('sha256', $normalizedToken, (string) config('app.key'));

        // Sesi dicari hanya dari token; kelas diambil dari akun siswa, bukan dari input.
        $attendanceToken = AttendanceToken::query()
            ->with('subject')
            ->where('token_hash', $tokenHash)
            ->first();

        if ($attendanceToken === null) {
            return $this->reject('Token tidak valid atau sesi presensi belum/sudah berakhir.');
        }

        if ($attendanceToken->class_name !== null && $student->class_name === null) {
            return $this->reject('Kelasmu belum diatur. Hubungi admin sebelum melakukan presensi.');
        }

        if ($attendanceToken->class_name !== null && $attendanceToken->class_name !== $student->class_name) {
            return $this->reject('Token ini bukan untuk kelasmu.');
        }

        if ($attendanceToken->statusKey() === AttendanceToken::STATUS_SCHEDULED) {
            return $this->reject('Token tidak valid atau sesi presensi belum/sudah berakhir. Sesi belum dimulai.');
        }

        if ($attendanceToken->statusKey() === AttendanceToken::STATUS_COMPLETED) {
            return $this->reject('Token tidak valid atau sesi presensi belum/sudah berakhir. Sesi sudah berakhir.');
        }

        if ($attendanceToken->attendances()->where('user_id', $student->id)->exists()) {
            return $this->reject('Presensi untuk sesi ini sudah pernah dicatat.');
        }

        try {
            Attendance::query()->create([
                'user_id' => $student->id,
                'subject_id' => $attendanceToken->subject_id,
                'attendance_token_id' => $attendanceToken->id,
                'attended_on' => today(),
                'status' => 'present',
            ]);
        } catch (UniqueConstraintViolationException) {
            // Kiriman ganda yang bersamaan ditahan oleh unique index (user_id, attendance_token_id).
            return $this->reject('Presensi untuk sesi ini sudah pernah dicatat.');
        }

        return back()->with('status', 'Presensi '.$attendanceToken->displayName().' berhasil dicatat pada '.now()->format('d M Y, H:i').'.');
    }

    private function reject(string $message): RedirectResponse
    {
        return back()->withErrors(['token' => $message])->withInput();
    }
}
