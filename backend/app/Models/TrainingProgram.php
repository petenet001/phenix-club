<?php

namespace App\Models;

use App\Enums\TrainingProgramKind;
use App\Enums\TrainingProgramStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Programme d'entraînement ou d'alimentation d'un adhérent.
 *
 * content (JSON) pour kind = training :
 * { "days": ["lundi", "mercredi", "vendredi"], "goal": "Perte de poids",
 *   "sessions": [ { "day": "lundi", "exercises": [ { "name": "Squat", "sets": 4, "reps": 12, "notes": "" } ] } ] }
 * content (JSON) pour kind = nutrition :
 * { "goal": "...", "meals": [ { "time": "Petit-déjeuner", "items": ["Flocons d'avoine", "Fruits"] } ], "advice": "..." }
 */
class TrainingProgram extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'kind',
        'title',
        'status',
        'proposed_by_member',
        'author_id',
        'starts_on',
        'ends_on',
        'content',
        'notes',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected function casts(): array
    {
        return [
            'kind' => TrainingProgramKind::class,
            'status' => TrainingProgramStatus::class,
            'proposed_by_member' => 'boolean',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'content' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeEffective(Builder $query): Builder
    {
        return $query->whereIn('status', [TrainingProgramStatus::Approved->value, TrainingProgramStatus::Imposed->value]);
    }

    public function scopeOfKind(Builder $query, string $kind): Builder
    {
        return $query->where('kind', $kind);
    }
}
