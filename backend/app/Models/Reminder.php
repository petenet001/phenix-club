<?php

namespace App\Models;

use App\Enums\ReminderChannel;
use App\Enums\ReminderType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
{
    use HasFactory;

    public const MIN_DAYS_BETWEEN = 7;

    protected $fillable = [
        'member_id',
        'type',
        'channel',
        'message',
        'status',
        'error',
        'sent_at',
        'sent_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => ReminderType::class,
            'channel' => ReminderChannel::class,
            'sent_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /** Vrai si une relance du même type a été envoyée il y a moins de 7 jours (RG-REL-04). */
    public static function recentlySentTo(int $memberId, ReminderType $type): bool
    {
        return static::where('member_id', $memberId)
            ->where('type', $type->value)
            ->where('status', 'sent')
            ->where('sent_at', '>=', now()->subDays(self::MIN_DAYS_BETWEEN))
            ->exists();
    }
}
