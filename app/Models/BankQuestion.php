<?php

namespace App\Models;

use App\Support\LearningCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Soal di bank soal admin. Kuis dibuat dengan menyalin soal ke tabel questions.
 * Setiap soal terhubung ke satu materi: topik Materi & Simulasi (kolom topic)
 * atau materi yang diunggah admin (kolom material_id).
 */
class BankQuestion extends Model
{
    public const CATEGORIES = ['LOTS', 'HOTS'];

    /**
     * Lampiran soal yang boleh diunggah: PDF, Word, atau gambar (maks. 10 MB).
     */
    public const ATTACHMENT_MIMES = 'pdf,doc,docx,jpg,jpeg,png,gif,webp';

    public const ATTACHMENT_MAX_KB = 10240;

    protected $fillable = ['material_id', 'topic', 'category', 'prompt', 'choices', 'correct_answer', 'explanation', 'points', 'attachment_path', 'attachment_name', 'attachment_mime', 'created_by'];

    protected function casts(): array
    {
        return ['choices' => 'array'];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function quizQuestions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Pilihan pulldown materi: topik Materi & Simulasi lalu materi unggahan admin per topik.
     * Nilai berbentuk "topic:{slug}" atau "material:{id}".
     *
     * @return Collection<string, array<string, string>>
     */
    public static function materialOptions(): Collection
    {
        $groups = collect([
            'Materi & Simulasi' => LearningCatalog::topics()
                ->mapWithKeys(fn (array $topic) => ['topic:'.$topic['slug'] => $topic['number'].' · '.Str::title($topic['title'])])
                ->all(),
        ]);

        Material::query()->with('subject:id,name')->orderBy('subject_id')->orderBy('sort_order')->orderBy('id')->get(['id', 'title', 'subject_id'])
            ->groupBy(fn (Material $material) => 'Materi unggahan · '.($material->subject?->name ?? 'Tanpa topik'))
            ->each(function (Collection $materials, string $group) use ($groups): void {
                $groups[$group] = $materials->mapWithKeys(fn (Material $material) => ['material:'.$material->id => $material->title])->all();
            });

        return $groups;
    }

    /**
     * Nilai pulldown materi untuk soal ini.
     */
    public function materialKey(): ?string
    {
        return match (true) {
            $this->topic !== null => 'topic:'.$this->topic,
            $this->material_id !== null => 'material:'.$this->material_id,
            default => null,
        };
    }

    /**
     * Nama materi untuk ditampilkan dan dikelompokkan.
     */
    public function materialLabel(): string
    {
        if ($this->topic !== null) {
            return Str::title((string) config("learning_topics.{$this->topic}.title", $this->topic));
        }

        return $this->material?->title ?? 'Tanpa materi';
    }

    /**
     * Hapus file lampiran bila tidak lagi dipakai bank soal maupun soal kuis.
     */
    public static function deleteAttachmentIfUnused(?string $path): void
    {
        if ($path === null) {
            return;
        }

        $used = static::query()->where('attachment_path', $path)->exists()
            || Question::query()->where('attachment_path', $path)->exists();

        if (! $used) {
            Storage::disk('local')->delete($path);
        }
    }
}
