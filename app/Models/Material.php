<?php

namespace App\Models;

use Database\Factories\MaterialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    /** @use HasFactory<MaterialFactory> */
    use HasFactory;

    protected $fillable = ['subject_id', 'title', 'slug', 'sort_order', 'summary', 'content', 'resource_url', 'is_published', 'attachment_path', 'attachment_name', 'attachment_mime', 'created_by'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function bankQuestions(): HasMany
    {
        return $this->hasMany(BankQuestion::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
