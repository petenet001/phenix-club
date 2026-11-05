<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'receipt_number',
        'member_id',
        'subscription_id',
        'type',
        'amount',
        'method',
        'reference',
        'paid_at',
        'period_start',
        'period_end',
        'received_by',
        'notes',
        'cancelled_at',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'type' => PaymentType::class,
            'method' => PaymentMethod::class,
            'paid_at' => 'datetime',
            'period_start' => 'date',
            'period_end' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Prochain numéro de reçu, séquentiel par année : RC-2026-000001 (RG-PAI-03).
     * À appeler dans une transaction avec verrou (lockForUpdate) dans PaymentService.
     */
    public static function nextReceiptNumber(?string $prefix = null, ?int $year = null): string
    {
        $prefix ??= Setting::get('receipt_prefix', 'RC');
        $year ??= (int) now()->format('Y');
        $pattern = sprintf('%s-%d-', $prefix, $year);

        $last = static::where('receipt_number', 'like', $pattern.'%')
            ->orderByDesc('receipt_number')
            ->lockForUpdate()
            ->value('receipt_number');

        $next = $last ? ((int) substr($last, strlen($pattern))) + 1 : 1;

        return sprintf('%s%06d', $pattern, $next);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->whereNull('cancelled_at');
    }

    public function scopePaidBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('paid_at', [$from.' 00:00:00', $to.' 23:59:59']);
    }
}
