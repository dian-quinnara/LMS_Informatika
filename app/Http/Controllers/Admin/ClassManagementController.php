<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Menu 3 · Kelola Kelas: kelas XE1–XE9, anggota per kelas, dan pindah kelas.
 */
class ClassManagementController extends Controller
{
    public function index(): View
    {
        $classes = config('attendance.grade_x_classes', []);
        $counts = User::query()
            ->where('role', 'student')
            ->whereIn('class_name', $classes)
            ->selectRaw('class_name, count(*) as total, sum(case when is_active then 1 else 0 end) as active')
            ->groupBy('class_name')
            ->get()
            ->keyBy('class_name');

        return view('admin.classes.index', [
            'classes' => collect($classes)->map(fn (string $class): array => [
                'name' => $class,
                'total' => (int) ($counts[$class]->total ?? 0),
                'active' => (int) ($counts[$class]->active ?? 0),
            ]),
            'unassigned' => User::query()->where('role', 'student')->whereNull('class_name')->orderBy('name')->get(['id', 'name', 'nis', 'email']),
        ]);
    }

    public function show(string $className): View
    {
        $classes = config('attendance.grade_x_classes', []);
        abort_unless(in_array($className, $classes, true), 404);

        return view('admin.classes.show', [
            'className' => $className,
            'classes' => $classes,
            'members' => User::query()->where('role', 'student')->where('class_name', $className)->orderBy('name')
                ->get(['id', 'name', 'nis', 'email', 'is_active', 'must_change_password']),
        ]);
    }

    /**
     * Pindahkan satu atau beberapa siswa ke kelas lain (atau kosongkan kelasnya).
     */
    public function move(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1', 'max:500'],
            'user_ids.*' => ['integer', Rule::exists('users', 'id')->where('role', 'student')],
            'class_name' => ['nullable', 'string', Rule::in(config('attendance.grade_x_classes', []))],
        ], [
            'user_ids.required' => 'Pilih minimal satu siswa.',
            'user_ids.*.exists' => 'Siswa tidak ditemukan.',
            'class_name.in' => 'Kelas harus salah satu dari XE1–XE9.',
        ]);

        $target = $validated['class_name'] ?? null;
        $moved = User::query()
            ->where('role', 'student')
            ->whereIn('id', $validated['user_ids'])
            ->update(['class_name' => $target, 'updated_at' => now()]);

        return back()->with('status', $target
            ? $moved.' siswa dipindahkan ke kelas '.$target.'.'
            : 'Kelas '.$moved.' siswa dikosongkan.');
    }
}
