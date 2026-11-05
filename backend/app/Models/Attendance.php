<?php

namespace App\Models;

use App\Enums\AttendanceMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'subscription_id',
        'attended_on',
        'checked_in_at',
        'method',
        'forced',
        'force_reason',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'attended_on' => 'date',
            'checked_in_at' => 'datetime',
            'method' => AttendanceMethod::class,
            'forced' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Attendance $attendance) {
            $attendance->checked_in_at ??= now();
            $attendance->attended_on ??= $attendance->checked_in_at->toDateString();
        });
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeOnDate(Builder $query, string $date): Builder
    {
        return $query->whereDate('attended_on', $date);
    }

    public function scopeBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('attended_on', [$from, $to]);
    }
}
