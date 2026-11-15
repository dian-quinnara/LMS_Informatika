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
            'password' => ['required', 'string', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
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
