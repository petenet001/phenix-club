<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_category_id',
        'label',
        'amount',
        'method',
        'payee',
        'reference',
        'spent_on',
        'receipt_path',
        'notes',
        'recorded_by',
        'cancelled_at',
        'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'method' => PaymentMethod::class,
            'spent_on' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query->whereNull('cancelled_at');
    }

    public function scopeSpentBetween(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('spent_on', [$from, $to]);
    }
}
