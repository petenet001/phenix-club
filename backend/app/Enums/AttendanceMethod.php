<?php

namespace App\Enums;

enum AttendanceMethod: string
{
    case Manual = 'manual';
    case Qr = 'qr';
    case App = 'app';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
