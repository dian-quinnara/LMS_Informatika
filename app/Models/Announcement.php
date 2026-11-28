<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    /**
     * Siswa yang sudah membaca pengumuman ini.
     */
    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_reads')->withPivot('read_at');
    }

    /**
     * Pengumuman yang belum dibaca oleh siswa tertentu.
     *
     * @param  Builder<Announcement>  $query
     */
    public function scopeUnreadBy(Builder $query, User $user): void
    {
        $query->whereDoesntHave('readers', fn (Builder $query) => $query->whereKey($user->id));
    }

    /**
     * Jumlah pengumuman terbit untuk siswa yang belum dibacanya (0 untuk admin).
     */
    public static function unreadCountFor(?User $user): int
    {
        if ($user === null || $user->role !== 'student' || ! static::readsTableReady()) {
            return 0;
        }

        return static::query()->visibleTo($user->class_name)->unreadBy($user)->count();
    }

    /**
     * Tandai pengumuman sebagai sudah dibaca oleh siswa.
     *
     * @param  iterable<int, Announcement>  $announcements
     */
    public static function markReadFor(User $user, iterable $announcements): void
    {
        if ($user->role !== 'student' || ! static::readsTableReady()) {
            return;
        }

        $now = now();
        $rows = collect($announcements)->map(fn (Announcement $announcement) => [
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'read_at' => $now,
        ])->all();

        if ($rows !== []) {
            DB::table('announcement_reads')->insertOrIgnore($rows);
        }
    }

    public static function readsTableReady(): bool
    {
        static $ready = null;

        return $ready ??= Schema::hasTable('announcement_reads');
    }
}
