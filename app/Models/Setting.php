<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaturan sistem sederhana (key/value), misalnya identitas website.
 */
class Setting extends Model
{
    public const DEFAULTS = [
        'site_name' => 'Ruang Belajar',
        'school_name' => '',
        'site_tagline' => '',
    ];

    private const CACHE_KEY = 'system_settings';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    /**
     * @return array<string, string>
     */
    public static function allValues(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $stored = Schema::hasTable('settings') ? static::query()->pluck('value', 'key')->all() : [];

            return array_merge(self::DEFAULTS, array_filter($stored, fn ($value) => $value !== null));
        });
    }

    public static function value(string $key): string
    {
        return (string) (static::allValues()[$key] ?? '');
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public static function store(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value ?? '']);
        }

        Cache::forget(self::CACHE_KEY);
    }
}
