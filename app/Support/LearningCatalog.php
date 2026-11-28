<?php

namespace App\Support;

use App\Models\LearningActivity;
use Illuminate\Support\Collection;

/**
 * Daftar materi interaktif siswa (dari config/learning_topics.php) beserta simulasi,
 * latihan, dan kunci jejak belajar yang dipakai Beranda, Latihan, dan Progres Belajar.
 */
class LearningCatalog
{
    /**
     * Topik yang memakai halaman simulasi & latihan generik.
     */
    public const GENERIC_TOPICS = ['computational-thinking', 'sorting', 'pseudocode'];

    /**
     * @return Collection<string, array<string, mixed>>
     */
    public static function topics(): Collection
    {
        return collect(config('learning_topics', []))->map(function (array $topic, string $slug): array {
            $practices = [];
            $simulation = null;
            $items = [$slug => $topic['title']];

            if ($slug === 'searching') {
                $items = [
                    'searching/sequential-search' => 'Sequential Search',
                    'searching/binary-search' => 'Binary Search',
                ];
                $simulation = ['key' => $slug, 'label' => 'Simulasi Searching', 'description' => 'Visualisasi Sequential Search dan Binary Search.', 'url' => route('materi.searching.simulator')];
                foreach (['quiz' => 'LOTS', 'hots_quiz' => 'HOTS'] as $quizKey => $category) {
                    if (isset($topic[$quizKey])) {
                        $practices[] = [
                            'key' => 'searching.'.$quizKey,
                            'label' => $topic[$quizKey]['label'],
                            'description' => $topic[$quizKey]['description'],
                            'categories' => [$category],
                            'types' => ['Pilihan ganda'],
                            'count' => count($topic[$quizKey]['questions']),
                            'url' => route($quizKey === 'quiz' ? 'materi.searching.quiz' : 'materi.searching.hots-quiz'),
                        ];
                    }
                }
            } else {
                if (isset($topic['simulation'])) {
                    $simulation = [
                        'key' => $slug,
                        'label' => $topic['simulation']['label'],
                        'description' => $topic['simulation']['description'],
                        'url' => route('materi.simulation', $slug),
                    ];
                }
                if (isset($topic['practice'])) {
                    $questions = collect($topic['practice']['questions']);
                    $practices[] = [
                        'key' => $slug.'.practice',
                        'label' => $topic['practice']['label'],
                        'description' => $topic['practice']['description'],
                        'categories' => $questions->pluck('category')->unique()->values()->all(),
                        'types' => $questions->pluck('type')->unique()->map(fn ($type) => $type === 'tf' ? 'Benar/salah' : 'Pilihan ganda')->values()->all(),
                        'count' => $questions->count(),
                        'url' => route('materi.practice', $slug),
                    ];
                }
            }

            return [
                'slug' => $slug,
                'number' => $topic['number'],
                'title' => $topic['title'],
                'description' => $topic['description'],
                'topics' => $topic['topics'] ?? [],
                'url' => route('materi.'.$slug),
                'items' => $items,
                'simulation' => $simulation,
                'practices' => $practices,
            ];
        });
    }

    /**
     * Ringkasan progres siswa per topik dari jejak belajar.
     *
     * @return Collection<string, array<string, mixed>>
     */
    public static function progressFor(int $userId): Collection
    {
        $activities = LearningActivity::tableReady()
            ? LearningActivity::query()->where('user_id', $userId)->get()->groupBy('type')
            : collect();
        $materials = ($activities->get(LearningActivity::TYPE_MATERIAL) ?? collect())->keyBy('key');
        $simulations = ($activities->get(LearningActivity::TYPE_SIMULATION) ?? collect())->keyBy('key');
        $practices = ($activities->get(LearningActivity::TYPE_PRACTICE) ?? collect())->keyBy('key');
        $passing = (int) config('students.passing_score', 70);

        return self::topics()->map(function (array $topic) use ($materials, $simulations, $practices, $passing): array {
            $visited = collect($topic['items'])->keys()->filter(fn (string $key) => $materials->has($key))->count();
            $total = count($topic['items']);
            $practiceRows = collect($topic['practices'])->map(fn (array $practice): array => [
                ...$practice,
                'activity' => $practices->get($practice['key']),
            ]);
            $scored = $practiceRows->pluck('activity')->filter();
            $lastScores = $scored->pluck('last_score')->filter(fn ($score) => $score !== null);

            return [
                ...$topic,
                'visited' => $visited,
                'total' => $total,
                'status' => $visited === 0 ? 'belum' : ($visited >= $total ? 'sudah' : 'sebagian'),
                'simulation_activity' => $topic['simulation'] ? $simulations->get($topic['simulation']['key']) : null,
                'practice_rows' => $practiceRows,
                'average' => $lastScores->isEmpty() ? null : (int) round($lastScores->avg()),
                'needs_review' => $lastScores->isNotEmpty() && $lastScores->min() < $passing,
            ];
        });
    }
}
