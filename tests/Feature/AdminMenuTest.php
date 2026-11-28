<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Attempt;
use App\Models\BankQuestion;
use App\Models\Material;
use App\Models\Simulation;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminMenuTest extends TestCase
{
    use RefreshDatabase;

    private const MENU = [
        'Dashboard Admin' => 'dashboard',
        'Kelola Siswa' => 'admin.students.index',
        'Kelola Kelas' => 'admin.classes.index',
        'Kelola Presensi' => 'admin.attendance.index',
        'Kelola Materi &amp; Simulasi' => 'admin.content.index',
        'Kelola Soal &amp; Kuis' => 'admin.quizzes.index',
        'Hasil &amp; Analisis Belajar' => 'admin.results.index',
        'Pengumuman' => 'admin.announcements.index',
        'Pengaturan Sistem' => 'admin.settings.index',
    ];

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'password' => 'AdminLama123']);
    }

    private function student(?string $class = 'XE1', array $attributes = []): User
    {
        return User::factory()->create([
            'role' => 'student',
            'class_name' => $class,
            'nis' => (string) fake()->unique()->numberBetween(10000, 99999),
            ...$attributes,
        ]);
    }

    public function test_admin_sidebar_lists_nine_menus_and_every_page_loads(): void
    {
        $this->actingAs($this->admin());
        $response = $this->get(route('dashboard'))->assertOk();

        foreach (self::MENU as $label => $route) {
            $response->assertSee($label, false)->assertSee(route($route), false);
        }
        $response->assertSee('09');

        foreach (self::MENU as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_students_cannot_open_any_admin_menu(): void
    {
        $this->actingAs($this->student());

        foreach (array_slice(self::MENU, 1) as $route) {
            $this->get(route($route))->assertForbidden();
        }
        $this->get(route('admin.settings.backup'))->assertForbidden();
    }

    public function test_dashboard_summarises_students_classes_attendance_sessions_materials_and_quizzes(): void
    {
        $this->travelTo(Carbon::parse('2026-11-27 08:00'));
        $student = $this->student('XE2');
        $this->student('XE2');
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.attendance-sessions.store'), [
            'class_name' => 'XE2', 'title' => 'Sesi Pagi',
            'starts_at' => now()->subMinutes(5)->format('Y-m-d\TH:i'), 'expires_at' => now()->addHour()->format('Y-m-d\TH:i'),
        ])->assertRedirect(route('admin.attendance.index'));
        $token = session('attendance_session')['token'];
        $this->actingAs($student)->post(route('attendances.store'), ['token' => $token])->assertSessionHasNoErrors();
        $simulation = Simulation::factory()->create(['title' => 'Kuis Dasbor']);
        Attempt::query()->create(['user_id' => $student->id, 'simulation_id' => $simulation->id, 'started_at' => now(), 'submitted_at' => now(), 'score' => 80, 'status' => 'completed']);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('XE2 · Sesi Pagi')
            ->assertSee('1/2 hadir')
            ->assertSee('Kuis Dasbor')
            ->assertSee('80%')
            ->assertDontSee($token);
    }

    public function test_admin_adds_edits_deactivates_and_resets_student_accounts(): void
    {
        config(['students.default_password' => null]);
        $this->actingAs($this->admin());

        $this->post(route('admin.students.store'), ['name' => 'Siswa Baru', 'nis' => '7001', 'email' => 'Baru@Sekolah.test', 'class_name' => 'XE4'])
            ->assertRedirect(route('admin.students.index'));
        $credential = session('student_credential');
        $student = User::query()->where('nis', '7001')->firstOrFail();
        $this->assertSame('student', $student->role);
        $this->assertSame('baru@sekolah.test', $student->email);
        $this->assertSame('XE4', $student->class_name);
        $this->assertTrue($student->must_change_password);
        $this->assertTrue(Hash::check($credential['password'], $student->password));

        $this->post(route('admin.students.store'), ['name' => 'Duplikat', 'nis' => '7001', 'email' => 'lain@sekolah.test'])
            ->assertSessionHasErrors('nis');

        $this->put(route('admin.students.update', $student), ['name' => 'Siswa Diubah', 'nis' => '7002', 'email' => 'baru@sekolah.test', 'class_name' => 'XE5'])
            ->assertRedirect(route('admin.students.index'));
        $this->assertSame(['Siswa Diubah', '7002', 'XE5'], [$student->fresh()->name, $student->fresh()->nis, $student->fresh()->class_name]);

        $this->get(route('admin.students.index', ['class' => 'XE5']))->assertOk()->assertSee('Siswa Diubah');
        $this->get(route('admin.students.index', ['class' => 'XE1']))->assertOk()->assertDontSee('Siswa Diubah');

        $this->patch(route('admin.students.status', $student))->assertRedirect();
        $this->assertFalse($student->fresh()->is_active);

        $this->post(route('admin.students.password', $student), ['mode' => 'random', 'confirm' => '1'])->assertSessionHasNoErrors();
        $newPassword = session('student_credential')['password'];
        $this->assertTrue(Hash::check($newPassword, $student->fresh()->password));

        $this->post('/logout');
        $this->flushSession();
        $this->post('/login', ['email' => 'baru@sekolah.test', 'password' => $newPassword])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($this->admin())->patch(route('admin.students.status', $student));
        $this->post('/logout');
        $this->flushSession();
        $this->post('/login', ['email' => 'baru@sekolah.test', 'password' => $newPassword])->assertRedirect(route('dashboard'));
    }

    public function test_deactivated_student_is_logged_out_on_next_request(): void
    {
        $student = $this->student();
        $this->actingAs($student)->get(route('dashboard'))->assertOk();

        $student->forceFill(['is_active' => false])->save();

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_cannot_manage_admin_accounts_from_student_menu(): void
    {
        $otherAdmin = $this->admin();
        $this->actingAs($this->admin());

        $this->get(route('admin.students.edit', $otherAdmin))->assertNotFound();
        $this->patch(route('admin.students.status', $otherAdmin))->assertNotFound();
        $this->assertNotFalse($otherAdmin->fresh()->is_active);
    }

    public function test_class_pages_show_members_and_move_students(): void
    {
        $ani = $this->student('XE1', ['name' => 'Ani Satu']);
        $budi = $this->student('XE1', ['name' => 'Budi Satu']);
        $tanpa = $this->student(null, ['name' => 'Cici Tanpa Kelas']);
        $admin = $this->admin();
        $this->actingAs($admin);

        $this->get(route('admin.classes.index'))->assertOk()->assertSee('Kelas XE1')->assertSee('2 siswa')->assertSee('Cici Tanpa Kelas');
        $this->get(route('admin.classes.show', 'XE1'))->assertOk()->assertSee('Ani Satu')->assertSee('Budi Satu')->assertDontSee('Cici Tanpa Kelas');
        $this->get(route('admin.classes.show', 'XII F1'))->assertNotFound();

        $this->patch(route('admin.classes.move'), ['user_ids' => [$ani->id, $tanpa->id], 'class_name' => 'XE9'])->assertSessionHasNoErrors();
        $this->assertSame('XE9', $ani->fresh()->class_name);
        $this->assertSame('XE9', $tanpa->fresh()->class_name);
        $this->assertSame('XE1', $budi->fresh()->class_name);

        $this->patch(route('admin.classes.move'), ['user_ids' => [$admin->id], 'class_name' => 'XE2'])->assertSessionHasErrors('user_ids.0');
        $this->patch(route('admin.classes.move'), ['user_ids' => [$budi->id], 'class_name' => 'XI F1'])->assertSessionHasErrors('class_name');
        $this->assertNull($admin->fresh()->class_name);
    }

    public function test_attendance_report_can_be_downloaded_as_csv(): void
    {
        $this->travelTo(Carbon::parse('2026-11-27 07:00'));
        $hadir = $this->student('XE3', ['name' => 'Dina Hadir']);
        $this->student('XE3', ['name' => '=Eko Absen']);
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.attendance-sessions.store'), [
            'class_name' => 'XE3', 'title' => 'Pertemuan 5',
            'starts_at' => now()->format('Y-m-d\TH:i'), 'expires_at' => now()->addHour()->format('Y-m-d\TH:i'),
        ]);
        $token = session('attendance_session')['token'];
        $this->travelTo(Carbon::parse('2026-11-27 07:10'));
        $this->actingAs($hadir)->post(route('attendances.store'), ['token' => $token])->assertSessionHasNoErrors();

        $response = $this->actingAs($admin)->get(route('admin.attendance-report.download', ['class_name' => 'XE3', 'date_from' => '2026-11-27', 'date_to' => '2026-11-27']));
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Pertemuan 5 27/11/2026 07:00', $csv);
        $this->assertStringContainsString('Dina Hadir', $csv);
        $this->assertStringContainsString('Hadir 07:10', $csv);
        $this->assertStringContainsString("'=Eko Absen", $csv);
        $this->assertStringContainsString('Belum presensi', $csv);
        $this->assertStringNotContainsString($token, $csv);

        $this->get(route('admin.attendance-report.download'))->assertSessionHasErrors('class_name');
    }

    public function test_admin_manages_materials_with_attachment_order_and_publication(): void
    {
        Storage::fake('local');
        $subject = Subject::factory()->create(['name' => 'Topik Sorting']);
        $student = $this->student();
        $this->actingAs($this->admin());

        $this->post(route('admin.content.materials.store'), [
            'subject_id' => $subject->id, 'title' => 'Bubble Sort', 'summary' => 'Pengurutan sederhana', 'content' => 'Isi materi',
            'sort_order' => 2, 'attachment' => UploadedFile::fake()->create('ringkasan.pdf', 50, 'application/pdf'),
        ])->assertRedirect(route('admin.content.index'));
        $material = Material::query()->where('title', 'Bubble Sort')->firstOrFail();
        $this->assertFalse($material->is_published);
        $this->assertSame(2, $material->sort_order);
        $this->assertSame('ringkasan.pdf', $material->attachment_name);
        Storage::disk('local')->assertExists($material->attachment_path);

        $this->post(route('admin.content.materials.store'), ['subject_id' => $subject->id, 'title' => 'Skrip', 'attachment' => UploadedFile::fake()->create('x.html', 1, 'text/html')])
            ->assertSessionHasErrors('attachment');

        $this->get(route('admin.content.index'))->assertOk()->assertSee('Topik Sorting')->assertSee('02 · Bubble Sort')->assertSee('Draf');
        $this->actingAs($student)->get(route('materials.show', $material))->assertNotFound();
        $this->get(route('materials.attachment', $material))->assertNotFound();

        $this->actingAs($this->admin())->patch(route('admin.content.materials.publication', $material))->assertRedirect();
        $this->assertTrue($material->fresh()->is_published);
        $this->put(route('admin.content.materials.update', $material), ['subject_id' => $subject->id, 'title' => 'Bubble Sort Lanjut', 'sort_order' => 1, 'is_published' => '1'])
            ->assertRedirect(route('admin.content.index'));
        $this->assertSame('Bubble Sort Lanjut', $material->fresh()->title);

        $this->actingAs($student)->get(route('materials.show', $material))->assertOk()->assertSee('ringkasan.pdf');
        $this->get(route('materials.attachment', $material))->assertOk();
        $this->get(route('admin.content.index'))->assertForbidden();
    }

    public function test_simulation_settings_can_be_edited(): void
    {
        $simulation = Simulation::factory()->create(['is_published' => false]);
        $this->actingAs($this->admin());

        $this->put(route('admin.content.simulations.update', $simulation), [
            'subject_id' => $simulation->subject_id, 'title' => 'Simulasi Baru', 'duration_minutes' => 15, 'max_attempts' => 2, 'is_published' => '1',
        ])->assertRedirect(route('admin.content.index'));

        $simulation->refresh();
        $this->assertSame(['Simulasi Baru', 15, 2, true], [$simulation->title, $simulation->duration_minutes, $simulation->max_attempts, $simulation->is_published]);
    }

    public function test_question_bank_builds_quiz_with_category_explanation_and_attempt_limit(): void
    {
        $subject = Subject::factory()->create();
        $material = Material::factory()->create(['subject_id' => $subject->id, 'title' => 'Binary Search']);
        $this->actingAs($this->admin());

        foreach (['LOTS' => 'Apa syarat binary search?', 'HOTS' => 'Analisis langkah binary search'] as $category => $prompt) {
            $this->post(route('admin.question-bank.store'), [
                'material' => 'material:'.$material->id, 'category' => $category, 'prompt' => $prompt,
                'choices' => ['A' => 'Data terurut', 'B' => 'Data acak', 'C' => 'Data kosong', 'D' => 'Data ganda'],
                'correct_answer' => 'A', 'explanation' => 'Binary search butuh data terurut.', 'points' => 1,
            ])->assertSessionHasNoErrors();
        }
        $this->post(route('admin.question-bank.store'), ['material' => 'material:'.$material->id, 'category' => 'MOTS', 'prompt' => 'x', 'choices' => ['A' => '1'], 'correct_answer' => 'E', 'points' => 1])
            ->assertSessionHasErrors(['category', 'choices', 'correct_answer']);

        $bank = BankQuestion::query()->orderBy('id')->get();
        $this->get(route('admin.quizzes.index', ['category' => 'HOTS']))->assertOk()->assertSee('Analisis langkah binary search')->assertDontSee('Apa syarat binary search?');

        $this->post(route('admin.quizzes.store'), [
            'subject_id' => $subject->id, 'title' => 'Kuis Binary', 'duration_minutes' => 10, 'max_attempts' => 1,
            'question_ids' => $bank->pluck('id')->all(), 'is_published' => '1',
        ])->assertSessionHasNoErrors();
        $quiz = Simulation::query()->where('title', 'Kuis Binary')->firstOrFail();
        $this->assertSame(1, $quiz->max_attempts);
        $this->assertSame(['LOTS', 'HOTS'], $quiz->questions->pluck('category')->all());
        $this->assertSame('Binary search butuh data terurut.', $quiz->questions->first()->explanation);

        // Mengubah bank soal tidak mengubah soal kuis yang sudah dibuat.
        $this->put(route('admin.question-bank.update', $bank->first()), [
            'material' => 'material:'.$material->id, 'category' => 'LOTS', 'prompt' => 'Pertanyaan diganti',
            'choices' => ['A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd'], 'correct_answer' => 'B', 'points' => 2,
        ])->assertSessionHasNoErrors();
        $this->assertSame('Apa syarat binary search?', $quiz->questions()->first()->prompt);

        $student = $this->student();
        $this->actingAs($student)->post(route('simulations.begin', $quiz))->assertRedirect();
        $attempt = Attempt::query()->where('user_id', $student->id)->firstOrFail();
        // Memulai ulang melanjutkan percobaan yang belum dikirim.
        $this->post(route('simulations.begin', $quiz))->assertRedirect(route('attempts.show', $attempt));
        $answers = $quiz->questions->mapWithKeys(fn ($question) => [$question->id => 'A'])->all();
        $this->post(route('attempts.submit', $attempt), ['answers' => $answers])->assertRedirect(route('attempts.show', $attempt));
        $this->get(route('attempts.show', $attempt))->assertOk()->assertSee('Pembahasan: Binary search butuh data terurut.');

        $this->post(route('simulations.begin', $quiz))->assertSessionHasErrors('attempt');
        $this->assertSame(1, Attempt::query()->where('user_id', $student->id)->count());

        $this->actingAs($this->admin())->put(route('admin.quizzes.update', $quiz), ['duration_minutes' => 30, 'max_attempts' => ''])->assertSessionHasNoErrors();
        $this->assertNull($quiz->fresh()->max_attempts);
        $this->assertFalse($quiz->fresh()->is_published);
    }

    public function test_results_page_analyses_scores_questions_materials_and_mentoring(): void
    {
        $material = Material::factory()->create(['title' => 'Materi Sulit']);
        $quiz = Simulation::factory()->create(['title' => 'Kuis Analisis', 'is_published' => true]);
        $bank = BankQuestion::query()->create(['material_id' => $material->id, 'category' => 'HOTS', 'prompt' => 'Soal analisis', 'choices' => ['A' => '1', 'B' => '2', 'C' => '3', 'D' => '4'], 'correct_answer' => 'A', 'points' => 1]);
        $question = $quiz->questions()->create(['bank_question_id' => $bank->id, 'category' => 'HOTS', 'prompt' => 'Soal analisis', 'choices' => $bank->choices, 'correct_answer' => 'A', 'points' => 1, 'position' => 1]);
        $lemah = $this->student('XE6', ['name' => 'Fajar Perlu Bantuan']);
        $kuat = $this->student('XE7', ['name' => 'Gita Tuntas']);

        foreach ([[$lemah, 'B', 0], [$kuat, 'A', 100]] as [$student, $answer, $score]) {
            $attempt = Attempt::query()->create(['user_id' => $student->id, 'simulation_id' => $quiz->id, 'started_at' => now(), 'submitted_at' => now(), 'score' => $score, 'status' => 'completed']);
            $attempt->answers()->create(['question_id' => $question->id, 'answer' => $answer, 'is_correct' => $answer === 'A', 'points_earned' => $answer === 'A' ? 1 : 0]);
        }

        $this->actingAs($this->admin())->get(route('admin.results.index'))
            ->assertOk()
            ->assertSee('Fajar Perlu Bantuan')
            ->assertSee('Rata-rata 0')
            ->assertSee('Materi Sulit')
            ->assertSee('50% benar')
            ->assertSee('Soal analisis')
            ->assertSee('Kuis Analisis');

        $this->get(route('admin.results.index', ['class' => 'XE7']))
            ->assertOk()
            ->assertSee('Gita Tuntas')
            ->assertDontSee('Fajar Perlu Bantuan');
    }

    public function test_announcements_are_managed_by_admin_and_shown_to_target_students(): void
    {
        $this->actingAs($this->admin());
        $this->post(route('admin.announcements.store'), ['title' => 'Info Semua', 'body' => 'Untuk semua kelas', 'is_published' => '1'])->assertSessionHasNoErrors();
        $this->post(route('admin.announcements.store'), ['title' => 'Info XE2', 'body' => 'Khusus XE2', 'class_name' => 'XE2', 'is_published' => '1']);
        $this->post(route('admin.announcements.store'), ['title' => 'Info Draf', 'body' => 'Belum tampil']);
        $this->post(route('admin.announcements.store'), ['title' => 'Salah', 'body' => 'x', 'class_name' => 'XII F1'])->assertSessionHasErrors('class_name');

        $draft = Announcement::query()->where('title', 'Info Draf')->firstOrFail();
        $this->put(route('admin.announcements.update', $draft), ['title' => 'Info Draf Diubah', 'body' => 'Masih draf'])->assertSessionHasNoErrors();
        $this->assertSame('Info Draf Diubah', $draft->fresh()->title);

        $this->actingAs($this->student('XE1'))->get(route('dashboard'))
            ->assertOk()->assertSee('Info Semua')->assertDontSee('Info XE2')->assertDontSee('Info Draf Diubah');
        $this->actingAs($this->student('XE2'))->get(route('dashboard'))
            ->assertOk()->assertSee('Info Semua')->assertSee('Info XE2');

        $this->actingAs($this->admin())->delete(route('admin.announcements.destroy', Announcement::query()->where('title', 'Info Semua')->firstOrFail()))->assertRedirect();
        $this->assertDatabaseMissing('announcements', ['title' => 'Info Semua']);
    }

    public function test_system_settings_update_identity_profile_and_backup_excludes_secrets(): void
    {
        $admin = $this->admin();
        $this->student('XE1', ['name' => 'Siswa Cadangan']);
        $this->actingAs($admin);

        $this->put(route('admin.settings.identity'), ['site_name' => 'LMS Informatika', 'school_name' => 'SMA Contoh'])->assertSessionHasNoErrors();
        $this->get(route('admin.settings.index'))->assertOk()->assertSee('LMS Informatika')->assertSee('SMA CONTOH');

        $this->put(route('admin.settings.profile'), ['name' => 'Admin Baru', 'email' => 'admin.baru@sekolah.test', 'current_password' => 'salah'])
            ->assertSessionHasErrors('current_password');
        $this->put(route('admin.settings.profile'), ['name' => 'Admin Baru', 'email' => 'admin.baru@sekolah.test', 'current_password' => 'AdminLama123'])
            ->assertSessionHasNoErrors();
        $this->assertSame('admin.baru@sekolah.test', $admin->fresh()->email);

        $backup = $this->get(route('admin.settings.backup'))->assertOk()->streamedContent();
        $data = json_decode($backup, true, flags: JSON_THROW_ON_ERROR);
        $this->assertContains('Siswa Cadangan', array_column($data['tables']['users'], 'name'));
        $this->assertArrayNotHasKey('password', $data['tables']['users'][0]);
        $this->assertArrayNotHasKey('remember_token', $data['tables']['users'][0]);
        $this->assertStringNotContainsString($admin->fresh()->password, $backup);
        $this->assertSame('LMS Informatika', collect($data['tables']['settings'])->firstWhere('key', 'site_name')['value']);
    }
}
