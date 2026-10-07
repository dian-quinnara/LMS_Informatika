<?php

namespace App\Models;

use Database\Factories\AttendanceTokenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceToken extends Model
{
    /** @use HasFactory<AttendanceTokenFactory> */
    use HasFactory;

    protected $fillable = ['subject_id', 'class_name', 'created_by', 'token', 'token_hash', 'starts_at', 'expires_at'];

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
            return 'Kelas '.$this->class_name;
        }

        return $this->subject?->name ?? 'Sesi presensi';
    }

    /**
     * Status sesi berdasarkan rentang waktu sesi.
     */
    public function sessionStatus(): string
    {
        return match (true) {
            $this->starts_at->isFuture() => 'Belum Dimulai',
            $this->expires_at->isPast() => 'Selesai',
            default => 'Aktif',
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
