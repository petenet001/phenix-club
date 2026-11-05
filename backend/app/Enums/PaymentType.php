<?php

namespace App\Enums;

enum PaymentType: string
{
    case Registration = 'registration';
    case Subscription = 'subscription';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Registration => 'Frais d\'inscription',
            self::Subscription => 'Abonnement',
            self::Other => 'Autre',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
