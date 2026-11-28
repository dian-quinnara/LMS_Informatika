<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\Question;
use App\Models\Simulation;
use App\Support\StoredAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SimulationAttemptController extends Controller
{
    public function showSimulation(Simulation $simulation): View
    {
        abort_unless($simulation->is_published && $simulation->questions()->exists(), 404);

        $simulation->load('subject')->loadCount('questions');
        $usedAttempts = $simulation->attempts()->where('user_id', request()->user()->id)->count();

        return view('simulations.show', compact('simulation', 'usedAttempts'));
    }

    public function begin(Request $request, Simulation $simulation): RedirectResponse
    {
        abort_unless($simulation->is_published && $simulation->questions()->exists(), 404);

        $attempts = $request->user()->attempts()->where('simulation_id', $simulation->id);

        // Lanjutkan percobaan yang belum dikirim agar tidak menghabiskan jatah percobaan.
        $unfinished = (clone $attempts)->where('status', 'in_progress')->latest()->first();
        if ($unfinished !== null) {
            return redirect()->route('attempts.show', $unfinished);
        }

        if ($simulation->max_attempts !== null && (clone $attempts)->count() >= $simulation->max_attempts) {
            return back()->withErrors(['attempt' => 'Batas percobaan kuis ini ('.$simulation->max_attempts.'x) sudah habis.']);
        }

        $attempt = $request->user()->attempts()->create([
            'simulation_id' => $simulation->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        return redirect()->route('attempts.show', $attempt);
    }

    public function showAttempt(Request $request, Attempt $attempt): View
    {
        abort_unless($attempt->user_id === $request->user()->id, 404);

        if ($attempt->status === 'completed') {
            $attempt->load('simulation.subject', 'answers.question');

            return view('simulations.result', compact('attempt'));
        }

        $attempt->load('simulation.subject', 'simulation.questions');

        return view('simulations.attempt', compact('attempt'));
    }

    public function submit(Request $request, Attempt $attempt): RedirectResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 404);
        abort_if($attempt->status === 'completed', 409);

        $questions = $attempt->simulation->questions;
        $rules = ['answers' => ['nullable', 'array']];

        foreach ($questions as $question) {
            $rules['answers.'.$question->id] = ['nullable', 'string', Rule::in(array_keys($question->choices))];
        }

        $validated = $request->validate($rules);
        $submittedAnswers = $validated['answers'] ?? [];
        $totalPoints = max(1, $questions->sum('points'));
        $earnedPoints = 0;

        DB::transaction(function () use ($attempt, $questions, $submittedAnswers, $totalPoints, &$earnedPoints): void {
            foreach ($questions as $question) {
                $answer = $submittedAnswers[$question->id] ?? null;
                $isCorrect = $answer === $question->correct_answer;
                $pointsEarned = $isCorrect ? $question->points : 0;
                $earnedPoints += $pointsEarned;

                $attempt->answers()->create([
                    'question_id' => $question->id,
                    'answer' => $answer,
                    'is_correct' => $isCorrect,
                    'points_earned' => $pointsEarned,
                ]);
            }

            $attempt->update([
                'submitted_at' => now(),
                'score' => round(($earnedPoints / $totalPoints) * 100, 2),
                'status' => 'completed',
            ]);
        });

        return redirect()->route('attempts.show', $attempt)->with('status', 'Jawaban tersimpan. Nilai Anda: '.number_format(($earnedPoints / $totalPoints) * 100, 1).'%');
    }

    /**
     * Lampiran soal (PDF/Word/gambar) hanya untuk siswa yang sedang/pernah mengerjakan kuisnya.
     */
    public function questionAttachment(Request $request, Question $question): StreamedResponse
    {
        abort_unless($request->user()->attempts()->where('simulation_id', $question->simulation_id)->exists(), 404);

        return StoredAttachment::stream($question->attachment_path, $question->attachment_name, $question->attachment_mime);
    }
}
