<?php

namespace App\Services;

/**
 * QR code de pointage affiché sur la borne de la salle (écran de l'accueil).
 *
 * Le contenu change toutes les PERIOD secondes et se vérifie sans stockage (HMAC sur la clé de
 * l'application). Le code de la période précédente reste accepté pour absorber le délai de scan :
 * une photo du QR envoyée à quelqu'un hors de la salle n'est donc utilisable qu'environ une minute.
 */
class KioskTokenService
{
    public const PREFIX = 'PHENIX-CHECKIN:';

    public const PERIOD = 30;

    /** @return array{payload: string, period: int, expires_in: int} */
    public function current(?int $now = null): array
    {
        $now ??= time();
        $window = intdiv($now, self::PERIOD);

        return [
            'payload' => self::PREFIX.$window.'.'.$this->sign($window),
            'period' => self::PERIOD,
            'expires_in' => self::PERIOD - ($now % self::PERIOD),
        ];
    }

    public function isValid(string $payload, ?int $now = null): bool
    {
        if (! preg_match('/^'.preg_quote(self::PREFIX, '/').'(\d+)\.([a-f0-9]{32})$/', trim($payload), $m)) {
            return false;
        }

        $window = (int) $m[1];
        $current = intdiv($now ?? time(), self::PERIOD);

        return ($window === $current || $window === $current - 1)
            && hash_equals($this->sign($window), $m[2]);
    }

    private function sign(int $window): string
    {
        return substr(hash_hmac('sha256', 'kiosk-checkin|'.$window, (string) config('app.key')), 0, 32);
    }
}
