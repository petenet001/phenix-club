<?php

namespace App\Services;

use App\Enums\AttendanceMethod;
use App\Exceptions\BusinessRuleException;
use App\Models\Attendance;
use App\Models\Member;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    /**
     * Résultat d'une vérification : abonnement utilisable, avertissement éventuel ou code de refus.
     * ['allowed' => bool, 'subscription' => ?Subscription, 'warning' => ?string, 'error' => ?string, 'message' => ?string]
     */
    public function check(Member $member, ?\Carbon\CarbonInterface $at = null): array
    {
        $at = $at ?? now();
        $today = $at->toDateString();

        if ($member->isPending()) {
            return $this->refusal('membership_pending', 'Adhésion en attente de validation : passer en caisse.');
        }

        if ($member->isSuspended()) {
            return $this->refusal('member_suspended', 'Adhérent suspendu, voir le gérant.');
        }

        if ($member->attendances()->whereDate('attended_on', $today)->exists()) {
            $time = $member->attendances()->whereDate('attended_on', $today)->first()->checked_in_at->format('H\hi');

            return $this->refusal('already_checked_in_today', "Déjà pointé aujourd'hui à {$time}.");
        }

        // RG-PTG-01 : abonnement couvrant la date (hors tolérance d'abord)
        $covering = $member->subscriptions()
            ->notCancelled()
            ->with('plan')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->orderBy('end_date')
            ->first();

        if ($covering !== null) {
            if (! $covering->hasSessionsLeft()) {
                return $this->refusal('sessions_exhausted', sprintf(
                    'Les %d séances de la formule %s sont utilisées, renouveler.',
                    $covering->plan->sessions_count,
                    $covering->plan->name
                ), $covering);
            }

            return ['allowed' => true, 'subscription' => $covering, 'warning' => null, 'error' => null, 'message' => null];
        }

        // RG-PTG-02 : fenêtre de tolérance après échéance
        $tolerance = Setting::checkinToleranceDays();
        $expired = $member->subscriptions()
            ->notCancelled()
            ->with('plan')
            ->whereDate('end_date', '<', $today)
            ->orderByDesc('end_date')
            ->first();

        if ($expired !== null && $expired->isWithinTolerance($at, $tolerance)) {
            if (! $expired->hasSessionsLeft()) {
                return $this->refusal('sessions_exhausted', 'Séances épuisées, renouveler.', $expired);
            }

            $daysSince = (int) $expired->end_date->diffInDays($at->copy()->startOfDay());
            $limit = $expired->end_date->copy()->addDays($tolerance)->format('d/m/Y');

            return [
                'allowed' => true,
                'subscription' => $expired,
                'warning' => "Abonnement expiré depuis {$daysSince} jour(s), tolérance jusqu'au {$limit}. Paiement attendu.",
                'error' => null,
                'message' => null,
            ];
        }

        $upcoming = $member->subscriptions()->notCancelled()->whereDate('start_date', '>', $today)->orderBy('start_date')->first();

        if ($upcoming !== null) {
            return $this->refusal('subscription_not_started', 'Abonnement actif à partir du '.$upcoming->start_date->format('d/m/Y').'.', $upcoming);
        }

        if ($expired !== null) {
            return $this->refusal('subscription_expired', 'Abonnement expiré le '.$expired->end_date->format('d/m/Y').', hors tolérance. Renouveler ou encaisser.', $expired);
        }

        return $this->refusal('no_valid_subscription', 'Aucun abonnement : souscrire une formule.');
    }

    /**
     * Pointage (UC-06). $force = true réservé au gérant (RG-PTG-06).
     * Retourne ['attendance' => Attendance, 'warning' => ?string].
     */
    public function checkIn(Member $member, AttendanceMethod $method, ?User $actor, bool $force = false, ?string $forceReason = null): array
    {
        $result = $this->check($member);

        if (! $result['allowed']) {
            $forceable = ! in_array($result['error'], ['already_checked_in_today', 'membership_pending'], true);

            if (! $force || ! $forceable) {
                throw new BusinessRuleException($result['error'], $result['message'], [
                    'subscription_id' => $result['subscription']?->id,
                    'forceable' => $forceable,
                ]);
            }

            if ($actor === null || ! $actor->isManager()) {
                throw new BusinessRuleException('force_requires_manager', 'Seul le gérant peut forcer un pointage.', [], 403);
            }

            if (blank($forceReason)) {
                throw new BusinessRuleException('force_reason_required', 'Un motif est obligatoire pour forcer le pointage.');
            }
        }

        return DB::transaction(function () use ($member, $method, $actor, $result, $force, $forceReason) {
            $forced = ! $result['allowed'];

            $attendance = Attendance::create([
                'member_id' => $member->id,
                'subscription_id' => $result['subscription']?->id,
                'checked_in_at' => now(),
                'attended_on' => now()->toDateString(),
                'method' => $method,
                'forced' => $forced,
                'force_reason' => $forced ? $forceReason : null,
                'recorded_by' => $actor?->id,
            ]);

            // RG-PTG-05 : consommation d'une séance pour les formules limitées, hors pointage forcé
            $subscription = $result['subscription'];
            if (! $forced && $subscription instanceof Subscription && $subscription->plan && $subscription->plan->sessions_count !== null) {
                $subscription->increment('sessions_used');
            }

            return [
                'attendance' => $attendance->load(['member', 'subscription.plan', 'recorder']),
                'warning' => $result['warning'],
            ];
        });
    }

    private function refusal(string $code, string $message, ?Subscription $subscription = null): array
    {
        return ['allowed' => false, 'subscription' => $subscription, 'warning' => null, 'error' => $code, 'message' => $message];
    }
}
