<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\StudentImporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Menu 2 · Kelola Siswa: tambah, edit, nonaktifkan akun, NIS & kelas, serta reset kata sandi.
 */
class StudentManagementController extends Controller
{
    public function __construct(private readonly StudentImporter $importer) {}

    public function index(Request $request): View
    {
        $classes = config('attendance.grade_x_classes', []);
        $filters = [
            'class' => in_array($request->query('class'), [...$classes, 'none'], true) ? $request->query('class') : null,
            'status' => in_array($request->query('status'), ['active', 'inactive'], true) ? $request->query('status') : null,
            'q' => is_string($request->query('q')) ? mb_substr(trim($request->query('q')), 0, 100) : '',
        ];

        $students = User::query()
            ->where('role', 'student')
            ->when($filters['class'] === 'none', fn (Builder $query) => $query->whereNull('class_name'))
            ->when($filters['class'] !== null && $filters['class'] !== 'none', fn (Builder $query) => $query->where('class_name', $filters['class']))
            ->when($filters['status'] !== null, fn (Builder $query) => $query->where('is_active', $filters['status'] === 'active'))
            ->when($filters['q'] !== '', function (Builder $query) use ($filters): void {
                $term = '%'.addcslashes($filters['q'], '%_\\').'%';
                $query->where(fn (Builder $query) => $query->where('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('nis', 'like', $term));
            })
            ->orderBy('class_name')
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        $classCounts = User::query()->where('role', 'student')->whereIn('class_name', $classes)
            ->selectRaw('class_name, count(*) as total')->groupBy('class_name')->pluck('total', 'class_name');

        return view('admin.students.index', [
            'students' => $students,
            'classes' => $classes,
            'filters' => $filters,
            'classCounts' => $classCounts,
            'withoutClass' => User::query()->where('role', 'student')->whereNull('class_name')->count(),
            'inactiveCount' => User::query()->where('role', 'student')->where('is_active', false)->count(),
            'defaultPasswordSet' => $this->importer->defaultPassword() !== null,
        ]);
    }

    public function create(): View
    {
        return view('admin.students.form', [
            'student' => new User(['class_name' => null]),
            'classes' => config('attendance.grade_x_classes', []),
            'defaultPasswordSet' => $this->importer->defaultPassword() !== null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateStudent($request);
        $password = $this->importer->initialPassword();

        $student = new User;
        $student->forceFill([
            ...$validated,
            'role' => 'student',
            'is_active' => true,
            'password' => $password,
            'must_change_password' => true,
        ])->save();

        return redirect()->route('admin.students.index')
            ->with('status', 'Akun '.$student->name.' berhasil dibuat. Siswa wajib mengganti kata sandi saat login pertama.')
            ->with('student_credential', $this->credential($student, $password));
    }

    public function edit(User $student): View
    {
        $this->ensureStudent($student);

        return view('admin.students.form', [
            'student' => $student,
            'classes' => config('attendance.grade_x_classes', []),
            'defaultPasswordSet' => $this->importer->defaultPassword() !== null,
        ]);
    }

    public function update(Request $request, User $student): RedirectResponse
    {
        $this->ensureStudent($student);
        $student->forceFill($this->validateStudent($request, $student))->save();

        return redirect()->route('admin.students.index')->with('status', 'Data '.$student->name.' berhasil diperbarui.');
    }

    public function toggleActive(User $student): RedirectResponse
    {
        $this->ensureStudent($student);

        DB::transaction(function () use ($student): void {
            $student->forceFill(['is_active' => ! $student->is_active])->save();

            if (! $student->is_active) {
                // Akhiri sesi login yang masih berjalan di perangkat siswa.
                if (config('session.driver') === 'database') {
                    DB::table(config('session.table', 'sessions'))->where('user_id', $student->id)->delete();
                }
                $student->forceFill(['remember_token' => null])->save();
            }
        });

        return back()->with('status', $student->is_active
            ? 'Akun '.$student->name.' diaktifkan kembali.'
            : 'Akun '.$student->name.' dinonaktifkan. Siswa tidak dapat login sampai diaktifkan kembali.');
    }

    public function resetPassword(Request $request, User $student): RedirectResponse
    {
        $this->ensureStudent($student);
        $validated = $request->validate([
            'mode' => ['required', Rule::in(['default', 'random'])],
            'confirm' => ['accepted'],
        ], [
            'confirm.accepted' => 'Centang konfirmasi sebelum mereset kata sandi.',
        ]);

        if ($validated['mode'] === 'default' && $this->importer->defaultPassword() === null) {
            return back()->withErrors(['mode' => 'Kata sandi default sekolah belum diatur di file .env (STUDENT_DEFAULT_PASSWORD).']);
        }

        $password = $validated['mode'] === 'default' ? $this->importer->defaultPassword() : $this->importer->randomPassword();
        $student->forceFill(['password' => $password, 'must_change_password' => true, 'remember_token' => null])->save();

        return back()
            ->with('status', 'Kata sandi '.$student->name.' direset. Siswa wajib menggantinya saat login berikutnya.')
            ->with('student_credential', $this->credential($student, $password));
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStudent(Request $request, ?User $student = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nis' => ['required', 'string', 'regex:/^[A-Za-z0-9.\-\/]{3,30}$/', Rule::unique('users', 'nis')->ignore($student?->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($student?->id)],
            'class_name' => ['nullable', 'string', Rule::in(config('attendance.grade_x_classes', []))],
        ], [
            'nis.regex' => 'NIS hanya boleh berisi 3–30 huruf, angka, titik, strip, atau garis miring.',
            'nis.unique' => 'NIS sudah dipakai akun lain.',
            'email.unique' => 'Email sudah dipakai akun lain.',
            'class_name.in' => 'Kelas harus salah satu dari XE1–XE9.',
        ]);
        $validated['email'] = mb_strtolower(trim($validated['email']));
        $validated['class_name'] = $validated['class_name'] ?? null;

        return $validated;
    }

    /**
     * Kata sandi acak ditampilkan sekali; kata sandi default sekolah tidak pernah ditampilkan.
     *
     * @return array<string, string|null>
     */
    private function credential(User $student, string $password): array
    {
        return [
            'name' => $student->name,
            'email' => $student->email,
            'password' => $this->importer->displayPassword($password),
        ];
    }

    private function ensureStudent(User $student): void
    {
        abort_unless($student->role === 'student', 404);
    }
}
