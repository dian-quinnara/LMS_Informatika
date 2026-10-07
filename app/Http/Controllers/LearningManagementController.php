<?php

namespace App\Http\Controllers;

use App\Models\AttendanceToken;
use App\Models\Material;
use App\Models\Simulation;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LearningManagementController extends Controller
{
    public function index(): View
    {
        $subjects = Subject::query()->withCount(['materials', 'simulations'])->orderBy('name')->get();
        $materials = Material::query()->with('subject')->latest()->take(8)->get();
        $simulations = Simulation::query()->with('subject')->withCount('questions')->latest()->get();
        $attendanceTokens = AttendanceToken::query()->with('subject')->latest()->take(10)->get();

        return view('admin.manage', compact('subjects', 'materials', 'simulations', 'attendanceTokens'));
    }

    public function storeSubject(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:subjects,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $request->user()->createdSubjects()->create($validated);

        return back()->with('status', 'Mata pelajaran berhasil ditambahkan.');
    }

    public function storeMaterial(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:20000'],
            'resource_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $request->user()->createdMaterials()->create($validated);

        return back()->with('status', 'Materi berhasil diterbitkan.');
    }

    public function storeSimulation(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:240'],
        ]);
        $validated['is_published'] = $request->boolean('is_published');
        $validated['published_at'] = $validated['is_published'] ? now() : null;

        Subject::query()->findOrFail($validated['subject_id'])->simulations()->create($validated);

        return back()->with('status', 'Simulasi berhasil dibuat. Tambahkan soal sebelum dibagikan ke siswa.');
    }

    public function storeQuestion(Request $request): RedirectResponse
    {
        $simulationId = $request->input('simulation_id');
        $choices = $request->input('choices');
        $choiceKeys = is_array($choices) ? array_keys($choices) : [];

        $validated = $request->validate([
            'simulation_id' => ['required', 'integer', 'exists:simulations,id'],
            'prompt' => ['required', 'string', 'max:2000'],
            'choices' => ['required', 'array', 'min:2', 'max:4'],
            'choices.*' => ['required', 'string', 'max:255'],
            'correct_answer' => ['required', 'string', Rule::in($choiceKeys)],
            'points' => ['required', 'integer', 'min:1', 'max:100'],
        ]);
        $simulation = Simulation::query()->findOrFail($simulationId);
        unset($validated['simulation_id']);
        $validated['position'] = $simulation->questions()->count() + 1;

        $simulation->questions()->create($validated);

        return back()->with('status', 'Soal berhasil ditambahkan.');
    }
}
