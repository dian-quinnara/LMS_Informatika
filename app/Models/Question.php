<?php

namespace App\Models;

use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    protected $fillable = ['simulation_id', 'prompt', 'choices', 'correct_answer', 'points', 'position'];

    protected $hidden = ['correct_answer'];

    protected function casts(): array
    {
        return ['choices' => 'array'];
    }

    public function simulation(): BelongsTo
    {
        return $this->belongsTo(Simulation::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}
