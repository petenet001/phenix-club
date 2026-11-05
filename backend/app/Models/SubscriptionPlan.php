<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'duration_days',
        'price',
        'sessions_count',
        'registration_fee',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
            'price' => 'integer',
            'sessions_count' => 'integer',
            'registration_fee' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /** Frais d'inscription applicables : spécifiques à la formule, sinon paramètre global. */
    public function effectiveRegistrationFee(): int
    {
        return $this->registration_fee ?? (int) Setting::get('registration_fee', 0);
    }

    public function isUnlimited(): bool
    {
        return $this->sessions_count === null;
    }
}
