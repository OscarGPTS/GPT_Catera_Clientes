<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'locked',
    ];

    protected $casts = [
        'value' => 'array',
        'locked' => 'boolean',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("system_setting:{$key}", now()->addMinutes(30), function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (! $setting) {
                return $default;
            }

            return $setting->resolved();
        });
    }

    public static function set(string $key, mixed $value, ?string $type = null): self
    {
        $setting = static::firstOrNew(['key' => $key]);

        $resolvedType = $type ?? $setting->type ?? static::inferType($value);

        $setting->fill([
            'value' => ['v' => $value],
            'type' => $resolvedType,
        ])->save();

        Cache::forget("system_setting:{$key}");

        return $setting;
    }

    public function resolved(): mixed
    {
        $raw = $this->value['v'] ?? null;

        return match ($this->type) {
            'bool', 'boolean' => (bool) $raw,
            'int', 'integer' => (int) $raw,
            'float', 'double' => (float) $raw,
            'array', 'json' => is_array($raw) ? $raw : [],
            default => $raw,
        };
    }

    protected static function inferType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_array($value) => 'array',
            default => 'string',
        };
    }
}
