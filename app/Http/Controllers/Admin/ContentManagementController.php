<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\Simulation;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menu 5 · Kelola Materi & Simulasi: materi per topik, lampiran, urutan, publikasi, dan simulasi.
 */
class ContentManagementController extends Controller
{
    /**
     * Gambar, video, dan dokumen pendukung yang boleh diunggah (maks. 20 MB).
     */
    public const ATTACHMENT_MIMES = 'jpg,jpeg,png,gif,webp,mp4,webm,pdf,doc,docx,ppt,pptx,xls,xlsx';

    public const ATTACHMENT_MAX_KB = 20480;

    public function index(): View
    {
        $subjects = Subject::query()
            ->withCount(['materials', 'simulations'])
            ->with(['materials' => fn ($query) => $query->orderBy('sort_order')->orderBy('id')])
            ->orderBy('name')
            ->get();

        return view('admin.content.index', [
            'subjects' => $subjects,
            'simulations' => Simulation::query()->with('subject')->withCount(['questions', 'attempts'])->latest()->get(),
            'interactiveSimulations' => [
                ['title' => 'Simulator Searching', 'description' => 'Visualisasi langkah Sequential Search dan Binary Search.', 'route' => 'materi.searching.simulator'],
            ],
        ]);
    }

    public function storeMaterial(Request $request): RedirectResponse
    {
        $validated = $this->validateMaterial($request);
        $material = new Material([...$validated, 'created_by' => $request->user()->id]);
        $this->applyAttachment($material, $request->file('attachment'));
        $material->save();

        return redirect()->route('admin.content.index')->with('status', 'Materi "'.$material->title.'" berhasil disimpan'.($material->is_published ? ' dan diterbitkan.' : ' sebagai draf.'));
    }

    public function editMaterial(Material $material): View
    {
        return view('admin.content.material-form', [
            'material' => $material,
            'subjects' => Subject::query()->orderBy('name')->get(),
        ]);
    }

    public function updateMaterial(Request $request, Material $material): RedirectResponse
    {
        $validated = $this->validateMaterial($request, $material);

        if ($request->boolean('remove_attachment') && $material->attachment_path) {
            Storage::disk('local')->delete($material->attachment_path);
            $material->fill(['attachment_path' => null, 'attachment_name' => null, 'attachment_mime' => null]);
        }

        $material->fill($validated);
        $this->applyAttachment($material, $request->file('attachment'));
        $material->save();

        return redirect()->route('admin.content.index')->with('status', 'Materi "'.$material->title.'" berhasil diperbarui.');
    }

    public function togglePublication(Material $material): RedirectResponse
    {
        $material->forceFill(['is_published' => ! $material->is_published])->save();

        return back()->with('status', 'Materi "'.$material->title.'" '.($material->is_published ? 'diterbitkan.' : 'disembunyikan dari siswa (draf).'));
    }

    public function attachment(Material $material): StreamedResponse
    {
        return self::streamAttachment($material);
    }

    public function editSimulation(Simulation $simulation): View
    {
        return view('admin.content.simulation-form', [
            'simulation' => $simulation->loadCount('questions'),
            'subjects' => Subject::query()->orderBy('name')->get(),
        ]);
    }

    public function updateSimulation(Request $request, Simulation $simulation): RedirectResponse
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:240'],
            'max_attempts' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);
        $published = $request->boolean('is_published');
        $simulation->fill([
            ...$validated,
            'max_attempts' => $validated['max_attempts'] ?? null,
            'is_published' => $published,
            'published_at' => $published ? ($simulation->published_at ?? now()) : null,
        ])->save();

        return redirect()->route('admin.content.index')->with('status', 'Simulasi "'.$simulation->title.'" berhasil diperbarui.');
    }

    /**
     * Unduh/tampilkan lampiran materi dari penyimpanan privat (tidak perlu storage:link).
     */
    public static function streamAttachment(Material $material): StreamedResponse
    {
        abort_if($material->attachment_path === null || ! Storage::disk('local')->exists($material->attachment_path), 404);

        $inline = str_starts_with((string) $material->attachment_mime, 'image/')
            || str_starts_with((string) $material->attachment_mime, 'video/')
            || $material->attachment_mime === 'application/pdf';

        return Storage::disk('local')->response(
            $material->attachment_path,
            $material->attachment_name,
            ['Content-Type' => $material->attachment_mime ?? 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff'],
            $inline ? 'inline' : 'attachment',
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validateMaterial(Request $request, ?Material $material = null): array
    {
        $validated = $request->validate([
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:20000'],
            'resource_url' => ['nullable', 'url:http,https', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'attachment' => ['nullable', 'file', 'mimes:'.self::ATTACHMENT_MIMES, 'max:'.self::ATTACHMENT_MAX_KB],
        ], [
            'attachment.mimes' => 'Lampiran harus berupa gambar, video (mp4/webm), PDF, atau dokumen Office.',
            'attachment.max' => 'Ukuran lampiran maksimal 20 MB.',
        ]);
        unset($validated['attachment']);
        $validated['sort_order'] = $validated['sort_order'] ?? ($material?->sort_order ?? 0);
        $validated['is_published'] = $request->boolean('is_published');

        return $validated;
    }

    private function applyAttachment(Material $material, ?UploadedFile $file): void
    {
        if ($file === null) {
            return;
        }

        if ($material->attachment_path) {
            Storage::disk('local')->delete($material->attachment_path);
        }

        $material->fill([
            'attachment_path' => $file->store('materials', 'local'),
            'attachment_name' => mb_substr(basename($file->getClientOriginalName()), 0, 200),
            'attachment_mime' => $file->getMimeType(),
        ]);
    }
}
