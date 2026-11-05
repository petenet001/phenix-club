<?php

namespace App\Services\Messaging;

use App\Enums\ReminderChannel;

interface MessageGateway
{
    /**
     * Envoie un message. Retourne ['ok' => bool, 'error' => ?string, 'link' => ?string].
     * 'link' est renseigné quand l'envoi est manuel (ex : lien wa.me à ouvrir depuis l'accueil).
     */
    public function send(ReminderChannel $channel, string $phone, string $message): array;
}
