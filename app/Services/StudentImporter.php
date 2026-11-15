<?php

namespace App\Services;

use App\Models\User;
use App\Support\SimpleXlsx;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Membaca, memvalidasi, dan menyimpan data siswa dari file XLSX/CSV.
 *
 * Aturan: data lama tidak pernah dihapus. Baris dengan email siswa yang sudah ada
 * hanya memperbarui kelas (dan NIS bila belum ada); selain itu dibuat akun baru
 * dengan kata sandi awal sama dengan NIS yang wajib diganti saat login pertama.
 */
class StudentImporter
{
    public const MAX_ROWS = 1000;

    public const HEADERS = ['Nama', 'NIS', 'Email', 'Kelas'];

    private const HEADER_ALIASES = [
        'name' => ['nama', 'nama siswa', 'nama lengkap', 'name', 'student name'],
        'nis' => ['nis', 'no induk', 'nomor induk', 'nomor induk siswa'],
        'email' => ['email', 'e-mail', 'email/username', 'username', 'email atau username'],
        'class_name' => ['kelas', 'kode kelas', 'class', 'class code', 'class_code'],
    ];

    /**
     * Baca file unggahan menjadi baris mentah (tanpa header) beserta nomor barisnya.
     *
     * @return list<array{line: int, name: string, nis: string, email: string, class_name: string}>
     */
    public function read(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $rows = match ($extension) {
            'xlsx' => SimpleXlsx::readFirstSheet($file->getRealPath()),
            'csv' => $this->readCsv($file->getRealPath()),
            default => throw new RuntimeException('Format file harus .xlsx atau .csv.'),
        };

        $headerIndex = null;
        foreach ($rows as $index => $row) {
            if (array_filter($row, fn ($value) => trim((string) $value) !== '') !== []) {
                $headerIndex = $index;
                break;
            }
        }

        if ($headerIndex === null) {
            throw new RuntimeException('File kosong. Isi data siswa di bawah baris judul kolom.');
        }

        $columns = $this->mapHeader($rows[$headerIndex]);
        $records = [];

        foreach (array_slice($rows, $headerIndex + 1, null, true) as $index => $row) {
            $record = ['line' => $index + 1];
            foreach ($columns as $field => $column) {
                $record[$field] = $this->clean($row[$column] ?? '');
            }

            if ($record['name'] === '' && $record['nis'] === '' && $record['email'] === '' && $record['class_name'] === '') {
                continue;
            }

            $records[] = $record;
        }

        if ($records === []) {
            throw new RuntimeException('Tidak ada data siswa di bawah baris judul kolom.');
        }

        if (count($records) > self::MAX_ROWS) {
            throw new RuntimeException('Maksimal '.self::MAX_ROWS.' siswa per file. File ini berisi '.count($records).' baris data.');
        }

        return $records;
    }

    /**
     * Validasi semua baris terhadap aturan isian, duplikat dalam file, dan data di database.
     *
     * @param  list<array{line: int, name: string, nis: string, email: string, class_name: string}>  $records
     * @return list<array{line: int, name: string, nis: string, email: string, class_name: string, action: ?string, errors: list<string>}>
     */
    public function validate(array $records): array
    {
        $classes = config('attendance.grade_x_classes', []);
        $emails = array_values(array_filter(array_map(fn ($r) => Str::lower($r['email']), $records)));
        $nisList = array_values(array_filter(array_column($records, 'nis')));

        $usersByEmail = User::query()->whereIn(DB::raw('LOWER(email)'), $emails)->get(['id', 'email', 'nis', 'role'])->keyBy(fn (User $u) => Str::lower($u->email));
        $usersByNis = User::query()->whereIn('nis', $nisList)->get(['id', 'email', 'nis'])->keyBy('nis');
        $seenEmails = [];
        $seenNis = [];
        $results = [];

        foreach ($records as $record) {
            $record['email'] = Str::lower($record['email']);
            $record['class_name'] = Str::upper(str_replace(' ', '', $record['class_name']));
            $errors = [];

            if ($record['name'] === '') {
                $errors[] = 'Nama wajib diisi.';
            } elseif (mb_strlen($record['name']) > 255) {
                $errors[] = 'Nama maksimal 255 karakter.';
            }

            if ($record['nis'] === '') {
                $errors[] = 'NIS wajib diisi.';
            } elseif (preg_match('/^[A-Za-z0-9.\-\/]{3,30}$/', $record['nis']) !== 1) {
                $errors[] = 'NIS hanya boleh berisi 3–30 huruf, angka, titik, strip, atau garis miring.';
            }

            if ($record['email'] === '') {
                $errors[] = 'Email wajib diisi.';
            } elseif (filter_var($record['email'], FILTER_VALIDATE_EMAIL) === false || mb_strlen($record['email']) > 255) {
                $errors[] = 'Email tidak valid. Login Ruang Belajar memakai alamat email.';
            }

            if ($record['class_name'] === '') {
                $errors[] = 'Kelas wajib diisi.';
            } elseif (! in_array($record['class_name'], $classes, true)) {
                $errors[] = 'Kelas "'.$record['class_name'].'" tidak dikenal. Gunakan salah satu: '.implode(', ', $classes).'.';
            }

            if ($record['email'] !== '' && isset($seenEmails[$record['email']])) {
                $errors[] = 'Email sama dengan baris '.$seenEmails[$record['email']].' di file ini.';
            }

            if ($record['nis'] !== '' && isset($seenNis[$record['nis']])) {
                $errors[] = 'NIS sama dengan baris '.$seenNis[$record['nis']].' di file ini.';
            }

            $seenEmails[$record['email']] ??= $record['line'];
            $seenNis[$record['nis']] ??= $record['line'];

            $existing = $usersByEmail->get($record['email']);
            $nisOwner = $record['nis'] !== '' ? $usersByNis->get($record['nis']) : null;

            if ($existing !== null && $existing->role !== 'student') {
                $errors[] = 'Email sudah dipakai akun admin.';
            }

            if ($existing !== null && $existing->nis !== null && $record['nis'] !== '' && $existing->nis !== $record['nis']) {
                $errors[] = 'Email sudah terdaftar dengan NIS lain ('.$existing->nis.').';
            }

            if ($nisOwner !== null && ($existing === null || $nisOwner->id !== $existing->id)) {
                $errors[] = 'NIS sudah dipakai akun lain ('.$nisOwner->email.').';
            }

            $record['errors'] = $errors;
            $record['action'] = $errors === [] ? ($existing === null ? 'create' : 'update') : null;
            $results[] = $record;
        }

        return $results;
    }

