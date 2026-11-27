<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menu 9 · Pengaturan Sistem: identitas website, profil admin, keamanan akun, dan data sistem.
 */
class SystemSettingsController extends Controller
{
    /**
     * Tabel yang dicadangkan beserta kolom rahasia yang tidak ikut diekspor.
     *
     * @var array<string, list<string>>
     */
    private const BACKUP_TABLES = [
        'users' => ['password', 'remember_token'],
        'subjects' => [],
        'materials' => [],
        'simulations' => [],
        'bank_questions' => [],
        'questions' => [],
        'attempts' => [],
        'answers' => [],
        'attendance_tokens' => ['token', 'token_hash'],
        'attendances' => [],
        'announcements' => [],
        'settings' => [],
    ];

    public function index(): View
    {
        $counts = collect(self::BACKUP_TABLES)
            ->keys()
            ->filter(fn (string $table) => Schema::hasTable($table))
            ->mapWithKeys(fn (string $table) => [$table => DB::table($table)->count()]);

        return view('admin.settings.index', [
            'settings' => Setting::allValues(),
            'counts' => $counts,
            'environment' => [
                'Laravel' => app()->version(),
                'PHP' => PHP_VERSION,
                'Database' => config('database.default'),
                'Zona waktu' => config('app.timezone'),
                'Kata sandi default siswa' => filled(config('students.default_password')) ? 'Diatur di .env' : 'Belum diatur (acak)',
                'KKM kuis' => (string) config('students.passing_score', 70),
            ],
        ]);
    }

    public function updateIdentity(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:60'],
            'school_name' => ['nullable', 'string', 'max:120'],
            'site_tagline' => ['nullable', 'string', 'max:160'],
        ]);

        Setting::store($validated);

        return back()->with('status', 'Identitas website diperbarui.');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'current_password'],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
        ]);

        $user->forceFill([
            'name' => $validated['name'],
            'email' => mb_strtolower(trim($validated['email'])),
        ])->save();

        return back()->with('status', 'Profil admin diperbarui.');
    }

    /**
     * Cadangan data aplikasi (JSON). Kata sandi dan token presensi tidak ikut diekspor.
     */
    public function backup(): StreamedResponse
    {
        $filename = 'backup-lms-'.now()->format('Ymd-His').'.json';

        return response()->streamDownload(function (): void {
            echo '{"generated_at":'.json_encode(now()->toIso8601String()).',"app":'.json_encode(config('app.name')).',"tables":{';
            $firstTable = true;

            foreach (self::BACKUP_TABLES as $table => $hidden) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                echo ($firstTable ? '' : ',').json_encode($table).':[';
                $firstTable = false;
                $firstRow = true;
                $orderColumn = Schema::hasColumn($table, 'id') ? 'id' : Schema::getColumnListing($table)[0];

                foreach (DB::table($table)->lazyById(500, $orderColumn) as $row) {
                    $data = array_diff_key((array) $row, array_flip($hidden));
                    echo ($firstRow ? '' : ',').json_encode($data, JSON_UNESCAPED_UNICODE);
                    $firstRow = false;
                }

                echo ']';
            }

            echo '}}';
        }, $filename, ['Content-Type' => 'application/json; charset=UTF-8']);
    }
}
