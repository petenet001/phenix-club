<?php

namespace App\Services;

use App\Enums\MemberStatus;
use App\Enums\RegistrationSource;
use App\Exceptions\BusinessRuleException;
use App\Enums\SubscriptionStatus;
use App\Models\Member;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MemberService
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = Member::query()
            ->with(['currentSubscription.plan', 'lastAttendance'])
            ->search($filters['search'] ?? null)
            ->status($filters['status'] ?? null);

        if (! empty($filters['source'])) {
            $query->where('registration_source', $filters['source']);
        }

        if (! empty($filters['plan_id'])) {
            $query->whereHas('currentSubscription', fn (Builder $q) => $q->where('subscription_plan_id', $filters['plan_id']));
        }

        if (! empty($filters['late'])) {
            $query->whereIn('id', $this->lateMemberIdsQuery());
        }

        return $query->orderBy('last_name')->orderBy('first_name')->paginate($perPage)->withQueryString();
    }

    /**
     * Inscription (UC-02). La fiche est créée au statut pending (RG-ADH-05)
     * quelle que soit la source ; elle est validée à l'encaissement ou manuellement.
     */
    public function create(array $data, ?UploadedFile $photo = null, RegistrationSource $source = RegistrationSource::OnSite): Member
    {
        return DB::transaction(function () use ($data, $photo, $source) {
            if ($photo !== null) {
                $data['photo_path'] = $this->storePhoto($photo);
            }

            $data['status'] = MemberStatus::Pending;
            $data['registration_source'] = $source;

            $member = Member::create($data);

            return $member->load(['currentSubscription.plan', 'lastAttendance']);
        });
    }

    /**
     * Validation de l'adhésion (RG-ADH-06) : appelée par l'encaissement du premier paiement
     * ou manuellement par un agent / gérant. Idempotente. Le statut devient active ou inactive
     * selon les abonnements.
     */
    public function validateMembership(Member $member, ?User $actor): Member
    {
        if ($member->isSuspended()) {
            throw new BusinessRuleException('member_suspended', 'Adhérent suspendu, impossible de valider.');
        }

        if (! $member->isValidated()) {
            $member->validated_at = now();
            $member->validated_by = $actor?->id;
        }

        $member->status = $this->computeStatus($member);
        $member->save();

        return $member->refresh();
    }

    public function update(Member $member, array $data, ?UploadedFile $photo = null): Member
    {
        if ($photo !== null) {
            if ($member->photo_path) {
                Storage::disk('public')->delete($member->photo_path);
            }
            $data['photo_path'] = $this->storePhoto($photo);
        }

        $member->update($data);

        return $member->refresh()->load(['currentSubscription.plan', 'lastAttendance']);
    }

    /**
     * Changement de statut par le gérant (UC-17).
     * Suspension forcée ; réactivation = recalcul selon les abonnements (RG-ADH-03).
     */
    public function changeStatus(Member $member, MemberStatus $status, ?User $actor = null): Member
    {
        if ($status === MemberStatus::Suspended) {
            $member->update(['status' => MemberStatus::Suspended]);
        } elseif ($status === MemberStatus::Pending) {
            $member->update(['status' => MemberStatus::Pending, 'validated_at' => null, 'validated_by' => null]);
        } else {
            // active ou inactive demandé : équivaut à une validation puis un recalcul.
            // La réactivation lève d'abord la suspension, que validateMembership refuse.
            if ($member->isSuspended()) {
                $member->status = MemberStatus::Inactive;
            }

            return $this->validateMembership($member, $actor);
        }

        return $member->refresh();
    }

    /**
     * Statut calculé : active si un abonnement couvre aujourd'hui ou est dans la tolérance de pointage,
     * sinon inactive. Ne touche pas aux suspendus sauf si $force est vrai.
     */
    public function computeStatus(Member $member, bool $includeTolerance = true): MemberStatus
    {
        $today = now();
        $tolerance = $includeTolerance ? Setting::checkinToleranceDays() : 0;

        $hasValid = $member->subscriptions()
            ->notCancelled()
            ->whereDate('start_date', '<=', $today->toDateString())
            ->whereDate('end_date', '>=', $today->copy()->subDays($tolerance)->toDateString())
            ->exists();

        return $hasValid ? MemberStatus::Active : MemberStatus::Inactive;
    }

    public function refreshStatus(Member $member): Member
    {
        // pending et suspended sont des statuts manuels, jamais recalculés
        if (! $member->status->isComputed()) {
            return $member;
        }

        $computed = $this->computeStatus($member);

        if ($member->status !== $computed) {
            $member->update(['status' => $computed]);
        }

        return $member;
    }

    /**
     * Informations de retard pour un adhérent (RG-RET-01 à 03).
     * Retourne ['is_late' => bool, 'days_late' => int, 'amount_due' => int].
     */
    public function lateInfo(Member $member): array
    {
        $none = ['is_late' => false, 'days_late' => 0, 'amount_due' => 0];

        if ($member->isSuspended() || $member->isPending()) {
            return $none;
        }

        $hasCurrentOrUpcoming = $member->subscriptions()->currentOrUpcoming()->exists();

        /** @var Subscription|null $last */
        $last = $member->subscriptions()->notCancelled()->with('payments')->orderByDesc('end_date')->first();

        if ($last === null) {
            return $none;
        }

        $lateDays = Setting::latePaymentDays();
        $deadline = $last->end_date->copy()->addDays($lateDays);

        if (! now()->startOfDay()->greaterThan($deadline)) {
            return $none;
        }

        $balance = $last->balance_due;

        // Cas 1 : pas de renouvellement. Cas 2 : solde impayé sur le dernier abonnement.
        if (! $hasCurrentOrUpcoming || $balance > 0) {
            return [
                'is_late' => true,
                'days_late' => (int) $deadline->diffInDays(now()->startOfDay()),
                'amount_due' => $balance > 0 ? $balance : $last->price_at_subscription,
            ];
        }

        return $none;
    }

    /**
     * Sous-requête des ids d'adhérents en retard (RG-RET-01), utilisée par le filtre de liste.
     */
    public function lateMemberIdsQuery(): Builder
    {
        $lateDays = Setting::latePaymentDays();
        $today = now()->toDateString();

        $lastSub = Subscription::query()
            ->select('member_id')
            ->selectRaw('MAX(end_date) AS last_end')
            ->where('status', '<>', SubscriptionStatus::Cancelled->value)
            ->groupBy('member_id');

        return Member::query()
            ->select('members.id')
            ->joinSub($lastSub, 'ls', 'ls.member_id', '=', 'members.id')
            ->whereNotIn('members.status', [MemberStatus::Suspended->value, MemberStatus::Pending->value])
            ->whereRaw("ls.last_end + make_interval(days => ?) < ?::date", [$lateDays, $today])
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('subscriptions as s2')
                    ->whereColumn('s2.member_id', 'members.id')
                    ->whereIn('s2.status', [SubscriptionStatus::Active->value, SubscriptionStatus::Pending->value]);
            });
    }

    private function storePhoto(UploadedFile $photo): string
    {
        return $photo->store('members', 'public');
    }
}
