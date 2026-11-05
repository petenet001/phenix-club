<?php

namespace App\Services\Messaging;

use App\Enums\ReminderChannel;
use Illuminate\Support\Facades\Log;

/**
 * Passerelle par défaut : journalise le SMS et génère un lien WhatsApp (wa.me) prérempli
 * que l'agent ouvre depuis l'accueil. À remplacer par une passerelle réelle (Twilio, Orange SMS API,
 * WhatsApp Cloud API) en liant MessageGateway dans AppServiceProvider.
 */
class LogGateway implements MessageGateway
{
    public function send(ReminderChannel $channel, string $phone, string $message): array
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($digits, '0') && strlen($digits) === 9) {
            $digits = '242'.substr($digits, 1);
        }

        Log::info('Relance '.$channel->value, ['phone' => $phone, 'message' => $message]);

        if ($channel === ReminderChannel::Whatsapp) {
            return ['ok' => true, 'error' => null, 'link' => 'https://wa.me/'.$digits.'?text='.rawurlencode($message)];
        }

        return ['ok' => true, 'error' => null, 'link' => 'sms:'.$phone.'?body='.rawurlencode($message)];
    }
}
