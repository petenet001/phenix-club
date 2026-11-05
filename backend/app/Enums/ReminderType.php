<?php

namespace App\Enums;

enum ReminderType: string
{
    case Absence = 'absence';
    case LatePayment = 'late_payment';
    case ExpirySoon = 'expiry_soon';

    public function label(): string
    {
        return match ($this) {
            self::Absence => 'Absence',
            self::LatePayment => 'Retard de paiement',
            self::ExpirySoon => 'Échéance proche',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
