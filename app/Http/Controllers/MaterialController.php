<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\ContentManagementController;
use App\Models\Material;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('dashboard', ['section' => 'materials']);
    }

    public function searching(): View
    {
        return $this->showTopic('searching');
    }

    public function sequentialSearch(): View
    {
        return $this->showSearchingLesson('sequential-search');
    }

    public function binarySearch(): View
    {
        return $this->showSearchingLesson('binary-search');
    }

    public function searchingSimulator(): View
    {
        return view('simulations.searching-simulator');
    }

    public function sorting(): View
    {
        return $this->showTopic('sorting');
    }

    public function strukturData(): View
    {
        return $this->showTopic('struktur-data');
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

    private function showTopic(string $slug): View
    {
        return view('materials.topic', ['topic' => config("learning_topics.{$slug}")]);
    }

    private function showSearchingLesson(string $slug): View
    {
        $material = Material::query()
            ->with('subject')
            ->where('slug', $slug)
            ->where('is_published', true)
            ->whereHas('subject', fn ($query) => $query->where('code', 'IF-SEARCHING'))
            ->firstOrFail();
        $sections = json_decode($material->content, true);

        abort_unless(is_array($sections), 500);

        return view('materials.lesson', compact('material', 'sections'));
    }
}
