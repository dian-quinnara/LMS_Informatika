<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SearchingQuizController extends Controller
{
    /**
     * Session key prefix holding the latest submitted attempt for each practice quiz.
     */
    private const ATTEMPT_SESSION_PREFIX = 'searching_quiz_attempt.';

    /**
     * Route names used to render and score each practice quiz.
     *
     * @var array<string, array{show: string, submit: string}>
     */
    private const QUIZ_ROUTES = [
        'quiz' => ['show' => 'materi.searching.quiz', 'submit' => 'materi.searching.quiz.submit'],
        'hots_quiz' => ['show' => 'materi.searching.hots-quiz', 'submit' => 'materi.searching.hots-quiz.submit'],
    ];

    public function show(): View
    {
        return $this->renderQuiz('quiz');
    }

    public function submit(Request $request): View
    {
        return $this->scoreQuiz('quiz', $request);
    }

    public function showHots(): View
    {
        return $this->renderQuiz('hots_quiz');
    }

    public function submitHots(Request $request): View
    {
        return $this->scoreQuiz('hots_quiz', $request);
    }

    private function renderQuiz(string $quizKey): View
    {
        return view('materials.searching-quiz', [
            'quiz' => $this->quiz($quizKey),
            'submitRoute' => self::QUIZ_ROUTES[$quizKey]['submit'],
        ]);
    }

    private function scoreQuiz(string $quizKey, Request $request): View
    {
        $questions = $this->quiz($quizKey)['questions'];
        $rules = ['answers' => ['nullable', 'array']];

        foreach (array_keys($questions) as $index) {
            $rules['answers.'.$index] = ['nullable', 'string', Rule::in(array_keys($questions[$index]['options']))];
        }

        $validated = $request->validate($rules);
        $submittedAnswers = $validated['answers'] ?? [];
        $reviews = [];
        $storedAnswers = [];
        $correctCount = 0;

        foreach ($questions as $index => $question) {
            $selectedAnswer = $submittedAnswers[$index] ?? null;
            $isCorrect = $selectedAnswer === $question['answer'];
            $correctCount += $isCorrect ? 1 : 0;
            $storedAnswers[$index] = $selectedAnswer;

            $reviews[] = [
                'question' => $question['question'],
                'options' => $question['options'],
                'answer' => $question['answer'],
                'explanation' => $question['explanation'],
                'selected' => $selectedAnswer,
                'is_correct' => $isCorrect,
            ];
        }

        $score = round(($correctCount / max(1, count($questions))) * 100);

        $this->rememberAttempt($quizKey, $storedAnswers, $score);

        return view('materials.searching-quiz-result', [
            'quiz' => $this->quiz($quizKey),
            'reviews' => $reviews,
            'correctCount' => $correctCount,
            'score' => $score,
            'retryRoute' => self::QUIZ_ROUTES[$quizKey]['show'],
        ]);
    }

    /**
     * Keep the submitted answers so the analysis page can build personalised feedback.
     *
     * @param  array<int, string|null>  $answers
     */
    private function rememberAttempt(string $quizKey, array $answers, int $score): void
    {
        session()->put(self::ATTEMPT_SESSION_PREFIX.$quizKey, [
            'answers' => $answers,
            'score' => $score,
            'submitted_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * @return array{label: string, description: string, questions: array<int, array{question: string, options: array<string, string>, answer: string, explanation: string, indicator: string}>}
     */
    private function quiz(string $quizKey): array
    {
        $quiz = config('learning_topics.searching.'.$quizKey);

        abort_unless(is_array($quiz) && ! empty($quiz['questions']), 500);

        return $quiz;
    }
}
