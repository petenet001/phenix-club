<?php

namespace App\Enums;

enum ReminderChannel: string
{
    case Sms = 'sms';
    case Whatsapp = 'whatsapp';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
