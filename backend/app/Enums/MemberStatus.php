<?php

namespace App\Enums;

enum MemberStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente de validation',
            self::Active => 'Actif',
            self::Inactive => 'Inactif',
            self::Suspended => 'Suspendu',
        };
    }

    /** Statuts calculés automatiquement à partir des abonnements (RG-ADH-03). */
    public function isComputed(): bool
    {
        return in_array($this, [self::Active, self::Inactive], true);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
