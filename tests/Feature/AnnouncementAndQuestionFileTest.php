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
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnnouncementAndQuestionFileTest extends TestCase
{
    use RefreshDatabase;

    private const MARK = '<span class="unread-mark"';

    private function student(string $class = 'XE1'): User
    {
        return User::factory()->create(['role' => 'student', 'class_name' => $class, 'nis' => (string) fake()->unique()->numberBetween(10000, 99999)]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /**
     * @return array<string, mixed>
     */
    private function questionPayload(string $material, array $extra = []): array
    {
        return [
            'material' => $material, 'category' => 'LOTS', 'prompt' => 'Perhatikan gambar berikut.',
            'choices' => ['A' => 'Satu', 'B' => 'Dua', 'C' => 'Tiga', 'D' => 'Empat'],
            'correct_answer' => 'B', 'points' => 1, ...$extra,
        ];
    }

    public function test_orange_mark_shows_for_unread_announcement_and_disappears_after_reading(): void
    {
        $student = $this->student('XE1');
        $this->actingAs($student)->get(route('dashboard'))->assertOk()->assertDontSee(self::MARK, false);

        Announcement::query()->create(['title' => 'Kuis Senin', 'body' => 'Siapkan materi Sorting', 'is_published' => true]);

        $this->get(route('dashboard'))->assertOk()->assertSee(self::MARK, false)->assertSee('1 pengumuman belum dibaca');

        // Saat dibuka, pengumuman baru masih ditandai sekali, lalu dicatat sudah dibaca.
        $this->get(route('student.announcements'))->assertOk()->assertSee('Kuis Senin')->assertSee('Pengumuman baru');
        $this->assertDatabaseHas('announcement_reads', ['user_id' => $student->id]);

        $this->get(route('dashboard'))->assertOk()->assertDontSee(self::MARK, false);
        $this->get(route('student.announcements'))->assertOk()->assertDontSee('Pengumuman baru');
    }

    public function test_mark_ignores_drafts_other_classes_and_is_per_student(): void
    {
        $student = $this->student('XE1');
        $classmate = $this->student('XE1');
        Announcement::query()->create(['title' => 'Draf', 'body' => 'x', 'is_published' => false]);
        Announcement::query()->create(['title' => 'Khusus XE3', 'body' => 'x', 'class_name' => 'XE3', 'is_published' => true]);

        $this->actingAs($student)->get(route('dashboard'))->assertDontSee(self::MARK, false);

        Announcement::query()->create(['title' => 'Untuk XE1', 'body' => 'x', 'class_name' => 'XE1', 'is_published' => true]);
        $this->get(route('student.announcements'))->assertOk();
        $this->get(route('dashboard'))->assertDontSee(self::MARK, false);

        // Teman sekelas belum membuka, jadi tandanya tetap ada.
        $this->actingAs($classmate)->get(route('dashboard'))->assertSee(self::MARK, false);
        // Admin tidak pernah melihat tanda dan tidak tercatat membaca.
        $this->actingAs($this->admin())->get(route('admin.announcements.index'))->assertOk()->assertDontSee(self::MARK, false);
        $this->assertSame(1, \DB::table('announcement_reads')->count());
    }

    public function test_question_form_offers_existing_materials_in_a_pulldown(): void
    {
        $subject = Subject::factory()->create(['name' => 'Searching']);
        $material = Material::factory()->create(['subject_id' => $subject->id, 'title' => 'Binary Search']);
        $this->actingAs($this->admin());

        $this->get(route('admin.quizzes.index'))
            ->assertOk()
            ->assertSee('Tambah soal &amp; topik', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('<optgroup label="Materi &amp; Simulasi">', false)
            ->assertSee('value="topic:computational-thinking"', false)
            ->assertSee('value="topic:pseudocode"', false)
            ->assertSee('<optgroup label="Materi unggahan · Searching">', false)
            ->assertSee('value="material:'.$material->id.'"', false);

        $this->post(route('admin.question-bank.store'), $this->questionPayload('topic:sorting'))->assertSessionHasNoErrors();
        $this->post(route('admin.question-bank.store'), $this->questionPayload('material:'.$material->id, ['prompt' => 'Soal binary']))->assertSessionHasNoErrors();
        $this->post(route('admin.question-bank.store'), $this->questionPayload('topic:tidak-ada'))->assertSessionHasErrors('material');
        $this->post(route('admin.question-bank.store'), $this->questionPayload('material:999999'))->assertSessionHasErrors('material');

        $sorting = BankQuestion::query()->where('topic', 'sorting')->firstOrFail();
        $this->assertNull($sorting->material_id);
        $this->assertSame($material->id, BankQuestion::query()->where('prompt', 'Soal binary')->value('material_id'));

        $this->get(route('admin.quizzes.index', ['material' => 'topic:sorting']))->assertOk()->assertSee('Perhatikan gambar berikut.')->assertDontSee('Soal binary');
        $this->get(route('admin.question-bank.edit', $sorting))->assertOk()->assertSee('value="topic:sorting" selected', false);
    }

    public function test_admin_uploads_pdf_word_or_image_and_student_sees_it_in_quiz(): void
    {
        Storage::fake('local');
        $subject = Subject::factory()->create();
        $this->actingAs($this->admin());

        $this->post(route('admin.question-bank.store'), $this->questionPayload('topic:sorting', ['attachment' => UploadedFile::fake()->image('grafik.png')]))->assertSessionHasNoErrors();
        $this->post(route('admin.question-bank.store'), $this->questionPayload('topic:pseudocode', ['prompt' => 'Soal PDF', 'attachment' => UploadedFile::fake()->create('soal.pdf', 100, 'application/pdf')]))->assertSessionHasNoErrors();
        $this->post(route('admin.question-bank.store'), $this->questionPayload('topic:pseudocode', ['prompt' => 'Soal Word', 'attachment' => UploadedFile::fake()->create('soal.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')]))->assertSessionHasNoErrors();
        $this->post(route('admin.question-bank.store'), $this->questionPayload('topic:sorting', ['attachment' => UploadedFile::fake()->create('virus.exe', 10)]))->assertSessionHasErrors('attachment');
        $this->post(route('admin.question-bank.store'), $this->questionPayload('topic:sorting', ['attachment' => UploadedFile::fake()->create('besar.pdf', 11000, 'application/pdf')]))->assertSessionHasErrors('attachment');

        $image = BankQuestion::query()->where('attachment_name', 'grafik.png')->firstOrFail();
        Storage::disk('local')->assertExists($image->attachment_path);
        $this->assertSame(3, BankQuestion::query()->whereNotNull('attachment_path')->count());
        $this->get(route('admin.quizzes.index'))->assertOk()->assertSee('grafik.png')->assertSee('soal.docx');
        $this->get(route('admin.question-bank.attachment', $image))->assertOk();

        $this->post(route('admin.quizzes.store'), [
            'subject_id' => $subject->id, 'title' => 'Kuis Bergambar', 'duration_minutes' => 10,
            'question_ids' => [$image->id], 'is_published' => '1',
        ])->assertSessionHasNoErrors();
        $quiz = Simulation::query()->where('title', 'Kuis Bergambar')->firstOrFail();
        $question = $quiz->questions()->firstOrFail();
        $this->assertSame($image->attachment_path, $question->attachment_path);

        // Siswa lain yang belum mengerjakan kuis tidak dapat membuka file soal.
        $outsider = $this->student();
        $this->actingAs($outsider)->get(route('questions.attachment', $question))->assertNotFound();

        $student = $this->student();
        $this->actingAs($student)->post(route('simulations.begin', $quiz))->assertRedirect();
        $attempt = Attempt::query()->where('user_id', $student->id)->firstOrFail();
        $this->get(route('attempts.show', $attempt))->assertOk()->assertSee(route('questions.attachment', $question), false)->assertSee('<img', false);
        $this->get(route('questions.attachment', $question))->assertOk();
        $this->get(route('admin.question-bank.attachment', $image))->assertForbidden();

        // Menghapus soal dari bank tidak menghapus file yang masih dipakai kuis.
        $this->actingAs($this->admin())->delete(route('admin.question-bank.destroy', $image))->assertRedirect();
        Storage::disk('local')->assertExists($question->attachment_path);

        // Mengganti file soal yang belum dipakai kuis menghapus file lama.
        $pdf = BankQuestion::query()->where('prompt', 'Soal PDF')->firstOrFail();
        $oldPath = $pdf->attachment_path;
        $this->put(route('admin.question-bank.update', $pdf), $this->questionPayload('topic:pseudocode', ['prompt' => 'Soal PDF', 'attachment' => UploadedFile::fake()->image('baru.jpg')]))->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($oldPath);
        $this->assertSame('baru.jpg', $pdf->fresh()->attachment_name);

        $this->put(route('admin.question-bank.update', $pdf), $this->questionPayload('topic:pseudocode', ['prompt' => 'Soal PDF', 'remove_attachment' => '1']))->assertSessionHasNoErrors();
        $this->assertNull($pdf->fresh()->attachment_path);
    }
}
