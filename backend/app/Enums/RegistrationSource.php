<?php

namespace App\Enums;

enum RegistrationSource: string
{
    case OnSite = 'on_site';
    case Online = 'online';

    public function label(): string
    {
        return match ($this) {
            self::OnSite => 'Sur place',
            self::Online => 'En ligne',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
