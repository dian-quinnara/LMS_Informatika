<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\ContentManagementController;
use App\Models\LearningActivity;
use App\Models\Material;
use App\Support\LearningCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('materi.index');
    }

    public function computationalThinking(Request $request): View
    {
        return $this->showTopic($request, 'computational-thinking');
    }

    public function searching(Request $request): View
    {
        return $this->showTopic($request, 'searching');
    }

    public function sequentialSearch(Request $request): View
    {
        return $this->showSearchingLesson($request, 'sequential-search', 'Sequential Search');
    }

    public function binarySearch(Request $request): View
    {
        return $this->showSearchingLesson($request, 'binary-search', 'Binary Search');
    }

    public function searchingSimulator(Request $request): View
    {
        LearningActivity::record($request->user(), LearningActivity::TYPE_SIMULATION, 'searching', 'Simulasi Searching', route('materi.searching.simulator'));

        return view('simulations.searching-simulator');
    }

    public function sorting(Request $request): View
    {
        return $this->showTopic($request, 'sorting');
    }

    public function pseudocode(Request $request): View
    {
        return $this->showTopic($request, 'pseudocode');
    }

    public function strukturData(Request $request): View
    {
        return $this->showTopic($request, 'struktur-data');
    }

    /**
     * Simulasi interaktif topik generik (menyusun algoritma / visualisasi sorting).
     */
    public function simulation(Request $request, string $topic): View
    {
        $config = config("learning_topics.{$topic}");
        abort_unless(is_array($config) && isset($config['simulation']), 404);

        LearningActivity::record($request->user(), LearningActivity::TYPE_SIMULATION, $topic, $config['simulation']['label'], route('materi.simulation', $topic));

        return view($config['simulation']['type'] === 'sorting' ? 'simulations.sorting-simulator' : 'simulations.ordering', [
            'slug' => $topic,
            'topic' => $config,
            'simulation' => $config['simulation'],
        ]);
    }

    public function show(Material $material): View
    {
        abort_unless($material->is_published, 404);
        $material->load('subject');

        return view('materials.show', compact('material'));
    }

    public function attachment(Material $material): StreamedResponse
    {
        abort_unless($material->is_published, 404);

        return ContentManagementController::streamAttachment($material);
    }

    private function showTopic(Request $request, string $slug): View
    {
        $topic = config("learning_topics.{$slug}");
        LearningActivity::record($request->user(), LearningActivity::TYPE_MATERIAL, $slug, 'Materi '.ucwords(strtolower($topic['title'])), route('materi.'.$slug));

        return view('materials.topic', [
            'slug' => $slug,
            'topic' => $topic,
            'entry' => LearningCatalog::topics()->get($slug),
        ]);
    }

    private function showSearchingLesson(Request $request, string $slug, string $label): View
    {
        $material = Material::query()
            ->with('subject')
            ->where('slug', $slug)
            ->where('is_published', true)
            ->whereHas('subject', fn ($query) => $query->where('code', 'IF-SEARCHING'))
            ->firstOrFail();
        $sections = json_decode($material->content, true);

        abort_unless(is_array($sections), 500);

        LearningActivity::record($request->user(), LearningActivity::TYPE_MATERIAL, 'searching/'.$slug, $label, $request->url());

        return view('materials.lesson', compact('material', 'sections'));
    }
}
