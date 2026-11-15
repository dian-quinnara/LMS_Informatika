<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\StudentImporter;
use App\Support\SimpleXlsx;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentImportController extends Controller
{
    private const SESSION_KEY = 'student_import';

    /** Baris gagal impor terakhir (tanpa kata sandi), bertahan sampai impor berikutnya agar bisa diunduh. */
    private const FAILED_ROWS_KEY = 'student_import_failed_rows';

    public function __construct(private readonly StudentImporter $importer) {}

    /**
     * Halaman impor: unggah file, pratinjau, dan hasil impor terakhir.
     */
    public function index(Request $request): View
    {
        $pending = $request->session()->get(self::SESSION_KEY);
        $preview = $pending ? $this->importer->validate($pending['records']) : null;

        return view('admin.student-import', [
            'classes' => config('attendance.grade_x_classes', []),
            'pending' => $pending,
            'preview' => $preview,
            'result' => $request->session()->get('student_import_result'),
            'studentCount' => User::query()->where('role', 'student')->count(),
            'unassignedCount' => User::query()->where('role', 'student')->whereNull('class_name')->count(),
            'resettableCounts' => $this->resettableStudents()
                ->selectRaw('class_name, count(*) as total')
                ->groupBy('class_name')
                ->pluck('total', 'class_name'),
            'passwordReset' => $request->session()->get('password_reset_result'),
        ]);
    }

    /**
     * Buat ulang kata sandi awal untuk siswa yang belum pernah mengganti kata sandi.
     * Siswa yang sudah memakai kata sandi sendiri tidak tersentuh.
     */
    public function resetPasswords(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'class_name' => ['nullable', 'string', Rule::in(config('attendance.grade_x_classes', []))],
            'confirm' => ['accepted'],
        ], [
            'class_name.in' => 'Kelas harus salah satu dari XE1–XE9.',
            'confirm.accepted' => 'Centang konfirmasi bahwa kata sandi awal lama tidak berlaku lagi.',
        ]);
        $className = $validated['class_name'] ?? null;

        $rows = DB::transaction(function () use ($className): array {
            $students = $this->resettableStudents()
                ->when($className, fn ($query, string $class) => $query->where('class_name', $class))
                ->orderBy('class_name')
                ->orderBy('name')
                ->lockForUpdate()
                ->get();

            return $students->map(function (User $student): array {
                $password = $this->importer->initialPassword();
                $student->forceFill(['password' => $password, 'must_change_password' => true])->save();

                return [
                    'name' => $student->name,
                    'nis' => (string) $student->nis,
                    'email' => $student->email,
                    'class_name' => (string) $student->class_name,
                    'password' => $password,
                ];
            })->all();
        });

        if ($rows === []) {
            return back()->withErrors(['class_name' => 'Tidak ada siswa yang masih memakai kata sandi awal'.($className ? ' di kelas '.$className : '').'.']);
        }

        return redirect()->route('admin.students.import')
            ->with('status', 'Kata sandi awal baru dibuat untuk '.count($rows).' siswa'.($className ? ' kelas '.$className : '').'. Kata sandi awal lama tidak berlaku lagi.')
            ->with('password_reset_result', [
                'class_name' => $className,
                'rows' => $rows,
                'credentials_csv' => $this->credentialsCsv($rows),
            ]);
    }

    /**
     * @return Builder<User>
     */
    private function resettableStudents(): Builder
    {
        return User::query()->where('role', 'student')->where('must_change_password', true);
    }

    /**
     * @param  list<array{name: string, nis: string, email: string, class_name: string, password: string}>  $rows
     */
    private function credentialsCsv(array $rows): string
    {
        return base64_encode(
            "\xEF\xBB\xBF".'Nama,NIS,Email,Kelas,Kata sandi awal'."\r\n"
            .collect($rows)->map(fn (array $row) => collect([$row['name'], $row['nis'], $row['email'], $row['class_name'], $row['password']])
                ->map(fn (string $value) => '"'.str_replace('"', '""', $this->csvSafe($value)).'"')
                ->implode(','))->implode("\r\n")
        );
    }

    /**
     * Unduh template data siswa (.xlsx atau .csv).
     */
    public function template(string $format): Response
    {
        $classes = config('attendance.grade_x_classes', []);

        if ($format === 'csv') {
            return response("\xEF\xBB\xBF".implode(',', StudentImporter::HEADERS)."\r\n", 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="template-data-siswa.csv"',
            ]);
        }

        $content = SimpleXlsx::build([
            'Data Siswa' => [
                'rows' => [StudentImporter::HEADERS],
                'widths' => [34, 16, 34, 10],
                'text_columns' => [1],
                'bold_first_row' => true,
            ],
            'Petunjuk' => [
                'rows' => [
                    ['Petunjuk pengisian'],
                    ['1. Isi data siswa pada lembar "Data Siswa" mulai baris ke-2. Jangan ubah judul kolom.'],
                    ['2. Nama: nama lengkap siswa.'],
                    ['3. NIS: nomor induk siswa, unik untuk setiap siswa.'],
                    ['4. Email: alamat email untuk login, unik untuk setiap siswa.'],
                    ['5. Kelas: salah satu dari '.implode(', ', $classes).'.'],
                    ['6. Siswa yang emailnya sudah terdaftar hanya diperbarui kelasnya; data lama tidak dihapus.'],
                    ['7. Akun baru mendapat kata sandi awal acak dan wajib menggantinya saat login pertama.'],
                ],
                'widths' => [100],
                'bold_first_row' => true,
            ],
        ]);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="template-data-siswa.xlsx"',
        ]);
    }

    /**
     * Unduh contoh file berisi data fiktif yang valid.
     */
    public function example(): Response
    {
        $content = SimpleXlsx::build([
            'Data Siswa' => [
                'rows' => [
                    StudentImporter::HEADERS,
                    ['Siswa Contoh XE1', '0012301', 'siswa.xe1@example.test', 'XE1'],
                    ['Siswa Contoh XE2', '0012302', 'siswa.xe2@example.test', 'XE2'],
                    ['Siswa Contoh XE9', '0012309', 'siswa.xe9@example.test', 'XE9'],
                ],
                'widths' => [34, 16, 34, 14],
                'text_columns' => [1],
                'bold_first_row' => true,
            ],
        ]);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="contoh-data-siswa.xlsx"',
        ]);
    }

    /**
     * Baca file, simpan barisnya di sesi server, lalu tampilkan pratinjau.
     */
    public function preview(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:2048'],
        ], [
            'file.required' => 'Pilih file .xlsx atau .csv untuk diimpor.',
            'file.file' => 'File gagal diunggah. Coba lagi.',
            'file.max' => 'Ukuran file maksimal 2 MB.',
        ]);

        $file = $request->file('file');

        if (! in_array(strtolower($file->getClientOriginalExtension()), ['xlsx', 'csv'], true)) {
            return back()->withErrors(['file' => 'Format file harus .xlsx atau .csv.']);
        }

        try {
            $records = $this->importer->read($file);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()]);
        }

        $request->session()->forget(self::FAILED_ROWS_KEY);
        $request->session()->put(self::SESSION_KEY, [
            'id' => (string) Str::uuid(),
            'filename' => Str::limit($file->getClientOriginalName(), 80),
            'records' => $records,
        ]);

        return redirect()->route('admin.students.import');
    }

    /**
     * Simpan baris valid dari pratinjau. Data diambil dari sesi server, bukan dari form.
     */
    public function store(Request $request): RedirectResponse
    {
        $pending = $request->session()->get(self::SESSION_KEY);
        $request->validate(['import_id' => ['required', 'string']]);

        if (! $pending || ! hash_equals($pending['id'], (string) $request->input('import_id'))) {
            return redirect()->route('admin.students.import')
                ->withErrors(['file' => 'Pratinjau impor sudah kedaluwarsa. Unggah ulang file.']);
        }

        $request->session()->forget(self::SESSION_KEY);
        $result = $this->importer->import($pending['records']);

        $credentialsCsv = $result['created'] === [] ? null : $this->credentialsCsv($result['created']);
        $request->session()->put(self::FAILED_ROWS_KEY, $result['failed']);

        return redirect()->route('admin.students.import')
            ->with('status', count($result['created']).' akun baru dibuat, '.count($result['updated']).' siswa diperbarui, '.count($result['failed']).' baris gagal.')
            ->with('student_import_result', [
                'filename' => $pending['filename'],
                'created' => $result['created'],
                'updated' => $result['updated'],
                'failed' => $result['failed'],
                'credentials_csv' => $credentialsCsv,
            ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('admin.students.import')->with('status', 'Pratinjau impor dibatalkan. Tidak ada data yang disimpan.');
    }

    /**
     * Unduh baris yang gagal beserta sebabnya tanpa menyertakan kata sandi.
     */
    public function downloadErrors(Request $request): StreamedResponse
    {
        $failedRows = $request->session()->get(self::FAILED_ROWS_KEY, []);
        abort_if($failedRows === [], 404);

        return response()->streamDownload(function () use ($failedRows): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Baris', 'Nama', 'NIS', 'Email', 'Kelas', 'Kesalahan'], ',', '"', '');

            foreach ($failedRows as $row) {
                fputcsv($output, [
                    $row['line'],
                    $this->csvSafe($row['name']),
                    $this->csvSafe($row['nis']),
                    $this->csvSafe($row['email']),
                    $this->csvSafe($row['class_name']),
                    $this->csvSafe(implode(' ', $row['errors'])),
                ], ',', '"', '');
            }

            fclose($output);
        }, 'laporan-baris-gagal-impor.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Cegah formula injection saat file kredensial dibuka di Excel.
     */
    private function csvSafe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }
}
