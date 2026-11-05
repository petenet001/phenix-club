<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case MobileMoney = 'mobile_money';
    case BankTransfer = 'bank_transfer';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Espèces',
            self::MobileMoney => 'Mobile Money',
            self::BankTransfer => 'Virement',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
