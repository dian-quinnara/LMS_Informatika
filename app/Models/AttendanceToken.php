<?php

namespace App\Models;

use Database\Factories\AttendanceTokenFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceToken extends Model
{
    /** @use HasFactory<AttendanceTokenFactory> */
    use HasFactory;

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_LABELS = [
        self::STATUS_SCHEDULED => 'Terjadwal',
        self::STATUS_ACTIVE => 'Aktif',
        self::STATUS_COMPLETED => 'Selesai',
    ];

    protected $fillable = ['subject_id', 'class_name', 'title', 'created_by', 'token', 'token_hash', 'starts_at', 'expires_at'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    /**
     * Nama tampilan sesi presensi (berbasis kelas atau mata pelajaran).
     */
    public function displayName(): string
    {
        if ($this->class_name !== null) {
            return 'Kelas '.$this->class_name.($this->title ? ' · '.$this->title : '');
        }

        return $this->subject?->name ?? 'Sesi presensi';
    }

    /**
     * Kunci status sesi, dihitung dari waktu server (tidak disimpan di database).
     */
    public function statusKey(): string
    {
        $now = now();

        return match (true) {
            $now->lt($this->starts_at) => self::STATUS_SCHEDULED,
            $now->gt($this->expires_at) => self::STATUS_COMPLETED,
            default => self::STATUS_ACTIVE,
        };
    }

    /**
     * Status sesi berdasarkan rentang waktu sesi.
     */
    public function sessionStatus(): string
    {
        return self::STATUS_LABELS[$this->statusKey()];
    }

    public function isActive(): bool
    {
        return $this->statusKey() === self::STATUS_ACTIVE;
    }

    /**
     * Sesi presensi kelas X (XE1–XE9).
     *
     * @param  Builder<AttendanceToken>  $query
     */
    public function scopeGradeX(Builder $query): void
    {
        $query->whereIn('class_name', config('attendance.grade_x_classes', []));
    }

    /**
     * Filter sesi berdasarkan status yang dihitung dari waktu server.
     *
     * @param  Builder<AttendanceToken>  $query
     */
    public function scopeWithStatus(Builder $query, string $status): void
    {
        $now = now();

        match ($status) {
            self::STATUS_SCHEDULED => $query->where('starts_at', '>', $now),
            self::STATUS_ACTIVE => $query->where('starts_at', '<=', $now)->where('expires_at', '>=', $now),
            self::STATUS_COMPLETED => $query->where('expires_at', '<', $now),
            default => null,
        };
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
