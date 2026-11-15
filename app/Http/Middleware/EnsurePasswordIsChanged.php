<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun dengan kata sandi awal (hasil impor) wajib mengganti kata sandi sebelum memakai aplikasi.
 */
class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->must_change_password && ! $request->routeIs('password.edit', 'password.update', 'logout')) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Ganti kata sandi awal terlebih dahulu.'], 403)
                : redirect()->route('password.edit');
        }

        return $next($request);
    }
}
