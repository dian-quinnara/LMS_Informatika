<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Attempt;
use App\Models\AttendanceToken;
use App\Models\LearningActivity;
use App\Models\Question;
use App\Models\Simulation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StudentMenuTest extends TestCase
{
    use RefreshDatabase;

    private const MENU = [
        'Beranda' => 'dashboard',
        'Presensi' => 'student.attendance',
        'Materi &amp; Simulasi' => 'materi.index',
        'Latihan &amp; Kuis' => 'student.practice',
        'Progres Belajar' => 'student.progress',
        'Pengumuman' => 'student.announcements',
        'Profil Saya' => 'student.profile',
    ];

    private function student(?string $class = 'XE1', array $attributes = []): User
    {
        return User::factory()->create(['role' => 'student', 'class_name' => $class, 'nis' => '12345', ...$attributes]);
    }

    /**
     * @return array<int, string>
     */
    private function answersFor(string $topic, bool $allCorrect): array
    {
        return collect(config("learning_topics.{$topic}.practice.questions"))
            ->map(fn (array $question) => $allCorrect ? $question['answer'] : collect(array_keys($question['options']))->first(fn ($key) => $key !== $question['answer']))
            ->all();
    }

    public function test_student_sidebar_lists_seven_menus_and_every_page_loads(): void
    {
        $this->actingAs($this->student());
        $response = $this->get(route('dashboard'))->assertOk();

        foreach (self::MENU as $label => $route) {
            $response->assertSee($label, false)->assertSee(route($route), false);
            $this->get(route($route))->assertOk();
        }
        $response->assertSee('07')->assertDontSee('Kelola Siswa');
    }

    public function test_admin_cannot_open_student_menu_pages(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        foreach (array_slice(self::MENU, 1) as $route) {
            $this->get(route($route))->assertForbidden();
        }
    }

    public function test_home_summarises_today_attendance_last_material_pending_practice_and_progress(): void
    {
        $this->travelTo(Carbon::parse('2026-11-28 07:00'));
        $student = $this->student('XE4');
        AttendanceToken::factory()->create(['class_name' => 'XE4', 'title' => 'Informatika Pagi', 'starts_at' => now()->subMinutes(5), 'expires_at' => now()->addHour(), 'token' => 'ABCDEF1234', 'token_hash' => hash_hmac('sha256', 'ABCDEF1234', (string) config('app.key'))]);
        AttendanceToken::factory()->create(['class_name' => 'XE5', 'title' => 'Kelas Lain', 'starts_at' => now()->subMinutes(5), 'expires_at' => now()->addHour()]);
        $quiz = Simulation::factory()->create(['title' => 'Kuis Guru Baru', 'is_published' => true]);
        Question::factory()->for($quiz)->create();
        $this->actingAs($student);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Informatika Pagi')
            ->assertDontSee('Kelas Lain')
            ->assertDontSee('ABCDEF1234')
            ->assertSee('Belum presensi')
            ->assertSee('Belum ada materi yang dibuka.')
            ->assertSee('Kuis Guru Baru')
            ->assertSee('Latihan Computational Thinking');

        $this->post(route('attendances.store'), ['token' => 'ABCDEF1234'])->assertSessionHasNoErrors();
        $this->get(route('materi.pseudocode'))->assertOk();
        $this->post(route('materi.practice.submit', 'computational-thinking'), ['answers' => $this->answersFor('computational-thinking', true)])->assertOk();

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Hadir')
            ->assertSee('Materi Pseudocode')
            ->assertSee(route('materi.pseudocode'), false)
            ->assertDontSee('Latihan Computational Thinking')
            ->assertSee('100');
    }

    public function test_new_topics_show_explanation_cases_simulation_and_practice(): void
    {
        $this->actingAs($this->student());

        $this->get(route('materi.computational-thinking'))
            ->assertOk()
            ->assertSee('COMPUTATIONAL THINKING')
            ->assertSee('Dekomposisi')->assertSee('Pengenalan Pola')->assertSee('Abstraksi')->assertSee('Algoritma')
            ->assertSee('CONTOH KASUS')
            ->assertSee('Mencari buku di perpustakaan')
            ->assertSee(route('materi.simulation', 'computational-thinking'), false)
            ->assertSee(route('materi.practice', 'computational-thinking'), false)
            ->assertDontSee(route('materi.searching.analysis'), false);

        $this->get(route('materi.pseudocode'))
            ->assertOk()
            ->assertSee('Percabangan')
            ->assertSee('Perulangan')
            ->assertSee('Menentukan kelulusan');

        $this->get(route('materi.sorting'))
            ->assertOk()
            ->assertSee('Mengurutkan nilai ulangan')
            ->assertSee(route('materi.simulation', 'sorting'), false);

        $this->get(route('materi.simulation', 'computational-thinking'))->assertOk()->assertSee('Periksa urutan')->assertSee('ordering-activity.js');
        $this->get(route('materi.simulation', 'pseudocode'))->assertOk()->assertSee('OUTPUT maks');
        $this->get(route('materi.simulation', 'sorting'))->assertOk()->assertSee('Bubble Sort')->assertSee('sorting-simulator.js');
        $this->get(route('materi.simulation', 'struktur-data'))->assertNotFound();
        $this->get('/materi/searching/latihan')->assertNotFound();
    }

    public function test_practice_hides_answer_key_and_returns_score_explanation_and_feedback(): void
    {
        $student = $this->student();
        $this->actingAs($student);
        $questions = config('learning_topics.sorting.practice.questions');

        $this->get(route('materi.practice', 'sorting'))
            ->assertOk()
            ->assertSee('Latihan Sorting')
            ->assertSee('LOTS · Pilihan ganda')
            ->assertSee('HOTS · Benar/salah')
            ->assertSee('Benar')
            ->assertDontSee($questions[0]['explanation'])
            ->assertDontSee('correct_answer');

        $this->post(route('materi.practice.submit', 'sorting'), ['answers' => [0 => 'Z']])->assertSessionHasErrors('answers.0');

        $this->post(route('materi.practice.submit', 'sorting'), ['answers' => $this->answersFor('sorting', false)])
            ->assertOk()
            ->assertSee('0<small>%</small>', false)
            ->assertSee('Perlu belajar ulang')
            ->assertSee($questions[0]['explanation']);

        $activity = LearningActivity::query()->where('user_id', $student->id)->where('key', 'sorting.practice')->firstOrFail();
        $this->assertSame([0, 0, 1], [$activity->last_score, $activity->best_score, $activity->visits]);

        $this->get(route('student.progress'))->assertOk()->assertSee('Materi yang perlu dipelajari ulang')->assertSee('Rata-rata 0');

        $this->post(route('materi.practice.submit', 'sorting'), ['answers' => $this->answersFor('sorting', true)])
            ->assertOk()->assertSee('100<small>%</small>', false)->assertSee('Sangat baik');
        $activity->refresh();
        $this->assertSame([100, 100, 2], [$activity->last_score, $activity->best_score, $activity->visits]);
    }

    public function test_searching_quiz_score_is_recorded_for_progress(): void
    {
        $student = $this->student();
        $this->actingAs($student)->post(route('materi.searching.quiz.submit'), ['answers' => []])->assertOk()->assertSee('UMPAN BALIK');

        $this->assertSame(0, LearningActivity::query()->where('user_id', $student->id)->where('key', 'searching.quiz')->value('last_score'));
        $this->get(route('student.practice'))->assertOk()->assertSee('Nilai 0');
    }

    public function test_progress_shows_studied_materials_tried_simulations_and_scores(): void
    {
        $student = $this->student();
        $quiz = Simulation::factory()->create(['title' => 'Kuis Harian', 'is_published' => true]);
        Attempt::query()->create(['user_id' => $student->id, 'simulation_id' => $quiz->id, 'started_at' => now(), 'submitted_at' => now(), 'score' => 40, 'status' => 'completed']);
        $this->actingAs($student);

        $this->get(route('student.progress'))->assertOk()->assertSee('Belum dipelajari')->assertSee('Belum dicoba');

        $this->get(route('materi.computational-thinking'))->assertOk();
        $this->get(route('materi.simulation', 'sorting'))->assertOk();
        $this->get(route('materi.searching.simulator'))->assertOk();

        $this->get(route('student.progress'))
            ->assertOk()
            ->assertSee('Sudah dipelajari')
            ->assertSee('Sudah dicoba')
            ->assertSee('Kuis Harian')
            ->assertSee('Terakhir 40');

        $this->assertSame(3, LearningActivity::query()->where('user_id', $student->id)->count());
        $this->get(route('materi.index'))->assertOk()->assertSee('Sudah dipelajari');
    }

    public function test_activity_of_other_students_and_admins_is_separate(): void
    {
        $student = $this->student();
        $other = $this->student('XE2', ['nis' => '54321']);
        $this->actingAs($other)->get(route('materi.sorting'));
        $this->actingAs(User::factory()->create(['role' => 'admin']))->get(route('admin.results.index'));

        $this->actingAs($student)->get(route('dashboard'))->assertOk()->assertSee('Belum ada materi yang dibuka.');
        $this->assertSame(1, LearningActivity::query()->count());
    }

    public function test_announcements_page_lists_only_visible_announcements(): void
    {
        Announcement::query()->create(['title' => 'Jadwal Kuis', 'body' => 'Kuis Sorting hari Senin', 'is_published' => true]);
        Announcement::query()->create(['title' => 'Khusus XE3', 'body' => 'Bukan untuk XE1', 'class_name' => 'XE3', 'is_published' => true]);
        Announcement::query()->create(['title' => 'Draf Admin', 'body' => 'Belum tampil', 'is_published' => false]);

        $this->actingAs($this->student('XE1'))
            ->get(route('student.announcements'))
            ->assertOk()
            ->assertSee('Jadwal Kuis')
            ->assertDontSee('Khusus XE3')
            ->assertDontSee('Draf Admin');
    }

    public function test_profile_shows_identity_and_class_is_read_only(): void
    {
        $student = $this->student('XE6', ['name' => 'Rani Putri', 'nis' => '880011', 'email' => 'rani@sekolah.test']);

        $this->actingAs($student)
            ->get(route('student.profile'))
            ->assertOk()
            ->assertSee('Rani Putri')
            ->assertSee('880011')
            ->assertSee('XE6')
            ->assertSee('Diatur admin')
            ->assertSee(route('password.edit'), false)
            ->assertDontSee('name="class_name"', false);

        $this->patch(route('admin.student-classes.update'), ['user_id' => $student->id, 'class_name' => 'XE1'])->assertForbidden();
        $this->assertSame('XE6', $student->fresh()->class_name);
    }
}
