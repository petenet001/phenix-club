<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentType;
use App\Exceptions\BusinessRuleException;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(private readonly MemberService $members) {}

    /**
     * Ce qu'il reste à encaisser pour un adhérent (écran d'encaissement).
     * ['registration_fee_due' => int, 'subscriptions' => [ {id, plan, period, price, paid, balance_due} ], 'total_due' => int]
     */
    public function outstanding(Member $member): array
    {
        $registrationPaid = $member->payments()->valid()->where('type', PaymentType::Registration->value)->exists();
        $lastSub = $member->subscriptions()->notCancelled()->with('plan')->orderByDesc('end_date')->first();
        $fee = $registrationPaid ? 0 : ($lastSub?->plan?->effectiveRegistrationFee() ?? (int) \App\Models\Setting::get('registration_fee', 0));

        $subs = $member->subscriptions()->notCancelled()->with(['plan', 'payments'])->orderByDesc('end_date')->get()
            ->filter(fn (Subscription $s) => $s->balance_due > 0)
            ->map(fn (Subscription $s) => [
                'id' => $s->id,
                'plan' => $s->plan->name,
                'start_date' => $s->start_date->toDateString(),
                'end_date' => $s->end_date->toDateString(),
                'price' => $s->price_at_subscription,
                'amount_paid' => $s->amount_paid,
                'balance_due' => $s->balance_due,
                'status' => $s->status->value,
            ])->values()->all();

        return [
            'registration_fee_due' => $fee,
            'subscriptions' => $subs,
            'total_due' => $fee + array_sum(array_column($subs, 'balance_due')),
        ];
    }

    /**
     * Encaissement (UC-07, RG-PAI-01 à 09).
     * Valide l'adhésion si elle était en attente (RG-PAI-09).
     */
    public function record(Member $member, array $data, ?User $actor): Payment
    {
        $type = PaymentType::from($data['type'] ?? PaymentType::Subscription->value);
        $method = PaymentMethod::from($data['method']);
        $amount = (int) $data['amount'];

        if ($member->isSuspended()) {
            throw new BusinessRuleException('member_suspended', 'Adhérent suspendu, voir le gérant.');
        }

        if ($amount <= 0) {
            throw new BusinessRuleException('invalid_amount', 'Le montant doit être supérieur à zéro.');
        }

        if ($method !== PaymentMethod::Cash && blank($data['reference'] ?? null)) {
            throw new BusinessRuleException('reference_required', 'La référence de transaction est obligatoire pour '.$method->label().'.');
        }

        $subscription = null;
        if (! empty($data['subscription_id'])) {
            $subscription = Subscription::with(['plan', 'payments'])->findOrFail($data['subscription_id']);
            if ($subscription->member_id !== $member->id) {
                throw new BusinessRuleException('subscription_mismatch', 'Cet abonnement n\'appartient pas à cet adhérent.');
            }
            if ($subscription->isCancelled()) {
                throw new BusinessRuleException('subscription_cancelled', 'Abonnement annulé, aucun paiement possible.');
            }
        }

        if ($type === PaymentType::Subscription) {
            if ($subscription === null) {
                throw new BusinessRuleException('subscription_required', 'Choisissez l\'abonnement à régler.');
            }
            if ($amount > $subscription->balance_due) {
                throw new BusinessRuleException('amount_exceeds_balance', sprintf(
                    'Le montant dépasse le solde dû (%s FCFA).',
                    number_format($subscription->balance_due, 0, ',', ' ')
                ), ['balance_due' => $subscription->balance_due]);
            }
        }

        if ($type === PaymentType::Registration && $member->payments()->valid()->where('type', PaymentType::Registration->value)->exists()) {
            throw new BusinessRuleException('registration_already_paid', 'Les frais d\'inscription ont déjà été réglés.');
        }

        return DB::transaction(function () use ($member, $data, $actor, $type, $method, $amount, $subscription) {
            $paidAt = ! empty($data['paid_at']) ? Carbon::parse($data['paid_at']) : now();

            $payment = Payment::create([
                'receipt_number' => Payment::nextReceiptNumber(null, (int) $paidAt->format('Y')),
                'member_id' => $member->id,
                'subscription_id' => $subscription?->id,
                'type' => $type,
                'amount' => $amount,
                'method' => $method,
                'reference' => $data['reference'] ?? null,
                'paid_at' => $paidAt,
                'period_start' => $data['period_start'] ?? $subscription?->start_date?->toDateString(),
                'period_end' => $data['period_end'] ?? $subscription?->end_date?->toDateString(),
                'received_by' => $actor?->id,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($member->isPending()) {
                $this->members->validateMembership($member, $actor);
            } else {
                $this->members->refreshStatus($member);
            }

            return $payment->load(['member', 'subscription.plan', 'receiver']);
        });
    }

    /**
     * Encaissement groupé à la première adhésion : frais d'inscription + abonnement sur un seul reçu par ligne.
     * Retourne la liste des paiements créés.
     */
    public function recordBatch(Member $member, array $lines, array $common, ?User $actor): array
    {
        return DB::transaction(function () use ($member, $lines, $common, $actor) {
            $payments = [];
            foreach ($lines as $line) {
                $payments[] = $this->record($member, array_merge($common, $line), $actor);
            }

            return $payments;
        });
    }

    /** Annulation par le gérant (RG-PAI-07). */
    public function cancel(Payment $payment, string $reason): Payment
    {
        if ($payment->isCancelled()) {
            throw new BusinessRuleException('already_cancelled', 'Ce paiement est déjà annulé.');
        }

        return DB::transaction(function () use ($payment, $reason) {
            $payment->update(['cancelled_at' => now(), 'cancel_reason' => $reason]);
            $this->members->refreshStatus($payment->member);

            return $payment->refresh()->load(['member', 'subscription.plan', 'receiver']);
        });
    }

    public function paginate(array $filters, int $perPage = 30): LengthAwarePaginator
    {
        return $this->filteredQuery($filters)
            ->with(['member', 'subscription.plan', 'receiver'])
            ->orderByDesc('paid_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Totaux par mode et par type sur les paiements non annulés filtrés. */
    public function totals(array $filters): array
    {
        $rows = $this->filteredQuery($filters)->valid()
            ->selectRaw('method, type, SUM(amount) AS total, COUNT(*) AS count')
            ->groupBy('method', 'type')
            ->get();

        $byMethod = array_fill_keys(PaymentMethod::values(), 0);
        $byType = array_fill_keys(PaymentType::values(), 0);
        $total = 0;
        $count = 0;

        foreach ($rows as $row) {
            $byMethod[$row->method->value] += (int) $row->total;
            $byType[$row->type->value] += (int) $row->total;
            $total += (int) $row->total;
            $count += (int) $row->count;
        }

        return ['total' => $total, 'count' => $count, 'by_method' => $byMethod, 'by_type' => $byType];
    }

    private function filteredQuery(array $filters)
    {
        $query = Payment::query();

        if (! empty($filters['date'])) {
            $query->paidBetween($filters['date'], $filters['date']);
        } elseif (! empty($filters['from']) && ! empty($filters['to'])) {
            $query->paidBetween($filters['from'], $filters['to']);
        }

        if (! empty($filters['method'])) {
            $query->where('method', $filters['method']);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['member_id'])) {
            $query->where('member_id', $filters['member_id']);
        }
        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('receipt_number', 'ilike', "%{$term}%")
                    ->orWhere('reference', 'ilike', "%{$term}%")
                    ->orWhereHas('member', fn ($m) => $m->search($term));
            });
        }
        if (isset($filters['cancelled'])) {
            $filters['cancelled'] ? $query->whereNotNull('cancelled_at') : $query->whereNull('cancelled_at');
        }

        return $query;
    }
}
