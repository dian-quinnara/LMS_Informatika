<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Menu 8 · Pengumuman untuk siswa (semua kelas atau kelas tertentu).
 */
class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('admin.announcements.index', [
            'announcements' => Announcement::query()->with('creator:id,name')->latest()->get(),
            'classes' => config('attendance.grade_x_classes', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Announcement::query()->create([...$this->validated($request), 'created_by' => $request->user()->id]);

        return redirect()->route('admin.announcements.index')->with('status', 'Pengumuman berhasil disimpan.');
    }

    public function edit(Announcement $announcement): View
    {
        return view('admin.announcements.edit', [
            'announcement' => $announcement,
            'classes' => config('attendance.grade_x_classes', []),
        ]);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $announcement->update($this->validated($request));

        return redirect()->route('admin.announcements.index')->with('status', 'Pengumuman berhasil diperbarui.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()->route('admin.announcements.index')->with('status', 'Pengumuman dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'class_name' => ['nullable', 'string', Rule::in(config('attendance.grade_x_classes', []))],
        ], [
            'class_name.in' => 'Kelas harus salah satu dari XE1–XE9.',
        ]);
        $validated['class_name'] = $validated['class_name'] ?? null;
        $validated['is_published'] = $request->boolean('is_published');

        return $validated;
    }
}
