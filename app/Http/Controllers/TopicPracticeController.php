<?php

namespace App\Http\Controllers;

use App\Models\LearningActivity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Latihan per materi (pilihan ganda & benar/salah, LOTS & HOTS) dengan skor,
 * pembahasan, dan umpan balik. Kunci jawaban hanya ada di server.
 */
class TopicPracticeController extends Controller
{
    public function show(string $topic): View
    {
        [$config, $practice] = $this->practice($topic);

        return view('materials.searching-quiz', [
            ...$this->viewLabels($topic, $config),
            'quiz' => $this->publicQuiz($practice),
            'submitUrl' => route('materi.practice.submit', $topic),
            'sectionKicker' => 'LOTS & HOTS · PILIHAN GANDA & BENAR/SALAH',
            'sectionTitle' => count($practice['questions']).' soal latihan',
        ]);
    }

    public function submit(Request $request, string $topic): View
    {
        [$config, $practice] = $this->practice($topic);
        $questions = $practice['questions'];
        $rules = ['answers' => ['nullable', 'array']];

        foreach (array_keys($questions) as $index) {
            $rules['answers.'.$index] = ['nullable', 'string', Rule::in(array_keys($questions[$index]['options']))];
        }

        $submitted = $request->validate($rules)['answers'] ?? [];
        $reviews = [];
        $correctCount = 0;

        foreach ($questions as $index => $question) {
            $selected = $submitted[$index] ?? null;
            $isCorrect = $selected === $question['answer'];
            $correctCount += $isCorrect ? 1 : 0;
            $reviews[] = [
                'question' => $question['question'],
                'category' => $question['category'] ?? null,
                'options' => $question['options'],
                'answer' => $question['answer'],
                'explanation' => $question['explanation'],
                'selected' => $selected,
                'is_correct' => $isCorrect,
            ];
        }

        $score = (int) round($correctCount / max(1, count($questions)) * 100);
        LearningActivity::record($request->user(), LearningActivity::TYPE_PRACTICE, $topic.'.practice', $practice['label'], route('materi.practice', $topic), $score);

        return view('materials.searching-quiz-result', [
            ...$this->viewLabels($topic, $config, 'HASIL '),
            'quiz' => $practice,
            'reviews' => $reviews,
            'correctCount' => $correctCount,
            'score' => $score,
            'retryUrl' => route('materi.practice', $topic),
            'feedback' => self::feedbackFor($score, $reviews),
        ]);
    }

    /**
     * Umpan balik singkat berdasarkan skor dan kategori soal yang masih keliru.
     *
     * @param  list<array<string, mixed>>  $reviews
     * @return array{label: string, message: string}
     */
    public static function feedbackFor(int $score, array $reviews = []): array
    {
        $passing = (int) config('students.passing_score', 70);
        $wrongHots = collect($reviews)->where('is_correct', false)->where('category', 'HOTS')->count();
        $wrongLots = collect($reviews)->where('is_correct', false)->where('category', 'LOTS')->count();

        [$label, $message] = match (true) {
            $score >= 85 => ['Sangat baik', 'Pemahamanmu sudah kuat. Coba jelaskan materi ini kepada temanmu untuk menguji pemahamanmu.'],
            $score >= $passing => ['Tuntas', 'Kamu sudah mencapai KKM. Baca pembahasan soal yang masih keliru agar pemahamanmu makin mantap.'],
            $score >= 50 => ['Hampir tuntas', 'Pelajari ulang penjelasan dan contoh kasus, coba simulasinya, lalu kerjakan latihan ini lagi.'],
            default => ['Perlu belajar ulang', 'Mulai lagi dari penjelasan singkat dan simulasi materi ini secara perlahan, lalu ulangi latihannya.'],
        };

        if ($wrongLots > 0 && $score < 85) {
            $message .= ' Masih ada '.$wrongLots.' soal LOTS (konsep dasar) yang keliru, jadi ulangi definisi dan istilah pentingnya.';
        } elseif ($wrongHots > 0 && $score < 85) {
            $message .= ' Konsep dasarmu sudah baik; latih lagi soal HOTS dengan menelusuri contoh langkah demi langkah.';
        }

        return ['label' => $label, 'message' => $message];
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function practice(string $topic): array
    {
        $config = config("learning_topics.{$topic}");
        abort_unless(is_array($config) && ! empty($config['practice']['questions']), 404);

        return [$config, $config['practice']];
    }

    /**
     * Soal tanpa kunci jawaban dan pembahasan untuk ditampilkan sebelum dikirim.
     *
     * @param  array<string, mixed>  $practice
     * @return array<string, mixed>
     */
    private function publicQuiz(array $practice): array
    {
        $practice['questions'] = array_map(
            fn (array $question): array => array_intersect_key($question, array_flip(['question', 'options', 'category', 'type'])),
            $practice['questions'],
        );

        return $practice;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, string>
     */
    private function viewLabels(string $topic, array $config, string $prefix = ''): array
    {
        $title = ucwords(strtolower($config['title']));

        return [
            'kicker' => $prefix.'LATIHAN '.$config['title'],
            'backUrl' => route('materi.'.$topic),
            'backLabel' => 'Kembali ke '.$title,
        ];
    }
}
