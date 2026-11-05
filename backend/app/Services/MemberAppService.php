<?php

namespace App\Services;

use App\Enums\PaymentType;
use App\Enums\SubscriptionStatus;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Str;

/**
 * Vues de l'application adhérent, construites à partir des règles existantes
 * (pointage, encaissement, retards) sans les dupliquer.
 */
class MemberAppService
{
    public function __construct(
        private readonly AttendanceService $attendances,
        private readonly PaymentService $payments,
        private readonly MemberService $members,
        private readonly ReceiptService $receipts,
    ) {}

    /** Code d'accès à 6 chiffres remis par l'accueil ; il remplace le précédent. */
    public function grantAccess(Member $member): string
    {
        $code = (string) random_int(100000, 999999);

        $member->forceFill(['app_password' => $code, 'app_access_granted_at' => now()])->save();
        $member->tokens()->delete();

        return $code;
    }

    public function revokeAccess(Member $member): void
    {
        $member->forceFill(['app_password' => null, 'app_access_granted_at' => null])->save();
        $member->tokens()->delete();
    }

    public function profile(Member $member): array
    {
        return [
            'id' => $member->id,
            'code' => $member->code,
            'first_name' => $member->first_name,
            'last_name' => $member->last_name,
            'full_name' => $member->full_name,
            'phone' => $member->phone,
            'email' => $member->email,
            'address' => $member->address,
            'birth_date' => $member->birth_date?->toDateString(),
            // Adresse construite sur l'hôte de la requête (et non APP_URL = localhost) : un téléphone qui
            // joint l'API par l'IP du réseau local doit pouvoir charger la photo par la même adresse.
            'photo_url' => $member->photo_path ? asset('storage/'.$member->photo_path) : null,
            'status' => $member->status->value,
            'status_label' => $member->status->label(),
            'joined_at' => $member->joined_at->toDateString(),
            'qr_token' => $member->qr_token,
        ];
    }

    /** Écran d'accueil : abonnement, accès du jour, ce qui reste à payer, fréquentation. */
    public function summary(Member $member): array
    {
        $current = $member->currentSubscription()->with(['plan', 'payments'])->first();
        $upcoming = $member->subscriptions()->where('status', SubscriptionStatus::Pending->value)
            ->with(['plan', 'payments'])->reorder('start_date')->first();
        $access = $this->attendances->check($member);
        $balance = $this->balance($member);
        $monthStart = now()->startOfMonth()->toDateString();

        return [
            'profile' => $this->profile($member),
            'subscription' => $current ? $this->subscription($current) : null,
            'upcoming_subscription' => $upcoming && $upcoming->id !== $current?->id ? $this->subscription($upcoming) : null,
            'access' => [
                'allowed' => $access['allowed'],
                'code' => $access['error'],
                'message' => $access['allowed'] ? ($access['warning'] ?? 'Accès autorisé aujourd\'hui.') : $access['message'],
                'warning' => $access['allowed'] && $access['warning'] !== null,
            ],
            'balance' => [
                'total_due' => $balance['total_due'],
                'is_late' => $balance['late']['is_late'],
                'days_late' => $balance['late']['days_late'],
            ],
            'stats' => [
                'attendances_this_month' => $member->attendances()->whereDate('attended_on', '>=', $monthStart)->count(),
                'attendances_total' => $member->attendances()->count(),
                'last_attendance_at' => $member->attendances()->value('checked_in_at')?->toIso8601String(),
            ],
            'club' => $this->club(),
        ];
    }

    /**
     * Ce que l'adhérent doit : soldes d'abonnements, frais d'inscription, et retard de renouvellement.
     * Le retard de renouvellement (abonnement échu non renouvelé) n'est pas une dette : il est
     * signalé à part avec le montant attendu, comme sur l'écran Retards du personnel.
     */
    public function balance(Member $member): array
    {
        $outstanding = $this->payments->outstanding($member);
        $late = $this->members->lateInfo($member);

        $items = [];
        if ($outstanding['registration_fee_due'] > 0) {
            $items[] = ['type' => PaymentType::Registration->value, 'label' => 'Frais d\'inscription', 'amount' => $outstanding['registration_fee_due'], 'period_start' => null, 'period_end' => null];
        }
        foreach ($outstanding['subscriptions'] as $s) {
            $items[] = ['type' => PaymentType::Subscription->value, 'label' => 'Solde '.$s['plan'], 'amount' => $s['balance_due'], 'period_start' => $s['start_date'], 'period_end' => $s['end_date'], 'price' => $s['price'], 'amount_paid' => $s['amount_paid']];
        }

        return [
            'total_due' => $outstanding['total_due'],
            'items' => $items,
            'late' => [
                'is_late' => $late['is_late'],
                'days_late' => $late['days_late'],
                'amount_due' => $late['amount_due'],
                'message' => $late['is_late']
                    ? "Paiement en retard de {$late['days_late']} jour(s). Passez à l'accueil pour régulariser."
                    : null,
            ],
        ];
    }

    public function subscription(Subscription $s): array
    {
        $today = now()->startOfDay();

        return [
            'id' => $s->id,
            'plan' => $s->plan->name,
            'start_date' => $s->start_date->toDateString(),
            'end_date' => $s->end_date->toDateString(),
            'status' => $s->status->value,
            'status_label' => $s->status->label(),
            'days_total' => (int) $s->start_date->diffInDays($s->end_date) + 1,
            'days_left' => $s->end_date->lt($today) ? 0 : (int) $today->diffInDays($s->end_date) + 1,
            'sessions_count' => $s->plan->sessions_count,
            'sessions_used' => $s->sessions_used,
            'price' => $s->price_at_subscription,
            'amount_paid' => $s->amount_paid,
            'balance_due' => $s->balance_due,
        ];
    }

    public function plans(): array
    {
        return SubscriptionPlan::query()->where('is_active', true)->orderBy('sort_order')->get()
            ->map(fn (SubscriptionPlan $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'price' => $p->price,
                'duration_days' => $p->duration_days,
                'sessions_count' => $p->sessions_count,
                'description' => $p->description,
            ])->all();
    }

    public function club(): array
    {
        $club = $this->receipts->club();
        $phone = preg_replace('/\D/', '', (string) $club['phone']);

        return $club + [
            'logo_url' => asset('images/logo-phenix.png'),
            'whatsapp_url' => $phone !== '' ? 'https://wa.me/'.$phone : null,
        ];
    }

    /** Sert à nommer le token ("iPhone de Grâce") dans la liste des appareils connectés. */
    public static function deviceName(?string $name): string
    {
        return Str::limit($name ?: 'mobile', 60, '');
    }
}
