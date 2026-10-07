<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchingQuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_searching_quiz(): void
    {
        $this->get(route('materi.searching.quiz'))->assertRedirect(route('login'));
        $this->post(route('materi.searching.quiz.submit'), [])->assertRedirect(route('login'));
        $this->get(route('materi.searching.hots-quiz'))->assertRedirect(route('login'));
        $this->post(route('materi.searching.hots-quiz.submit'), [])->assertRedirect(route('login'));
        $this->get(route('materi.searching.analysis'))->assertRedirect(route('login'));
    }

    public function test_analysis_page_shows_an_empty_state_before_any_attempt(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('materi.searching.analysis'))
            ->assertOk()
            ->assertSee('Analisis belum tersedia')
            ->assertSee('Kerjakan Latihan soal LOTS dan Latihan soal HOTS terlebih dahulu');
    }

    public function test_every_practice_question_maps_to_a_searching_learning_objective(): void
    {
        $objectives = array_keys(config('learning_topics.searching.analysis.indicators'));

        $this->assertSame(['tp1', 'tp2', 'tp3', 'tp4'], $objectives);

        foreach (['quiz', 'hots_quiz'] as $quizKey) {
            foreach ($this->quizQuestions($quizKey) as $question) {
                $this->assertContains($question['indicator'], $objectives);
            }
        }
    }

    public function test_analysis_builds_feedback_from_the_actual_lots_and_hots_answers(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $lotsAnswers = [];
        $hotsAnswers = [];

        foreach ($this->quizQuestions() as $index => $question) {
            $lotsAnswers[$index] = $question['answer'];
        }

        foreach ($this->quizQuestions('hots_quiz') as $index => $question) {
            $hotsAnswers[$index] = $index === 0 ? $this->wrongChoiceFor($question) : $question['answer'];
        }

        $this->actingAs($student)
            ->post(route('materi.searching.quiz.submit'), ['answers' => $lotsAnswers])
            ->assertOk();

        $this->actingAs($student)
            ->post(route('materi.searching.hots-quiz.submit'), ['answers' => $hotsAnswers])
            ->assertOk();

        $this->actingAs($student)
            ->get(route('materi.searching.analysis'))
            ->assertOk()
            ->assertSee('TINGKAT PEMAHAMAN')
            ->assertSee('19 dari 20 jawaban benar')
            ->assertSee('Sangat baik')
            ->assertSee('Latihan soal LOTS')
            ->assertSee('Latihan soal HOTS')
            ->assertSee('TUJUAN PEMBELAJARAN')
            ->assertSee('TP 1')
            ->assertSee('Dikuasai')
            ->assertSee('SOAL YANG MASIH SALAH')
            ->assertSee('PENGAYAAN')
            ->assertDontSee('Belum ada tujuan pembelajaran yang dikuasai.');
    }

    public function test_analysis_reports_missing_quiz_and_improvement_recommendations(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $answers = [];

        foreach ($this->quizQuestions() as $index => $question) {
            $answers[$index] = $index === 0 ? $question['answer'] : $this->wrongChoiceFor($question);
        }

        $this->actingAs($student)
            ->post(route('materi.searching.quiz.submit'), ['answers' => $answers])
            ->assertOk();

        $this->actingAs($student)
            ->get(route('materi.searching.analysis'))
            ->assertOk()
            ->assertSee('1 dari 10 jawaban benar')
            ->assertSee('Kerjakan Latihan soal HOTS terlebih dahulu')
            ->assertSee('MASIH PERLU DIPERBAIKI')
            ->assertSee('Perlu ditingkatkan')
            ->assertSee('Belum ada tujuan pembelajaran yang dikuasai.');
    }

    public function test_searching_section_links_to_both_practice_quizzes(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->get(route('materi.searching'))
            ->assertOk()
            ->assertSee('Latihan soal LOTS')
            ->assertSee('Latihan soal HOTS')
            ->assertSee(route('materi.searching.quiz'), false)
            ->assertSee(route('materi.searching.hots-quiz'), false)
            ->assertSee(route('materi.searching.analysis'), false)
            ->assertSee('Analisis</a>', false)
            ->assertDontSee('Latihan LOTS')
            ->assertDontSee('Latihan HOTS')
            ->assertDontSee('LATIHAN SEARCHING')
            ->assertDontSee('Lihat jawaban dan pembahasan')
            ->assertDontSee('Array terurut [3, 7, 11, 18, 25, 31, 42] dicari dengan Binary Search')
            ->assertDontSee('href="#hots"', false);
    }

    public function test_student_sees_ten_unique_lots_questions_with_five_choices(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $questions = $this->quizQuestions();

        $this->assertCount(10, $questions);

        $prompts = [];
        foreach ($questions as $question) {
            $this->assertSame(['A', 'B', 'C', 'D', 'E'], array_keys($question['options']));
            $this->assertContains($question['answer'], array_keys($question['options']));
            $prompts[] = $question['question'];
        }
        $this->assertCount(10, array_unique($prompts));

        $response = $this->actingAs($student)->get(route('materi.searching.quiz'));

        $response->assertOk()->assertSee('Latihan soal LOTS');

        foreach ($questions as $question) {
            $response->assertSee($question['question']);
            foreach ($question['options'] as $option) {
                $response->assertSee($option);
            }
            $response->assertDontSee($question['explanation']);
        }
    }

    public function test_student_receives_score_correct_answers_and_explanations_after_submitting(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $questions = $this->quizQuestions();
        $answers = [];

        foreach ($questions as $index => $question) {
            $answers[$index] = $index < 6 ? $question['answer'] : $this->wrongChoiceFor($question);
        }

        $response = $this->actingAs($student)
            ->post(route('materi.searching.quiz.submit'), ['answers' => $answers]);

        $response->assertOk()
            ->assertSee('TOTAL SKOR')
            ->assertSee('60<small>%</small>', false)
            ->assertSee('6 dari 10 jawaban benar')
            ->assertSee('Belum tepat');

        foreach ($questions as $question) {
            $response->assertSee($question['question']);
            $response->assertSee($question['explanation']);
        }

        $this->assertSame(10, substr_count($response->getContent(), 'Jawaban benar:'));
    }

    public function test_student_can_submit_a_partial_attempt(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $questions = $this->quizQuestions();

        $this->actingAs($student)
            ->post(route('materi.searching.quiz.submit'), ['answers' => ['0' => $questions[0]['answer']]])
            ->assertOk()
            ->assertSee('10<small>%</small>', false)
            ->assertSee('1 dari 10 jawaban benar')
            ->assertSee('Tidak dijawab');
    }

    public function test_student_cannot_submit_a_choice_that_is_not_offered(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->post(route('materi.searching.quiz.submit'), ['answers' => ['0' => 'F']])
            ->assertSessionHasErrors('answers.0');
    }

    public function test_student_sees_ten_unique_hots_questions_with_five_choices(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $questions = $this->quizQuestions('hots_quiz');

        $this->assertCount(10, $questions);

        $prompts = [];
        foreach ($questions as $question) {
            $this->assertSame(['A', 'B', 'C', 'D', 'E'], array_keys($question['options']));
            $this->assertContains($question['answer'], array_keys($question['options']));
            $prompts[] = $question['question'];
        }
        $this->assertCount(10, array_unique($prompts));

        $response = $this->actingAs($student)->get(route('materi.searching.hots-quiz'));

        $response->assertOk()->assertSee('Latihan soal HOTS');

        foreach ($questions as $question) {
            $response->assertSee($question['question']);
            foreach ($question['options'] as $option) {
                $response->assertSee($option);
            }
            $response->assertDontSee($question['explanation']);
        }
    }

    public function test_student_receives_hots_score_and_explanations_after_submitting(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $questions = $this->quizQuestions('hots_quiz');
        $answers = [];

        foreach ($questions as $index => $question) {
            $answers[$index] = $index < 6 ? $question['answer'] : $this->wrongChoiceFor($question);
        }

        $response = $this->actingAs($student)
            ->post(route('materi.searching.hots-quiz.submit'), ['answers' => $answers]);

        $response->assertOk()
            ->assertSee('TOTAL SKOR')
            ->assertSee('60<small>%</small>', false)
            ->assertSee('6 dari 10 jawaban benar')
            ->assertSee('Belum tepat');

        foreach ($questions as $question) {
            $response->assertSee($question['question']);
            $response->assertSee($question['explanation']);
        }

        $this->assertSame(10, substr_count($response->getContent(), 'Jawaban benar:'));
    }

    public function test_student_cannot_submit_a_hots_choice_that_is_not_offered(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $this->actingAs($student)
            ->post(route('materi.searching.hots-quiz.submit'), ['answers' => ['0' => 'F']])
            ->assertSessionHasErrors('answers.0');
    }

    public function test_lots_and_hots_quizzes_do_not_reuse_the_same_questions(): void
    {
        $lotsQuestions = array_column($this->quizQuestions('quiz'), 'question');
        $hotsQuestions = array_column($this->quizQuestions('hots_quiz'), 'question');

        $this->assertSame([], array_values(array_intersect($lotsQuestions, $hotsQuestions)));
    }

    /**
     * @return array<int, array{question: string, options: array<string, string>, answer: string, explanation: string, indicator: string}>
     */
    private function quizQuestions(string $quizKey = 'quiz'): array
    {
        return config('learning_topics.searching.'.$quizKey.'.questions');
    }

    /**
     * @param  array{question: string, options: array<string, string>, answer: string, explanation: string, indicator: string}  $question
     */
    private function wrongChoiceFor(array $question): string
    {
        foreach ($question['options'] as $key => $option) {
            if ($key !== $question['answer']) {
                return $key;
            }
        }

        $this->fail('Question does not offer a wrong choice.');
    }
}
