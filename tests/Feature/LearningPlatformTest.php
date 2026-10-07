<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\AttendanceToken;
use App\Models\Material;
use App\Models\Question;
use App\Models\Simulation;
use App\Models\Subject;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SearchingMaterialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LearningPlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_login_and_register_pages(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk ke akunmu');
        $this->get('/register')->assertOk()->assertSee('Daftar ke Ruang Belajar');
    }

    public function test_public_registration_always_assigns_student_role(): void
    {
        $this->post('/register', [
            'name' => 'Admin Pertama',
            'email' => 'admin@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'admin@example.test', 'role' => 'student']);
        $this->post('/logout')->assertRedirect(route('login'));

        $this->post('/register', [
            'name' => 'Siswa Baru',
            'email' => 'student@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['email' => 'student@example.test', 'role' => 'student']);
    }

    public function test_operator_can_promote_an_existing_user_from_the_console(): void
    {
        $user = User::factory()->create(['role' => 'student']);

        $this->artisan('user:make-admin', ['email' => $user->email])->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'admin']);
    }

    public function test_role_seeder_creates_known_local_accounts_idempotently(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::query()->where('email', 'admin@ruangbelajar.test')->firstOrFail();
        $student = User::query()->where('email', 'siswa@ruangbelajar.test')->firstOrFail();

        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('AdminRuang123!', $admin->password));
        $this->assertSame('student', $student->role);
        $this->assertTrue(Hash::check('SiswaRuang123!', $student->password));

        $this->post('/login', [
            'email' => 'admin@ruangbelajar.test',
            'password' => 'AdminRuang123!',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->post('/logout')->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => 'siswa@ruangbelajar.test',
            'password' => 'SiswaRuang123!',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($student);

        $this->seed(RoleSeeder::class);

        $this->assertDatabaseCount('users', 2);
    }

    public function test_role_seeder_does_not_create_demo_accounts_in_production(): void
    {
        config()->set('app.env', 'production');

        $this->seed(RoleSeeder::class);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_student_cannot_open_admin_content_management(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)->get(route('admin.manage'))->assertForbidden();
        $this->post(route('admin.attendance-tokens.store'), [])->assertForbidden();
    }

    public function test_student_dashboard_renders_learning_content(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $subject = Subject::factory()->create(['name' => 'Fisika Dasar']);
        AttendanceToken::factory()->for($subject)->create([
            'starts_at' => now()->subMinutes(5),
            'expires_at' => now()->addMinutes(25),
        ]);

        $this->actingAs($student)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Presensi siswa')
            ->assertSee('Masukkan token kelas')
            ->assertSee('Berlangsung')
            ->assertSee('Fisika Dasar');
    }

    public function test_student_menu_renders_only_the_selected_content(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $material = Material::factory()->create(['title' => 'Dasar Algoritma']);
        $simulation = Simulation::factory()->create(['title' => 'Logika Komputasi']);
        Question::factory()->for($simulation)->create();

        $this->actingAs($student)
            ->get(route('dashboard', ['section' => 'materials']))
            ->assertOk()
            ->assertSee('Materi belajar Informatika')
            ->assertSee('MATERI 01')
            ->assertSee('SEARCHING')
            ->assertSee('Pelajari algoritma pencarian data, mulai dari Sequential Search hingga Binary Search.')
            ->assertSee('MATERI 02')
            ->assertSee('SORTING')
            ->assertSee('Pelajari berbagai algoritma pengurutan data, mulai dari Bubble Sort, Selection Sort, hingga Insertion Sort.')
            ->assertSee('MATERI 03')
            ->assertSee('STRUKTUR DATA')
            ->assertSee('Pelajari konsep struktur data Stack dan Queue serta penerapannya dalam pemrograman.')
            ->assertSee('Mulai Belajar')
            ->assertSee(route('materi.searching'), false)
            ->assertSee(route('materi.sorting'), false)
            ->assertSee(route('materi.struktur-data'), false)
            ->assertDontSee('Masukkan token kelas')
            ->assertDontSee('Dasar Algoritma')
            ->assertDontSee('Logika Komputasi');

        $this->get(route('dashboard', ['section' => 'simulations']))
            ->assertOk()
            ->assertSee('Logika Komputasi')
            ->assertDontSee('Masukkan token kelas')
            ->assertDontSee('Dasar Algoritma');

        $this->get(route('dashboard', ['section' => 'attendance']))
            ->assertOk()
            ->assertSee('Masukkan token kelas')
            ->assertDontSee('Dasar Algoritma')
            ->assertDontSee('Logika Komputasi');
    }

    public function test_student_can_navigate_to_each_learning_topic(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('materi.index'))
            ->assertRedirect(route('dashboard', ['section' => 'materials']));

        $this->get(route('materi.searching'))
            ->assertOk()
            ->assertSee('SEARCHING')
            ->assertSee('Sequential Search')
            ->assertSee('Binary Search')
            ->assertSee(route('materi.searching.sequential'), false)
            ->assertSee(route('materi.searching.binary'), false)
            ->assertSeeInOrder([
                '<strong>Sequential Search</strong>',
                '<strong>Binary Search</strong>',
                '<strong>Simulasi</strong>',
                '<strong>Latihan soal LOTS</strong>',
                '<strong>Latihan soal HOTS</strong>',
            ], false)
            ->assertSee(route('materi.searching.quiz'), false)
            ->assertSee(route('materi.searching.hots-quiz'), false)
            ->assertSee('href="'.route('dashboard', ['section' => 'simulations']).'"', false)
            ->assertDontSee('Latihan LOTS')
            ->assertDontSee('Latihan HOTS')
            ->assertDontSee('Array terurut [3, 7, 11, 18, 25, 31, 42] dicari dengan Binary Search');

        $this->get(route('materi.sorting'))
            ->assertOk()
            ->assertSee('SORTING')
            ->assertSee('Bubble Sort')
            ->assertSee('Selection Sort')
            ->assertSee('Insertion Sort');

        $this->get(route('materi.struktur-data'))
            ->assertOk()
            ->assertSee('STRUKTUR DATA')
            ->assertSee('Stack')
            ->assertSee('Queue');
    }

    public function test_student_can_read_searching_lessons_from_slug_routes(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $this->seed(SearchingMaterialSeeder::class);
        $this->seed(SearchingMaterialSeeder::class);

        $this->assertDatabaseCount('materials', 2);
        $this->assertDatabaseHas('materials', ['slug' => 'sequential-search', 'sort_order' => 1]);
        $this->assertDatabaseHas('materials', ['slug' => 'binary-search', 'sort_order' => 2]);

        $this->actingAs($student)
            ->get(route('materi.searching.sequential'))
            ->assertOk()
            ->assertSee('SEQUENTIAL SEARCH')
            ->assertSee('Tujuan Pembelajaran')
            ->assertSee('Bandingkan 25 dengan 18.')
            ->assertSee('PROCEDURE SequentialSearch')
            ->assertSee('def sequential_search')
            ->assertSee('O(n)')
            ->assertSee('Pertanyaan Refleksi');

        $this->get(route('materi.searching.binary'))
            ->assertOk()
            ->assertSee('BINARY SEARCH')
            ->assertSee('Syarat Binary Search')
            ->assertSee('low=0, high=6, mid=3')
            ->assertSee('def binary_search')
            ->assertSee('O(log n)')
            ->assertSee('Perbandingan Sequential Search dan Binary Search')
            ->assertSee('Pertanyaan Refleksi');
    }

    public function test_admin_dashboard_renders_management_summary(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Selamat datang, '.$admin->name)
            ->assertSee('Kelola konten');

        $this->get(route('admin.manage'))
            ->assertOk()
            ->assertSee('Buat sesi presensi')
            ->assertSee('datetime-local');
    }

    public function test_admin_can_create_a_subject_material_simulation_and_question(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $this->post(route('admin.subjects.store'), [
            'code' => 'BIO-01',
            'name' => 'Biologi Dasar',
            'description' => 'Dasar kehidupan',
        ])->assertRedirect();
        $subject = Subject::query()->where('code', 'BIO-01')->firstOrFail();

        $this->post(route('admin.materials.store'), [
            'subject_id' => $subject->id,
            'title' => 'Sel dan jaringan',
            'summary' => 'Pengantar struktur kehidupan',
            'content' => 'Isi materi biologi.',
        ])->assertRedirect();
        $material = Material::query()->where('title', 'Sel dan jaringan')->firstOrFail();

        $this->post(route('admin.simulations.store'), [
            'subject_id' => $subject->id,
            'title' => 'Latihan sel',
            'duration_minutes' => 15,
            'is_published' => '1',
        ])->assertRedirect();
        $simulation = Simulation::query()->where('title', 'Latihan sel')->firstOrFail();

        $this->post(route('admin.questions.store'), [
            'simulation_id' => $simulation->id,
            'prompt' => 'Apa unit dasar kehidupan?',
            'choices' => ['A' => 'Organ', 'B' => 'Sel'],
            'correct_answer' => 'B',
            'points' => 2,
        ])->assertRedirect();

        $this->assertDatabaseHas('materials', ['id' => $material->id, 'subject_id' => $subject->id]);
        $this->assertDatabaseHas('simulations', ['id' => $simulation->id, 'is_published' => true]);
        $this->assertDatabaseHas('questions', ['simulation_id' => $simulation->id, 'correct_answer' => 'B']);
    }

    public function test_student_can_read_material_content(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $material = Material::factory()->create([
            'title' => 'Sistem tata surya',
            'content' => 'Planet mengorbit bintang.',
        ]);

        $this->actingAs($student)
            ->get(route('materials.show', $material))
            ->assertOk()
            ->assertSee('Sistem tata surya')
            ->assertSee('Planet mengorbit bintang.');
    }

    public function test_admin_can_create_a_scheduled_token_and_student_can_use_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);
        $subject = Subject::factory()->create();
        $startsAt = now()->addMinutes(10);
        $expiresAt = now()->addHour();

        $response = $this->actingAs($admin)->post(route('admin.attendance-tokens.store'), [
            'subject_id' => $subject->id,
            'starts_at' => $startsAt->format('Y-m-d\\TH:i'),
            'expires_at' => $expiresAt->format('Y-m-d\\TH:i'),
        ])->assertRedirect()->assertSessionHas('attendance_token');
        $plainToken = session('attendance_token');
        $this->assertIsString($plainToken);
        $attendanceToken = AttendanceToken::query()->where('subject_id', $subject->id)->firstOrFail();
        $this->assertSame(hash_hmac('sha256', $plainToken, (string) config('app.key')), $attendanceToken->token_hash);
        $this->assertArrayNotHasKey('token_hash', $attendanceToken->toArray());
        $this->travelTo($attendanceToken->starts_at->addMinute());

        $this->actingAs($student)
            ->post(route('attendances.store'), ['token' => strtolower($plainToken)])
            ->assertRedirect();
        $this->post(route('attendances.store'), ['token' => $plainToken])->assertRedirect();

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'attendance_token_id' => $attendanceToken->id,
            'status' => 'present',
        ]);
    }

    public function test_admin_cannot_create_a_session_that_expires_before_it_starts(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = Subject::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.attendance-tokens.store'), [
                'subject_id' => $subject->id,
                'starts_at' => now()->addHours(2)->format('Y-m-d\\TH:i'),
                'expires_at' => now()->addHour()->format('Y-m-d\\TH:i'),
            ])
            ->assertSessionHasErrors('expires_at');

        $this->assertDatabaseCount('attendance_tokens', 0);
    }

    public function test_student_cannot_use_a_token_outside_its_time_window_or_without_a_token(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $subject = Subject::factory()->create();
        $activeCode = 'ACTIVE1234';
        $futureCode = 'FUTURE1234';

        AttendanceToken::factory()->for($subject)->create([
            'token_hash' => hash_hmac('sha256', $activeCode, (string) config('app.key')),
            'starts_at' => now()->subHours(2),
            'expires_at' => now()->subHour(),
        ]);
        AttendanceToken::factory()->for($subject)->create([
            'token_hash' => hash_hmac('sha256', $futureCode, (string) config('app.key')),
            'starts_at' => now()->addHour(),
            'expires_at' => now()->addHours(2),
        ]);

        $this->actingAs($student)
            ->post(route('attendances.store'), ['token' => $activeCode])
            ->assertSessionHasErrors('token');
        $this->post(route('attendances.store'), ['token' => $futureCode])->assertSessionHasErrors('token');
        $this->post(route('attendances.store'), ['subject_id' => $subject->id])->assertSessionHasErrors('token');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_student_attempt_is_scored_on_the_server_and_answer_key_is_not_rendered(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $subject = Subject::factory()->create();
        $simulation = Simulation::factory()->for($subject)->create();
        $question = Question::factory()->for($simulation)->create([
            'choices' => ['A' => 'Pilihan satu', 'B' => 'Pilihan dua'],
            'correct_answer' => 'B',
            'points' => 2,
        ]);
        $attempt = Attempt::factory()->for($student)->for($simulation)->create();

        $this->actingAs($student)
            ->get(route('attempts.show', $attempt))
            ->assertOk()
            ->assertDontSee('correct_answer');
        $this->assertArrayNotHasKey('correct_answer', $question->toArray());

        $this->post(route('attempts.submit', $attempt), [
            'answers' => [$question->id => 'B'],
        ])->assertRedirect(route('attempts.show', $attempt));

        $this->assertSame('100.00', $attempt->fresh()->score);
        $this->assertDatabaseHas('attempts', ['id' => $attempt->id, 'status' => 'completed']);
        $this->assertDatabaseHas('answers', [
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'is_correct' => true,
            'points_earned' => 2,
        ]);
    }

    public function test_student_cannot_view_another_students_attempt(): void
    {
        $owner = User::factory()->create(['role' => 'student']);
        $otherStudent = User::factory()->create(['role' => 'student']);
        $simulation = Simulation::factory()->create();
        $attempt = Attempt::factory()->for($owner)->for($simulation)->create();

        $this->actingAs($otherStudent)->get(route('attempts.show', $attempt))->assertNotFound();
    }

    public function test_student_cannot_submit_a_choice_that_does_not_belong_to_the_question(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $simulation = Simulation::factory()->create();
        $question = Question::factory()->for($simulation)->create([
            'choices' => ['A' => 'Pilihan A', 'B' => 'Pilihan B'],
        ]);
        $attempt = Attempt::factory()->for($student)->for($simulation)->create();

        $this->actingAs($student)
            ->post(route('attempts.submit', $attempt), [
                'answers' => [$question->id => 'X'],
            ])
            ->assertSessionHasErrors('answers.'.$question->id);

        $this->assertDatabaseCount('answers', 0);
        $this->assertDatabaseHas('attempts', ['id' => $attempt->id, 'status' => 'in_progress']);
    }
}
