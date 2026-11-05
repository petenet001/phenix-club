<?php

namespace App\Enums;

enum TrainingProgramStatus: string
{
    case Proposed = 'proposed';
    case Approved = 'approved';
    case Declined = 'declined';
    case Imposed = 'imposed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Proposed => 'Proposé',
            self::Approved => 'Validé',
            self::Declined => 'Refusé',
            self::Imposed => 'Imposé',
            self::Archived => 'Archivé',
        };
    }

    /** Programme en vigueur pour l'adhérent. */
    public function isEffective(): bool
    {
        return in_array($this, [self::Approved, self::Imposed], true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
