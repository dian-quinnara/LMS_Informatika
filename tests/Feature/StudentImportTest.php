<?php

namespace Tests\Feature;

use App\Models\AttendanceToken;
use App\Models\User;
use App\Support\SimpleXlsx;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use ReflectionMethod;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private const SAMPLE_ROWS = [
        ['Nama', 'NIS', 'Email', 'Kelas'],
        ['Ayu Lestari', '0012301', 'ayu.lestari@sekolah.test', 'XE1'],
        ['Bima Saputra', '0012302', 'bima.saputra@sekolah.test', 'xe2'],
        ['Citra Maharani', '0012309', 'citra.maharani@sekolah.test', 'XE9'],
    ];

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function xlsxUpload(array $rows, string $name = 'data-siswa.xlsx'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, SimpleXlsx::build(['Data Siswa' => ['rows' => $rows]]));
    }

    /**
     * @param  list<list<string>>  $rows
     */
    private function csvUpload(array $rows, string $delimiter = ','): UploadedFile
    {
        $content = "\xEF\xBB\xBF".implode("\r\n", array_map(fn ($row) => implode($delimiter, $row), $rows));

        return UploadedFile::fake()->createWithContent('data-siswa.csv', $content);
    }

    /**
     * @return array<string, mixed>
     */
    private function previewAndImport(UploadedFile $file): array
    {
        $this->post(route('admin.students.import.preview'), ['file' => $file])
            ->assertRedirect(route('admin.students.import'));
        $importId = session('student_import')['id'];

        $this->post(route('admin.students.import.store'), ['import_id' => $importId])
            ->assertRedirect(route('admin.students.import'))
            ->assertSessionHas('student_import_result');

        return session('student_import_result');
    }

    public function test_admin_downloads_templates(): void
    {
        $this->actingAs($this->admin());

        $xlsx = $this->get(route('admin.students.import.template', 'xlsx'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $path = tempnam(sys_get_temp_dir(), 'tpl');
        file_put_contents($path, $xlsx->getContent());
        $this->assertSame([['Nama', 'NIS', 'Email', 'Kelas']], SimpleXlsx::readFirstSheet($path));
        unlink($path);

        $this->get(route('admin.students.import.template', 'csv'))
            ->assertOk()
            ->assertSee('Nama,NIS,Email,Kelas', false);

        $example = $this->get(route('admin.students.import.example'))
            ->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="contoh-data-siswa.xlsx"');
        $path = tempnam(sys_get_temp_dir(), 'example');
        file_put_contents($path, $example->getContent());
        $this->assertSame([
            ['Nama', 'NIS', 'Email', 'Kelas'],
            ['Siswa Contoh XE1', '0012301', 'siswa.xe1@example.test', 'XE1'],
            ['Siswa Contoh XE2', '0012302', 'siswa.xe2@example.test', 'XE2'],
            ['Siswa Contoh XE9', '0012309', 'siswa.xe9@example.test', 'XE9'],
        ], SimpleXlsx::readFirstSheet($path));
        unlink($path);

        $this->get('/admin/students/import/template/exe')->assertNotFound();
    }

    public function test_preview_does_not_save_and_shows_each_row(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.students.import.preview'), ['file' => $this->xlsxUpload(self::SAMPLE_ROWS)]);

        $this->get(route('admin.students.import'))
            ->assertOk()
            ->assertSee('Periksa data')
            ->assertSee('Ayu Lestari')
            ->assertSee('Kelas XE2')
            ->assertSee('3 SIAP · 0 GAGAL');

        $this->assertDatabaseMissing('users', ['email' => 'ayu.lestari@sekolah.test']);
    }

    public function test_xlsx_import_creates_students_in_xe1_xe2_xe9_with_hashed_initial_passwords(): void
    {
        $this->actingAs($this->admin());

        $result = $this->previewAndImport($this->xlsxUpload(self::SAMPLE_ROWS));

        $this->assertCount(3, $result['created']);
        $this->assertSame([], $result['failed']);

        foreach (['ayu.lestari@sekolah.test' => ['XE1', '0012301'], 'bima.saputra@sekolah.test' => ['XE2', '0012302'], 'citra.maharani@sekolah.test' => ['XE9', '0012309']] as $email => [$class, $nis]) {
            $student = User::query()->where('email', $email)->firstOrFail();
            $created = collect($result['created'])->firstWhere('email', $email);

            $this->assertSame('student', $student->role);
            $this->assertSame($class, $student->class_name);
            $this->assertSame($nis, $student->nis);
            $this->assertTrue($student->must_change_password);
            $this->assertNotSame($created['password'], $student->password);
            $this->assertTrue(Hash::check($created['password'], $student->password));
            $this->assertMatchesRegularExpression('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z0-9]{10}$/', $created['password']);
        }

        $this->get(route('admin.students.import'))->assertOk()->assertSee('Unduh kredensial');
    }

    public function test_csv_import_with_semicolon_delimiter(): void
    {
        $this->actingAs($this->admin());

        $result = $this->previewAndImport($this->csvUpload(self::SAMPLE_ROWS, ';'));

        $this->assertCount(3, $result['created']);
        $this->assertSame('XE9', User::query()->where('nis', '0012309')->value('class_name'));
    }

    public function test_import_accepts_class_code_column_and_downloads_failed_rows(): void
    {
        $this->actingAs($this->admin());
        $file = $this->csvUpload([
            ['name', 'nis', 'email', 'class_code'],
            ['Siswa Valid', '2001', 'valid@sekolah.test', 'XE1'],
            ['Siswa Tidak Valid', '2002', 'invalid@sekolah.test', 'XI F1'],
        ]);

        $result = $this->previewAndImport($file);

        $this->assertCount(1, $result['created']);
        $this->assertCount(1, $result['failed']);
        $this->assertDatabaseHas('users', ['email' => 'valid@sekolah.test', 'class_name' => 'XE1']);

        $report = $this->get(route('admin.students.import.errors'))
            ->assertDownload('laporan-baris-gagal-impor.csv')
            ->streamedContent();

        $this->assertStringContainsString('Siswa Tidak Valid', $report);
        $this->assertStringContainsString('Kelas', $report);
        $this->assertStringNotContainsString('Kata sandi', $report);
    }

    public function test_failed_rows_download_works_after_result_page_is_shown(): void
    {
        $this->actingAs($this->admin());
        $this->previewAndImport($this->csvUpload([
            ['Nama', 'NIS', 'Email', 'Kelas'],
            ['Siswa Valid', '3001', 'valid3@sekolah.test', 'XE1'],
            ['Siswa Gagal', '3002', 'gagal3@sekolah.test', 'ZZ9'],
        ]));

        // Alur browser: halaman hasil tampil dulu, baru tombol unduh diklik (bisa lebih dari sekali).
        $this->get(route('admin.students.import'))->assertOk()->assertSee('Unduh laporan kesalahan');
        $this->get(route('admin.students.import'))->assertOk();

        foreach ([1, 2] as $attempt) {
            $report = $this->get(route('admin.students.import.errors'))
                ->assertDownload('laporan-baris-gagal-impor.csv')
                ->streamedContent();
            $this->assertStringContainsString('Siswa Gagal', $report);
            $this->assertStringNotContainsString('Siswa Valid', $report);
        }

        // Unggahan baru menghapus laporan lama.
        $this->post(route('admin.students.import.preview'), ['file' => $this->xlsxUpload(self::SAMPLE_ROWS)]);
        $this->get(route('admin.students.import.errors'))->assertNotFound();
    }

    public function test_excel_file_with_shared_strings_and_numeric_nis_is_read(): void
    {
        // Simulasi file buatan Excel: teks di sharedStrings dan NIS tersimpan sebagai angka.
        $shared = ['Nama', 'NIS', 'Email', 'Kelas', 'Dewi Anggraini', 'dewi@sekolah.test', 'XE2'];
        $sharedXml = '<?xml version="1.0" encoding="UTF-8"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .implode('', array_map(fn ($s) => '<si><t>'.$s.'</t></si>', array_slice($shared, 0, 4)))
            .'<si><r><t>Dewi </t></r><r><rPr><b/></rPr><t>Anggraini</t></r></si><si><t>dewi@sekolah.test</t></si><si><t>XE2</t></si></sst>';
        $sheetXml = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'
            .'<row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c><c r="C1" t="s"><v>2</v></c><c r="D1" t="s"><v>3</v></c></row>'
            .'<row r="3"><c r="A3" t="s"><v>4</v></c><c r="B3"><v>1.2345678E7</v></c><c r="C3" t="s"><v>5</v></c><c r="D3" t="s"><v>6</v></c></row>'
            .'</sheetData></worksheet>';
        $zip = new ReflectionMethod(SimpleXlsx::class, 'zip');
        $bytes = $zip->invoke(null, [
            'xl/workbook.xml' => '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Siswa" sheetId="1" r:id="rId7"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId7" Type="worksheet" Target="worksheets/data.xml"/></Relationships>',
            'xl/sharedStrings.xml' => $sharedXml,
            'xl/worksheets/data.xml' => $sheetXml,
        ]);
        $this->actingAs($this->admin());

        $result = $this->previewAndImport(UploadedFile::fake()->createWithContent('excel.xlsx', $bytes));

        $this->assertCount(1, $result['created']);
        $this->assertDatabaseHas('users', ['email' => 'dewi@sekolah.test', 'name' => 'Dewi Anggraini', 'nis' => '12345678', 'class_name' => 'XE2']);
    }

    public function test_invalid_rows_are_reported_and_valid_rows_still_imported(): void
    {
        User::factory()->create(['email' => 'lama@sekolah.test', 'nis' => '777', 'role' => 'student', 'class_name' => 'XE3']);
        User::factory()->create(['email' => 'guru@sekolah.test', 'role' => 'admin']);
        $this->actingAs($this->admin());

        $rows = [
            ['Nama', 'NIS', 'Email', 'Kelas'],
            ['Valid Satu', '1001', 'valid1@sekolah.test', 'XE1'],
            ['Kelas Salah', '1002', 'kelas@sekolah.test', 'XI F1'],
            ['', '1003', 'tanpa-nama@sekolah.test', 'XE1'],
            ['Email Salah', '1004', 'bukan-email', 'XE1'],
            ['NIS Dipakai', '777', 'baru@sekolah.test', 'XE1'],
            ['Email Admin', '1005', 'guru@sekolah.test', 'XE1'],
            ['Duplikat Email', '1006', 'valid1@sekolah.test', 'XE2'],
            ['Duplikat NIS', '1001', 'lain@sekolah.test', 'XE2'],
            ['Tanpa Kelas', '1007', 'tanpakelas@sekolah.test', ''],
        ];

        $this->post(route('admin.students.import.preview'), ['file' => $this->xlsxUpload($rows)]);
        $this->get(route('admin.students.import'))
            ->assertOk()
            ->assertSee('1 SIAP · 8 GAGAL')
            ->assertSee('Kelas &quot;XIF1&quot; tidak dikenal', false)
            ->assertSee('Nama wajib diisi.')
            ->assertSee('Email tidak valid.')
            ->assertSee('NIS sudah dipakai akun lain')
            ->assertSee('Email sudah dipakai akun admin.')
            ->assertSee('Email sama dengan baris 2 di file ini.')
            ->assertSee('NIS sama dengan baris 2 di file ini.')
            ->assertSee('Kelas wajib diisi.');

        $result = $this->previewAndImportFromSession();

        $this->assertCount(1, $result['created']);
        $this->assertCount(8, $result['failed']);
        $this->assertSame(1, User::query()->where('email', 'valid1@sekolah.test')->count());
        $this->assertSame('XE3', User::query()->where('email', 'lama@sekolah.test')->value('class_name'));
        $this->assertDatabaseMissing('users', ['email' => 'kelas@sekolah.test']);
        $this->assertSame('admin', User::query()->where('email', 'guru@sekolah.test')->value('role'));
    }

    /**
     * @return array<string, mixed>
     */
    private function previewAndImportFromSession(): array
    {
        $this->post(route('admin.students.import.store'), ['import_id' => session('student_import')['id']]);

        return session('student_import_result');
    }

    public function test_existing_student_is_assigned_class_without_losing_data_or_password(): void
    {
        $existing = User::factory()->create(['email' => 'siswa@ruangbelajar.test', 'role' => 'student', 'class_name' => null, 'nis' => null, 'name' => 'Siswa Demo']);
        $passwordHash = $existing->password;
        $this->actingAs($this->admin());

        $result = $this->previewAndImport($this->xlsxUpload([
            ['Nama', 'NIS', 'Email', 'Kelas'],
            ['Nama Berbeda', '5555', 'SISWA@ruangbelajar.test', 'XE2'],
        ]));

        $this->assertCount(0, $result['created']);
        $this->assertCount(1, $result['updated']);
        $existing->refresh();
        $this->assertSame('XE2', $existing->class_name);
        $this->assertSame('5555', $existing->nis);
        $this->assertSame('Siswa Demo', $existing->name);
        $this->assertSame($passwordHash, $existing->password);
        $this->assertFalse($existing->must_change_password);
        $this->assertSame(2, User::query()->count());
    }

    public function test_existing_email_is_matched_case_insensitively_during_import(): void
    {
        $existing = User::factory()->create([
            'email' => 'SISWA@sekolah.test',
            'role' => 'student',
            'class_name' => null,
        ]);
        $this->actingAs($this->admin());

        $result = $this->previewAndImport($this->csvUpload([
            ['name', 'nis', 'email', 'class_code'],
            ['Siswa Lama', '9090', 'siswa@sekolah.test', 'XE1'],
        ]));

        $this->assertCount(0, $result['created']);
        $this->assertCount(1, $result['updated']);
        $this->assertSame('XE1', $existing->fresh()->class_name);
        $this->assertSame(1, User::query()->where('role', 'student')->count());
    }

    public function test_bad_files_and_stale_previews_are_rejected(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.students.import.preview'), ['file' => UploadedFile::fake()->createWithContent('data.pdf', '%PDF')])
            ->assertSessionHasErrors(['file' => 'Format file harus .xlsx atau .csv.']);
        $this->post(route('admin.students.import.preview'), ['file' => UploadedFile::fake()->createWithContent('data.txt', "Nama,NIS,Email,Kelas\nSiswa,123,siswa@sekolah.test,XE1")])
            ->assertSessionHasErrors(['file' => 'Format file harus .xlsx atau .csv.']);
        $this->post(route('admin.students.import.preview'), ['file' => UploadedFile::fake()->createWithContent('rusak.xlsx', 'bukan zip')])
            ->assertSessionHasErrors('file');
        $this->post(route('admin.students.import.preview'), ['file' => $this->csvUpload([['Nama', 'Email'], ['A', 'a@b.test']])])
            ->assertSessionHasErrors('file');
        $this->post(route('admin.students.import.preview'), [])->assertSessionHasErrors('file');

        $this->post(route('admin.students.import.store'), ['import_id' => 'palsu'])->assertSessionHasErrors('file');

        $this->post(route('admin.students.import.preview'), ['file' => $this->xlsxUpload(self::SAMPLE_ROWS)]);
        $this->delete(route('admin.students.import.destroy'))->assertRedirect();
        $this->assertNull(session('student_import'));
        $this->assertSame(1, User::query()->count());
    }

    public function test_students_cannot_access_import(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student);

        $this->get(route('admin.students.import'))->assertForbidden();
        $this->get(route('admin.students.import.template', 'xlsx'))->assertForbidden();
        $this->post(route('admin.students.import.preview'), ['file' => $this->xlsxUpload(self::SAMPLE_ROWS)])->assertForbidden();
        $this->post(route('admin.students.import.store'), ['import_id' => 'x'])->assertForbidden();
        $this->assertSame(1, User::query()->count());
    }

    public function test_imported_student_logs_in_changes_password_and_sees_class(): void
    {
        $this->actingAs($this->admin());
        $result = $this->previewAndImport($this->xlsxUpload(self::SAMPLE_ROWS));
        auth()->logout();
        $this->flushSession();

        foreach (['XE1' => 'ayu.lestari@sekolah.test', 'XE2' => 'bima.saputra@sekolah.test', 'XE9' => 'citra.maharani@sekolah.test'] as $class => $email) {
            $initial = collect($result['created'])->firstWhere('email', $email)['password'];
            $student = User::query()->where('email', $email)->firstOrFail();

            $this->post('/login', ['email' => $email, 'password' => $initial])->assertRedirect(route('dashboard'));
            $this->assertAuthenticatedAs($student);

            // Kata sandi awal wajib diganti sebelum membuka halaman lain.
            $this->get(route('dashboard'))->assertRedirect(route('password.edit'));
            $this->get(route('materi.index'))->assertRedirect(route('password.edit'));
            $this->get(route('password.edit'))->assertOk()->assertSee('Ganti kata sandi');

            $this->put(route('password.update'), [
                'current_password' => $initial,
                'password' => $initial,
                'password_confirmation' => $initial,
            ])->assertSessionHasErrors('password');

            $this->put(route('password.update'), [
                'current_password' => $initial,
                'password' => 'SandiBaru123',
                'password_confirmation' => 'SandiBaru123',
                'class_name' => 'XE5',
                'role' => 'admin',
            ])->assertRedirect(route('dashboard'));

            $student->refresh();
            $this->assertFalse($student->must_change_password);
            $this->assertTrue(Hash::check('SandiBaru123', $student->password));
            $this->assertSame($class, $student->class_name);
            $this->assertSame('student', $student->role);

            $this->get(route('dashboard'))
                ->assertOk()
                ->assertSee($student->name)
                ->assertSee('Kelas '.$class)
                ->assertSee('NIS '.$student->nis)
                ->assertDontSee('Kelasmu belum diatur.');

            $this->post('/logout');
            $this->flushSession();
        }
    }

    public function test_student_cannot_change_own_class_through_any_request(): void
    {
        $student = User::factory()->create(['role' => 'student', 'class_name' => 'XE1']);
        $session = AttendanceToken::factory()->create(['subject_id' => null, 'class_name' => 'XE2', 'token' => 'ABCDE12345', 'token_hash' => hash_hmac('sha256', 'ABCDE12345', (string) config('app.key'))]);
        $this->actingAs($student);

        $this->patch(route('admin.student-classes.update'), ['user_id' => $student->id, 'class_name' => 'XE2'])->assertForbidden();
        $this->post(route('admin.students.import.store'), ['import_id' => 'x'])->assertForbidden();
        $this->get(route('dashboard', ['class_name' => 'XE2']))->assertOk()->assertSee('Kelas XE1')->assertDontSee('Kelas XE2');
        $this->post(route('attendances.store'), ['token' => 'ABCDE12345', 'class_name' => 'XE2'])->assertSessionHasErrors('token');

        $this->assertSame('XE1', $student->fresh()->class_name);
        $this->assertSame(0, $session->attendances()->count());
    }

    public function test_registration_ignores_class_and_nis_fields(): void
    {
        $this->post('/register', [
            'name' => 'Siswa Mandiri',
            'email' => 'mandiri@sekolah.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'class_name' => 'XE1',
            'nis' => '999',
            'must_change_password' => '0',
        ])->assertRedirect(route('dashboard'));

        $user = User::query()->where('email', 'mandiri@sekolah.test')->firstOrFail();
        $this->assertNull($user->class_name);
        $this->assertNull($user->nis);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kelas belum diatur')
            ->assertSee('Kelasmu belum diatur.');
    }
}
