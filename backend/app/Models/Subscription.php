<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'subscription_plan_id',
        'start_date',
        'end_date',
        'price_at_subscription',
        'sessions_used',
        'status',
        'created_by',
        'cancelled_at',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'price_at_subscription' => 'integer',
            'sessions_used' => 'integer',
            'status' => SubscriptionStatus::class,
            'cancelled_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /** Somme des paiements non annulés (RG-ABO-06). */
    public function getAmountPaidAttribute(): int
    {
        if ($this->relationLoaded('payments')) {
            return (int) $this->payments->whereNull('cancelled_at')->sum('amount');
        }

        return (int) $this->payments()->whereNull('cancelled_at')->sum('amount');
    }

    public function getBalanceDueAttribute(): int
    {
        return max(0, $this->price_at_subscription - $this->amount_paid);
    }

    public function isPaidInFull(): bool
    {
        return $this->balance_due === 0;
    }

    public function isCancelled(): bool
    {
        return $this->status === SubscriptionStatus::Cancelled;
    }

    /** Statut attendu selon la date du jour (RG-ABO-05, RG-ABO-07). */
    public function computeStatus(?CarbonInterface $today = null): SubscriptionStatus
    {
        if ($this->isCancelled()) {
            return SubscriptionStatus::Cancelled;
        }

        $today = ($today ?? now())->toDateString();

        if ($this->start_date->toDateString() > $today) {
            return SubscriptionStatus::Pending;
        }

        if ($this->end_date->toDateString() < $today) {
            return SubscriptionStatus::Expired;
        }

        return SubscriptionStatus::Active;
    }

    /** Vrai si la date du jour est dans [start_date, end_date] (hors tolérance). */
    public function coversDate(CarbonInterface $date): bool
    {
        $d = $date->toDateString();

        return $this->start_date->toDateString() <= $d && $this->end_date->toDateString() >= $d;
    }

    /** Vrai si la date est dans la fenêtre de tolérance après l'échéance (RG-PTG-02). */
    public function isWithinTolerance(CarbonInterface $date, int $toleranceDays): bool
    {
        $d = $date->toDateString();
        $limit = $this->end_date->copy()->addDays($toleranceDays)->toDateString();

        return $this->end_date->toDateString() < $d && $d <= $limit;
    }

    public function hasSessionsLeft(): bool
    {
        $plan = $this->plan;

        if ($plan === null || $plan->sessions_count === null) {
            return true;
        }

        return $this->sessions_used < $plan->sessions_count;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::Active->value);
    }

    public function scopeNotCancelled(Builder $query): Builder
    {
        return $query->where('status', '<>', SubscriptionStatus::Cancelled->value);
    }

    public function scopeCurrentOrUpcoming(Builder $query): Builder
    {
        return $query->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Pending->value]);
    }
}
