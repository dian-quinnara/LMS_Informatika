<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InitialPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'password' => 'AdminLama123']);
    }

    private function student(string $class, bool $mustChange, string $password = 'SandiLama123'): User
    {
        return User::factory()->create([
            'role' => 'student',
            'class_name' => $class,
            'nis' => (string) fake()->unique()->numberBetween(1000, 9999),
            'password' => $password,
            'must_change_password' => $mustChange,
        ]);
    }

    public function test_admin_regenerates_initial_passwords_only_for_students_who_never_changed_them(): void
    {
        $admin = $this->admin();
        $pendingXe1 = $this->student('XE1', true);
        $pendingXe9 = $this->student('XE9', true);
        $activeStudent = $this->student('XE1', false, 'MilikSiswa123');

        $this->actingAs($admin)
            ->get(route('admin.students.import'))
            ->assertOk()
            ->assertSee('Buat ulang kata sandi awal')
            ->assertSee('Semua kelas · 2 siswa');

        $this->post(route('admin.students.initial-passwords.reset'), ['confirm' => '1'])
            ->assertRedirect(route('admin.students.import'))
            ->assertSessionHas('password_reset_result');
        $rows = collect(session('password_reset_result')['rows'])->keyBy('email');

        $this->assertCount(2, $rows);
        foreach ([$pendingXe1, $pendingXe9] as $student) {
            $student->refresh();
            $password = $rows[$student->email]['password'];
            $this->assertMatchesRegularExpression('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z0-9]{10}$/', $password);
            $this->assertTrue(Hash::check($password, $student->password));
            $this->assertFalse(Hash::check('SandiLama123', $student->password));
            $this->assertTrue($student->must_change_password);
        }

        $this->assertTrue(Hash::check('MilikSiswa123', $activeStudent->fresh()->password));
        $this->assertTrue(Hash::check('AdminLama123', $admin->fresh()->password));

        $this->get(route('admin.students.import'))
            ->assertOk()
            ->assertSee('Kredensial siswa')
            ->assertSee('Unduh kredensial')
            ->assertSee($rows[$pendingXe1->email]['password']);
    }

    public function test_reset_can_be_limited_to_one_class(): void
    {
        $xe1 = $this->student('XE1', true);
        $xe2 = $this->student('XE2', true);
        $this->actingAs($this->admin());

        $this->post(route('admin.students.initial-passwords.reset'), ['class_name' => 'XE1', 'confirm' => '1'])
            ->assertSessionHasNoErrors();

        $this->assertSame([$xe1->email], array_column(session('password_reset_result')['rows'], 'email'));
        $this->assertTrue(Hash::check('SandiLama123', $xe2->fresh()->password));
        $this->assertFalse(Hash::check('SandiLama123', $xe1->fresh()->password));
    }

    public function test_reset_is_validated(): void
    {
        $student = $this->student('XE1', true);
        $this->actingAs($this->admin());

        $this->post(route('admin.students.initial-passwords.reset'), [])->assertSessionHasErrors('confirm');
        $this->post(route('admin.students.initial-passwords.reset'), ['class_name' => 'XII F1', 'confirm' => '1'])->assertSessionHasErrors('class_name');
        $this->post(route('admin.students.initial-passwords.reset'), ['class_name' => 'XE5', 'confirm' => '1'])->assertSessionHasErrors('class_name');

        $this->assertTrue(Hash::check('SandiLama123', $student->fresh()->password));
    }

    public function test_students_cannot_reset_passwords(): void
    {
        $student = $this->student('XE1', false, 'MilikSiswa123');
        $other = $this->student('XE1', true);
        $this->actingAs($student);

        $this->post(route('admin.students.initial-passwords.reset'), ['confirm' => '1'])->assertForbidden();

        $this->assertTrue(Hash::check('SandiLama123', $other->fresh()->password));
    }

    public function test_student_logs_in_with_regenerated_password_and_must_change_it(): void
    {
        $student = $this->student('XE3', true);
        $this->actingAs($this->admin())->post(route('admin.students.initial-passwords.reset'), ['confirm' => '1']);
        $password = session('password_reset_result')['rows'][0]['password'];
        $this->post('/logout');
        $this->flushSession();

        $this->post('/login', ['email' => $student->email, 'password' => 'SandiLama123'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $student->email, 'password' => $password])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($student);
        $this->get(route('dashboard'))->assertRedirect(route('password.edit'));
    }
}
