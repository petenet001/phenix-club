<?php

namespace App\Enums;

enum UserRole: string
{
    case Manager = 'manager';
    case Agent = 'agent';
    case Coach = 'coach';

    public function label(): string
    {
        return match ($this) {
            self::Manager => 'Gérant',
            self::Agent => 'Agent d\'accueil',
            self::Coach => 'Coach',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
