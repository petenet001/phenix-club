<?php

namespace App\Services;

use App\Enums\ReminderChannel;
use App\Enums\ReminderType;
use App\Exceptions\BusinessRuleException;
use App\Models\Member;
use App\Models\Reminder;
use App\Models\Setting;
use App\Models\User;
use App\Services\Messaging\MessageGateway;

class ReminderService
{
    public function __construct(
        private readonly MessageGateway $gateway,
        private readonly MemberService $members,
    ) {}

    /** Message par défaut selon le type (RG-REL-02). */
    public function defaultMessage(Member $member, ReminderType $type): string
    {
        $club = Setting::get('club_name', 'Phenix Club');
        $first = $member->first_name;

        return match ($type) {
            ReminderType::LatePayment => (function () use ($member, $first, $club) {
                $late = $this->members->lateInfo($member);
                $amount = number_format($late['amount_due'], 0, ',', ' ');
                $sub = $member->subscriptions()->notCancelled()->orderByDesc('end_date')->first();
                $end = $sub?->end_date->format('d/m/Y') ?? '';

                return "Bonjour {$first}, votre abonnement {$club} est arrivé à échéance le {$end}. Montant attendu : {$amount} FCFA. Merci de passer à l'accueil pour régulariser.";
            })(),
            ReminderType::Absence => "Bonjour {$first}, cela fait un moment que nous ne vous avons pas vu à {$club}. Votre abonnement est actif, on vous attend pour votre prochaine séance !",
            ReminderType::ExpirySoon => (function () use ($member, $first, $club) {
                $sub = $member->subscriptions()->notCancelled()->orderByDesc('end_date')->first();
                $end = $sub?->end_date->format('d/m/Y') ?? '';

                return "Bonjour {$first}, votre abonnement {$club} se termine le {$end}. Pensez à le renouveler à l'accueil pour continuer sans interruption.";
            })(),
        };
    }

    /**
     * Envoi d'une relance (UC-11). RG-REL-04 : pas plus d'une relance du même type par 7 jours,
     * sauf $override (gérant).
     */
    public function send(Member $member, ReminderType $type, ReminderChannel $channel, ?string $message, ?User $actor, bool $override = false): Reminder
    {
        if (blank($member->phone)) {
            throw new BusinessRuleException('phone_missing', 'Aucun numéro de téléphone pour cet adhérent.');
        }

        if (! $override && Reminder::recentlySentTo($member->id, $type)) {
            $last = $member->reminders()->where('type', $type->value)->where('status', 'sent')->latest('sent_at')->first();
            throw new BusinessRuleException('reminder_too_recent', 'Déjà relancé le '.$last->sent_at->format('d/m/Y').'.', [
                'last_sent_at' => $last->sent_at->toIso8601String(),
            ]);
        }

        if ($override && ($actor === null || ! $actor->isManager())) {
            throw new BusinessRuleException('override_requires_manager', 'Seul le gérant peut relancer avant 7 jours.', [], 403);
        }

        $message = filled($message) ? $message : $this->defaultMessage($member, $type);
        $result = $this->gateway->send($channel, $member->phone, $message);

        $reminder = Reminder::create([
            'member_id' => $member->id,
            'type' => $type,
            'channel' => $channel,
            'message' => $message,
            'status' => $result['ok'] ? 'sent' : 'failed',
            'error' => $result['error'],
            'sent_at' => now(),
            'sent_by' => $actor?->id,
        ]);

        $reminder->setAttribute('link', $result['link'] ?? null);

        return $reminder->load(['member', 'sender']);
    }
}
