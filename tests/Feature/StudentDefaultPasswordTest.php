<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\SimpleXlsx;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Login siswa memakai email dari file Excel dan kata sandi default sekolah (dari .env),
 * bukan NIS. Siswa wajib mengganti kata sandi saat login pertama.
 */
class StudentDefaultPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const DEFAULT_PASSWORD = 'DefaultTes_2026';

    protected function setUp(): void
    {
        parent::setUp();
        config(['students.default_password' => self::DEFAULT_PASSWORD]);
    }

    /**
     * @param  list<list<string>>  $rows
     * @return array<string, mixed>
     */
    private function import(array $rows): array
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $file = UploadedFile::fake()->createWithContent('siswa.xlsx', SimpleXlsx::build(['Sheet1' => ['rows' => $rows]]));
        $this->post(route('admin.students.import.preview'), ['file' => $file])->assertRedirect(route('admin.students.import'));
        $this->post(route('admin.students.import.store'), ['import_id' => session('student_import')['id']]);
        $result = session('student_import_result');
        $this->post('/logout');
        $this->flushSession();

        return $result;
    }

    public function test_new_students_get_default_password_not_nis_and_it_is_not_exposed(): void
    {
        $result = $this->import([
            ['NIS', 'Nama siswa', 'Kelas', 'Email'],
            ['8001', 'Siswa Satu', 'XE1', 'siswa.satu@sekolah.test'],
            ['8009', 'Siswa Sembilan', 'XE9', 'siswa.sembilan@sekolah.test'],
        ]);

        $this->assertCount(2, $result['created']);
        foreach ($result['created'] as $row) {
            $this->assertNull($row['password']);
            $student = User::query()->where('email', $row['email'])->firstOrFail();
            $this->assertTrue(Hash::check(self::DEFAULT_PASSWORD, $student->password));
            $this->assertFalse(Hash::check($row['nis'], $student->password));
            $this->assertTrue($student->must_change_password);
        }
        $this->assertStringNotContainsString(self::DEFAULT_PASSWORD, base64_decode($result['credentials_csv']));

        // NIS tidak bisa dipakai login; email + kata sandi default bisa, lalu wajib ganti kata sandi.
        $this->post('/login', ['email' => 'siswa.satu@sekolah.test', 'password' => '8001'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'siswa.satu@sekolah.test', 'password' => self::DEFAULT_PASSWORD])->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertRedirect(route('password.edit'));
    }

    public function test_existing_student_matched_by_nis_gets_new_email_and_default_password(): void
    {
        $student = User::factory()->create([
            'name' => 'Siswa Lama', 'email' => 'lama@sekolah.test', 'nis' => '8668', 'role' => 'student',
            'class_name' => 'XE1', 'password' => '8668', 'must_change_password' => true,
        ]);

        $result = $this->import([
            ['NIS', 'Nama siswa', 'Kelas', 'Email'],
            ['8668', 'Siswa Lama', 'XE1', 'siswa.baru@sekolah.test'],
        ]);

        $this->assertCount(0, $result['created']);
        $this->assertCount(1, $result['updated']);
        $this->assertSame('lama@sekolah.test', $result['updated'][0]['previous_email']);
        $this->assertTrue($result['updated'][0]['password_reset']);
        $student->refresh();
        $this->assertSame('siswa.baru@sekolah.test', $student->email);
        $this->assertSame('8668', $student->nis);
        $this->assertTrue(Hash::check(self::DEFAULT_PASSWORD, $student->password));
        $this->assertSame(1, User::query()->where('nis', '8668')->count());

        $this->post('/login', ['email' => 'lama@sekolah.test', 'password' => self::DEFAULT_PASSWORD])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'siswa.baru@sekolah.test', 'password' => '8668'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'siswa.baru@sekolah.test', 'password' => self::DEFAULT_PASSWORD])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($student);
    }

    public function test_student_who_already_set_own_password_keeps_it_when_email_changes(): void
    {
        $student = User::factory()->create([
            'email' => 'lama2@sekolah.test', 'nis' => '8701', 'role' => 'student', 'class_name' => 'XE1',
            'password' => 'MilikSendiri99', 'must_change_password' => false,
        ]);

        $result = $this->import([
            ['NIS', 'Nama siswa', 'Kelas', 'Email'],
            ['8701', 'Siswa Aktif', 'XE1', 'aktif.baru@sekolah.test'],
        ]);

        $this->assertFalse($result['updated'][0]['password_reset']);
        $student->refresh();
        $this->assertSame('aktif.baru@sekolah.test', $student->email);
        $this->assertTrue(Hash::check('MilikSendiri99', $student->password));
        $this->assertFalse($student->must_change_password);
        $this->assertNull($result['credentials_csv']);
    }

    public function test_nis_owned_by_admin_or_email_taken_is_rejected(): void
    {
        User::factory()->create(['role' => 'admin', 'nis' => '9000', 'email' => 'admin.lain@sekolah.test']);
        User::factory()->create(['role' => 'student', 'nis' => '9001', 'email' => 'pemilik@sekolah.test']);
        User::factory()->create(['role' => 'student', 'nis' => '9002', 'email' => 'lain@sekolah.test']);

        $result = $this->import([
            ['NIS', 'Nama siswa', 'Kelas', 'Email'],
            ['9000', 'Coba Admin', 'XE1', 'coba.admin@sekolah.test'],
            ['9001', 'Rebut Email', 'XE1', 'lain@sekolah.test'],
        ]);

        $this->assertCount(2, $result['failed']);
        $this->assertSame('NIS sudah dipakai akun admin.', $result['failed'][0]['errors'][0]);
        $this->assertSame('pemilik@sekolah.test', User::query()->where('nis', '9001')->value('email'));
        $this->assertSame('lain@sekolah.test', User::query()->where('nis', '9002')->value('email'));
        $this->assertSame('admin', User::query()->where('nis', '9000')->value('role'));
    }

    public function test_new_password_cannot_be_default_password_or_contain_nis(): void
    {
        $student = User::factory()->create([
            'email' => 'ganti@sekolah.test', 'nis' => '8123', 'role' => 'student', 'class_name' => 'XE2',
            'password' => self::DEFAULT_PASSWORD, 'must_change_password' => true,
        ]);
        $this->actingAs($student);

        foreach ([self::DEFAULT_PASSWORD, 'abc8123xyz'] as $weak) {
            $this->put(route('password.update'), [
                'current_password' => self::DEFAULT_PASSWORD,
                'password' => $weak,
                'password_confirmation' => $weak,
            ])->assertSessionHasErrors('password');
        }
        $this->assertTrue($student->fresh()->must_change_password);

        $this->put(route('password.update'), [
            'current_password' => self::DEFAULT_PASSWORD,
            'password' => 'SandiKuat2026',
            'password_confirmation' => 'SandiKuat2026',
        ])->assertRedirect(route('dashboard'));
        $this->assertFalse($student->fresh()->must_change_password);
        $this->get(route('dashboard'))->assertOk()->assertSee('Kelas XE2');
    }
}
