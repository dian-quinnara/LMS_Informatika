<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

/**
 * Jejak belajar siswa: materi dibuka, simulasi dicoba, dan nilai latihan.
 */
class LearningActivity extends Model
{
    public const TYPE_MATERIAL = 'material';

    public const TYPE_SIMULATION = 'simulation';

    public const TYPE_PRACTICE = 'practice';

    protected $fillable = ['user_id', 'type', 'key', 'label', 'url', 'visits', 'last_score', 'best_score', 'last_activity_at'];

    protected function casts(): array
    {
        return ['last_activity_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Catat aktivitas siswa. Admin tidak dicatat. Aman dipanggil sebelum migrasi dijalankan.
     */
    public static function record(?User $user, string $type, string $key, string $label, ?string $url = null, ?int $score = null): void
    {
        if ($user === null || $user->role !== 'student' || ! static::tableReady()) {
            return;
        }

        $activity = static::query()->firstOrNew(['user_id' => $user->id, 'type' => $type, 'key' => $key]);
        $activity->fill([
            'label' => $label,
            'url' => $url ?? $activity->url,
            'visits' => $activity->visits + 1,
            'last_activity_at' => now(),
        ]);

        if ($score !== null) {
            $score = max(0, min(100, $score));
            $activity->last_score = $score;
            $activity->best_score = max($score, (int) $activity->best_score);
        }

        $activity->save();
    }

    public static function tableReady(): bool
    {
        static $ready = null;

        return $ready ??= Schema::hasTable('learning_activities');
    }
}