    /**
     * Simpan baris valid dalam satu transaksi. Baris divalidasi ulang terhadap database terkini.
     *
     * @param  list<array{line: int, name: string, nis: string, email: string, class_name: string}>  $records
     * @return array{created: list<array<string, mixed>>, updated: list<array<string, mixed>>, failed: list<array<string, mixed>>}
     */
    public function import(array $records): array
    {
        return DB::transaction(function () use ($records): array {
            $result = ['created' => [], 'updated' => [], 'failed' => []];

            foreach ($this->validate($records) as $row) {
                if ($row['errors'] !== []) {
                    $result['failed'][] = $row;

                    continue;
                }

                if ($row['action'] === 'update') {
                    $student = User::query()
                        ->whereRaw('LOWER(email) = ?', [$row['email']])
                        ->where('role', 'student')
                        ->lockForUpdate()
                        ->firstOrFail();
                    $student->forceFill([
                        'class_name' => $row['class_name'],
                        'nis' => $student->nis ?? $row['nis'],
                    ])->save();
                    $result['updated'][] = [...$row, 'name' => $student->name];

                    continue;
                }

                // Kata sandi awal = NIS (permintaan sekolah); wajib diganti saat login pertama.
                $password = $row['nis'];
                $student = new User;
                $student->forceFill([
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'nis' => $row['nis'],
                    'class_name' => $row['class_name'],
                    'role' => 'student',
                    'password' => $password,
                    'must_change_password' => true,
                ])->save();
                $result['created'][] = [...$row, 'password' => $password];
            }

            return $result;
        });
    }

    /**
     * Kata sandi awal acak (kriptografis), tanpa simbol agar mudah dibagikan.
     */
    public function initialPassword(): string
    {
        do {
            $password = Str::password(10, letters: true, numbers: true, symbols: false);
        } while (preg_match('/[a-z]/', $password) !== 1 || preg_match('/[A-Z]/', $password) !== 1 || preg_match('/\d/', $password) !== 1);

        return $password;
    }

    /**
     * @param  list<string>  $header
     * @return array{name: int, nis: int, email: int, class_name: int}
     */
    private function mapHeader(array $header): array
    {
        $normalized = array_map(fn ($value) => Str::lower(trim(preg_replace('/\s+/', ' ', (string) $value))), $header);
        $columns = [];

        foreach (self::HEADER_ALIASES as $field => $aliases) {
            foreach ($normalized as $index => $label) {
                if (in_array($label, $aliases, true)) {
                    $columns[$field] = $index;
                    break;
                }
            }
        }

        $missing = array_diff_key(array_combine(array_keys(self::HEADER_ALIASES), self::HEADERS), $columns);

        if ($missing !== []) {
            throw new RuntimeException('Kolom wajib tidak ditemukan: '.implode(', ', $missing).'. Gunakan template dengan judul kolom '.implode(', ', self::HEADERS).'.');
        }

        return $columns;
    }

    /**
     * @return list<list<string>>
     */
    private function readCsv(string $path): array
    {
        $content = (string) file_get_contents($path);
        $content = (string) preg_replace('/^\xEF\xBB\xBF/', '', $content);

        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        $firstLine = strtok($content, "\r\n") ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);
        $rows = [];

        while (($row = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
            $rows[] = array_map(fn ($value) => (string) $value, $row);
        }

        fclose($stream);

        return $rows;
    }

    private function clean(mixed $value): string
    {
        $value = trim(preg_replace('/[\x00-\x1F\x7F\x{00A0}]+/u', ' ', (string) $value) ?? '');

        return preg_replace('/\s{2,}/', ' ', $value) ?? $value;
    }
}
