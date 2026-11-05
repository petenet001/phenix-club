<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'À venir',
            self::Active => 'En cours',
            self::Expired => 'Expiré',
            self::Cancelled => 'Annulé',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
