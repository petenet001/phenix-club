<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function __construct(private readonly MemberService $members) {}

    /**
     * Date de début proposée (RG-ABO-03) : lendemain de la fin de l'abonnement actif ou à venir, sinon aujourd'hui.
     */
    public function suggestedStartDate(Member $member): Carbon
    {
        $latest = $member->subscriptions()
            ->currentOrUpcoming()
            ->orderByDesc('end_date')
            ->first();

        if ($latest !== null) {
            return $latest->end_date->copy()->addDay();
        }

        return now()->startOfDay();
    }

    /**
     * Souscription ou renouvellement (UC-05).
     * RG-ABO-01 : end_date = start_date + duration_days - 1
     * RG-ABO-02 : prix figé
     * RG-ABO-04 : un seul abonnement active, un seul pending
     */
    public function subscribe(Member $member, SubscriptionPlan $plan, ?Carbon $startDate, ?User $actor): Subscription
    {
        if ($member->isSuspended()) {
            throw new BusinessRuleException('member_suspended', 'Adhérent suspendu, voir le gérant.');
        }

        if (! $plan->is_active) {
            throw new BusinessRuleException('plan_inactive', 'Cette formule n\'est plus proposée.');
        }

        return DB::transaction(function () use ($member, $plan, $startDate, $actor) {
            $start = ($startDate ?? $this->suggestedStartDate($member))->startOfDay();
            $end = $start->copy()->addDays($plan->duration_days - 1);
            $today = now()->startOfDay();

            $status = $start->greaterThan($today) ? SubscriptionStatus::Pending : SubscriptionStatus::Active;

            if ($end->lessThan($today)) {
                $status = SubscriptionStatus::Expired;
            }

            $overlapping = $member->subscriptions()
                ->notCancelled()
                ->whereDate('start_date', '<=', $end->toDateString())
                ->whereDate('end_date', '>=', $start->toDateString())
                ->first();

            if ($overlapping !== null) {
                throw new BusinessRuleException(
                    'subscription_overlap',
                    sprintf(
                        'Un abonnement couvre déjà la période du %s au %s. Date de début proposée : %s.',
                        $overlapping->start_date->format('d/m/Y'),
                        $overlapping->end_date->format('d/m/Y'),
                        $this->suggestedStartDate($member)->format('d/m/Y')
                    ),
                    ['suggested_start_date' => $this->suggestedStartDate($member)->toDateString()]
                );
            }

            if ($status === SubscriptionStatus::Pending && $member->subscriptions()->where('status', SubscriptionStatus::Pending->value)->exists()) {
                throw new BusinessRuleException('pending_already_exists', 'Un abonnement à venir existe déjà pour cet adhérent.');
            }

            $subscription = Subscription::create([
                'member_id' => $member->id,
                'subscription_plan_id' => $plan->id,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'price_at_subscription' => $plan->price,
                'status' => $status,
                'created_by' => $actor?->id,
            ]);

            $this->members->refreshStatus($member);

            return $subscription->load(['plan', 'payments', 'creator']);
        });
    }

    /**
     * Annulation par le gérant (UC-18). Les paiements restent rattachés.
     */
    public function cancel(Subscription $subscription, string $reason): Subscription
    {
        if ($subscription->isCancelled()) {
            throw new BusinessRuleException('already_cancelled', 'Cet abonnement est déjà annulé.');
        }

        return DB::transaction(function () use ($subscription, $reason) {
            $subscription->update([
                'status' => SubscriptionStatus::Cancelled,
                'cancelled_at' => now(),
                'cancel_reason' => $reason,
            ]);

            $this->members->refreshStatus($subscription->member);

            return $subscription->refresh()->load(['plan', 'payments', 'creator']);
        });
    }

    /**
     * Tâche quotidienne (RG-ABO-07, UC-20). Idempotente.
     * Retourne ['activated' => n, 'expired' => n, 'members_updated' => n].
     */
    public function refreshStatuses(): array
    {
        $today = now()->toDateString();

        $activated = Subscription::query()
            ->where('status', SubscriptionStatus::Pending->value)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->update(['status' => SubscriptionStatus::Active->value, 'updated_at' => now()]);

        $expired = Subscription::query()
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Pending->value])
            ->whereDate('end_date', '<', $today)
            ->update(['status' => SubscriptionStatus::Expired->value, 'updated_at' => now()]);

        $membersUpdated = 0;

        Member::query()->whereIn('status', ['active', 'inactive'])->chunkById(200, function ($members) use (&$membersUpdated) {
            foreach ($members as $member) {
                $before = $member->status;
                $this->members->refreshStatus($member);
                if ($member->status !== $before) {
                    $membersUpdated++;
                }
            }
        });

        return compact('activated', 'expired', 'membersUpdated');
    }
}
