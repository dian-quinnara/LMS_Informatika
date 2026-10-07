<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SearchingAnalysisController extends Controller
{
    /**
     * Session key prefix set by SearchingQuizController after each submission.
     */
    private const ATTEMPT_SESSION_PREFIX = 'searching_quiz_attempt.';

    /**
     * Display labels of the practice quizzes feeding the analysis, keyed by config key.
     *
     * @var array<string, string>
     */
    private const QUIZ_LABELS = [
        'quiz' => 'Latihan soal LOTS',
        'hots_quiz' => 'Latihan soal HOTS',
    ];

    public function index(): View
    {
        $searching = config('learning_topics.searching');
        abort_unless(is_array($searching), 500);

        $analysis = $searching['analysis'] ?? [];
        abort_unless(! empty($analysis['indicators']), 500);

        $results = $this->storedResults($searching);
        $indicators = $this->blankIndicators($analysis);

        $completedQuizzes = [];
        $missingQuizzes = [];
        $wrongItems = [];
        $correctCount = 0;
        $questionTotal = 0;

        foreach (array_keys(self::QUIZ_LABELS) as $quizKey) {
            if (! isset($results[$quizKey])) {
                $missingQuizzes[] = self::QUIZ_LABELS[$quizKey];

                continue;
            }

            $completedQuizzes[] = self::QUIZ_LABELS[$quizKey];

            foreach ($searching[$quizKey]['questions'] as $index => $question) {
                $selected = $results[$quizKey]['answers'][$index] ?? null;
                $isCorrect = $selected !== null && $selected === $question['answer'];

                $questionTotal++;
                $correctCount += $isCorrect ? 1 : 0;

                $code = $question['indicator'] ?? null;
                if ($code !== null && isset($indicators[$code])) {
                    $indicators[$code]['total']++;
                    $indicators[$code]['correct'] += $isCorrect ? 1 : 0;
                }

                if (! $isCorrect) {
                    $wrongItems[] = [
                        'quiz' => self::QUIZ_LABELS[$quizKey],
                        'question' => $question['question'],
                        'selected' => $selected,
                        'answer' => $question['answer'],
                    ];
                }
            }
        }

        $hasResults = $completedQuizzes !== [];
        $score = $questionTotal > 0 ? (int) round(($correctCount / $questionTotal) * 100) : 0;
        $level = $this->levelFor($score, $analysis);

        [$indicators, $masteredIndicators, $improvementIndicators] = $this->evaluateIndicators(
            $indicators,
            (int) $analysis['mastery_threshold'],
        );

        return view('materials.searching-analysis', [
            'hasResults' => $hasResults,
            'score' => $score,
            'correctCount' => $correctCount,
            'questionTotal' => $questionTotal,
            'level' => $level,
            'indicators' => $indicators,
            'masteredIndicators' => $masteredIndicators,
            'improvementIndicators' => $improvementIndicators,
            'wrongItems' => $wrongItems,
            'recommendations' => $this->recommendations(
                $hasResults,
                $missingQuizzes,
                $improvementIndicators,
                $wrongItems,
            ),
            'completedQuizzes' => $completedQuizzes,
            'missingQuizzes' => $missingQuizzes,
        ]);
    }

    /**
     * Read the stored attempt for every practice quiz from the session.
     *
     * @param  array<string, mixed>  $searching
     * @return array<string, array{answers: array<int, string|null>}>
     */
    private function storedResults(array $searching): array
    {
        $results = [];

        foreach (array_keys(self::QUIZ_LABELS) as $quizKey) {
            $attempt = session(self::ATTEMPT_SESSION_PREFIX.$quizKey);

            if (is_array($attempt) && isset($attempt['answers'], $searching[$quizKey]['questions'])) {
                $results[$quizKey] = $attempt;
            }
        }

        return $results;
    }

    /**
     * Create one empty accumulator per Tujuan Pembelajaran.
     *
     * @param  array{indicators: array<string, array{title: string, objective: string, enrichment: string}>}  $analysis
     * @return array<string, array{code: string, title: string, objective: string, enrichment: string, correct: int, total: int, accuracy: int, mastered: bool}>
     */
    private function blankIndicators(array $analysis): array
    {
        $indicators = [];

        foreach ($analysis['indicators'] as $code => $definition) {
            $indicators[$code] = [
                'code' => (string) $code,
                'title' => $definition['title'],
                'objective' => $definition['objective'],
                'enrichment' => $definition['enrichment'],
                'correct' => 0,
                'total' => 0,
                'accuracy' => 0,
                'mastered' => false,
            ];
        }

        return $indicators;
    }

    /**
     * Score every Tujuan Pembelajaran and split it into mastered / still to improve.
     *
     * @param  array<string, array{code: string, title: string, objective: string, enrichment: string, correct: int, total: int, accuracy: int, mastered: bool}>  $indicators
     * @return array{0: array<string, array{code: string, title: string, objective: string, enrichment: string, correct: int, total: int, accuracy: int, mastered: bool}>, 1: array<int, array{code: string, title: string, objective: string, enrichment: string, correct: int, total: int, accuracy: int, mastered: bool}>, 2: array<int, array{code: string, title: string, objective: string, enrichment: string, correct: int, total: int, accuracy: int, mastered: bool}>}
     */
    private function evaluateIndicators(array $indicators, int $threshold): array
    {
        $mastered = [];
        $needsImprovement = [];

        foreach ($indicators as $code => $indicator) {
            $accuracy = $indicator['total'] > 0 ? ($indicator['correct'] / $indicator['total']) * 100 : 0;

            $indicators[$code]['accuracy'] = (int) round($accuracy);
            $indicators[$code]['mastered'] = $indicator['total'] > 0 && $accuracy >= $threshold;

            if ($indicators[$code]['mastered']) {
                $mastered[] = $indicators[$code];
            } elseif ($indicator['total'] > 0) {
                $needsImprovement[] = $indicators[$code];
            }
        }

        return [$indicators, $mastered, $needsImprovement];
    }

    /**
     * Pick the understanding level matching the achieved score.
     *
     * @param  array{levels: array<int, array{min: int, label: string, description: string}>}  $analysis
     * @return array{min: int, label: string, description: string}
     */
    private function levelFor(int $score, array $analysis): array
    {
        foreach ($analysis['levels'] as $level) {
            if ($score >= $level['min']) {
                return $level;
            }
        }

        return end($analysis['levels']);
    }

    /**
     * Build the concrete, student facing recommendations for this attempt.
     *
     * @param  array<int, string>  $missingQuizzes
     * @param  array<int, array{enrichment: string}>  $improvementIndicators
     * @param  array<int, array{quiz: string, question: string, selected: string|null, answer: string}>  $wrongItems
     * @return array<int, string>
     */
    private function recommendations(bool $hasResults, array $missingQuizzes, array $improvementIndicators, array $wrongItems): array
    {
        $recommendations = [];

        foreach ($missingQuizzes as $label) {
            $recommendations[] = 'Kerjakan '.$label.' terlebih dahulu agar analisis dapat menilai seluruh Tujuan Pembelajaran Searching.';
        }

        foreach ($improvementIndicators as $indicator) {
            $recommendations[] = $indicator['enrichment'];
        }

        if (! $hasResults) {
            return $recommendations;
        }

        $recommendations[] = $wrongItems === []
            ? 'Semua jawabanmu tepat. Pertahankan dengan mengulang latihan secara berkala agar pemahamanmu tetap terjaga.'
            : 'Kerjakan ulang kedua latihan lalu baca kembali pembahasan pada soal yang masih salah, karena pembahasan menjelaskan alasan jawaban yang benar.';

        return $recommendations;
    }
}
