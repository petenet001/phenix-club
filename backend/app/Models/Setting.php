<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public const CACHE_KEY = 'settings.all';

    protected $fillable = ['key', 'value', 'type', 'label'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /** Toutes les valeurs castées, mises en cache. */
    public static function all_cast(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return static::query()->get()
                ->mapWithKeys(fn (Setting $s) => [$s->key => $s->castValue()])
                ->all();
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::all_cast()[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::where('key', $key)->update(['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]);
        Cache::forget(self::CACHE_KEY);
    }

    public function castValue(): mixed
    {
        return match ($this->type) {
            'int' => (int) $this->value,
            'bool' => in_array($this->value, ['1', 'true', 'on'], true),
            default => $this->value,
        };
    }

    public static function checkinToleranceDays(): int
    {
        return (int) static::get('checkin_tolerance_days', 3);
    }

    public static function latePaymentDays(): int
    {
        return (int) static::get('late_payment_days', 5);
    }

    public static function inactivityDays(): int
    {
        return (int) static::get('inactivity_days', 14);
    }
}
