<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = ['title', 'body', 'class_name', 'is_published', 'created_by'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Pengumuman terbit untuk semua siswa atau untuk kelas tertentu.
     *
     * @param  Builder<Announcement>  $query
     */
    public function scopeVisibleTo(Builder $query, ?string $className): void
    {
        $query->where('is_published', true)->where(function (Builder $query) use ($className): void {
            $query->whereNull('class_name');

            if ($className !== null) {
                $query->orWhere('class_name', $className);
            }
        });
    }
}
