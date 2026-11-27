<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Soal di bank soal admin. Kuis dibuat dengan menyalin soal ke tabel questions.
 */
class BankQuestion extends Model
{
    public const CATEGORIES = ['LOTS', 'HOTS'];

    protected $fillable = ['material_id', 'category', 'prompt', 'choices', 'correct_answer', 'explanation', 'points', 'created_by'];

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
}
