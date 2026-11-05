<?php

namespace App\Enums;

enum TrainingProgramKind: string
{
    case Training = 'training';
    case Nutrition = 'nutrition';

    public function label(): string
    {
        return match ($this) {
            self::Training => 'Entraînement',
            self::Nutrition => 'Alimentation',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
