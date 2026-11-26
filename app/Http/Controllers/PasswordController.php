<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.change-password', ['user' => $request->user()]);
    }

    /**
     * Ganti kata sandi. Hanya kata sandi yang diubah; kelas dan peran tidak bisa diubah di sini.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => [
                'required', 'string', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers(),
                // Kata sandi baru tidak boleh mudah ditebak: bukan kata sandi default sekolah dan bukan NIS.
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    $default = config('students.default_password');
                    $nis = $request->user()->nis;

                    if (is_string($default) && $default !== '' && hash_equals($default, (string) $value)) {
                        $fail('Kata sandi baru tidak boleh sama dengan kata sandi default sekolah.');
                    } elseif ($nis !== null && $nis !== '' && str_contains(strtolower((string) $value), strtolower($nis))) {
                        $fail('Kata sandi baru tidak boleh memuat NIS.');
                    }
                },
            ],
        ], [
            'current_password.required' => 'Masukkan kata sandi saat ini.',
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password.required' => 'Masukkan kata sandi baru.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak sama.',
            'password.different' => 'Kata sandi baru harus berbeda dari kata sandi awal.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.letters' => 'Kata sandi baru harus berisi huruf.',
            'password.numbers' => 'Kata sandi baru harus berisi angka.',
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
        ])->save();

        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Kata sandi berhasil diganti.');
    }
}
