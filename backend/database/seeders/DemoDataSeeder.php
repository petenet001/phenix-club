<?php

namespace Database\Seeders;

use App\Enums\MemberStatus;
use App\Enums\PaymentMethod;
use App\Enums\RegistrationSource;
use App\Enums\SubscriptionStatus;
use App\Models\Attendance;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\TrainingProgram;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Jeu de démonstration couvrant les cas métier :
 * - adhérents à jour, en tolérance, en retard, absents, jamais abonnés, suspendus
 * - paiements complets et partiels, un paiement annulé
 * - présences sur les 30 derniers jours
 */
class DemoDataSeeder extends Seeder
{
    private int $receiptCounter = 0;

    public function run(): void
    {
        $agent = User::where('email', 'accueil@phenixclub.cg')->firstOrFail();
        $monthly = SubscriptionPlan::where('name', 'Mensuel')->firstOrFail();
        $quarterly = SubscriptionPlan::where('name', 'Trimestriel')->firstOrFail();
        $card10 = SubscriptionPlan::where('name', 'Carte 10 séances')->firstOrFail();

        $today = Carbon::today();

        DB::transaction(function () use ($agent, $monthly, $quarterly, $card10, $today) {
            // 1. Abonnement mensuel en cours, soldé, assidu
            $m1 = $this->member('Mbemba', 'Alain', 'M', '+242061000001', 'Moungali', $today->copy()->subMonths(3));
            $s1 = $this->subscription($m1, $monthly, $today->copy()->subDays(10), $agent, SubscriptionStatus::Active);
            $this->payment($m1, $s1, $monthly->price, PaymentMethod::Cash, $s1->start_date, $agent);
            $this->attendances($m1, $s1, 10, 1, $agent);

            // 2. Abonnement expiré depuis 2 jours : fenêtre de tolérance (3 jours)
            $m2 = $this->member('Ngoma', 'Clarisse', 'F', '+242061000002', 'Poto-Poto', $today->copy()->subMonths(2));
            $s2 = $this->subscription($m2, $monthly, $today->copy()->subDays(31), $agent, SubscriptionStatus::Expired);
            $this->payment($m2, $s2, $monthly->price, PaymentMethod::MobileMoney, $s2->start_date, $agent);
            $this->attendances($m2, $s2, 31, 3, $agent);

            // 3. Abonnement expiré depuis 12 jours, non renouvelé : retard de paiement
            $m3 = $this->member('Okemba', 'Dieudonné', 'M', '+242061000003', 'Talangaï', $today->copy()->subMonths(4));
            $s3 = $this->subscription($m3, $monthly, $today->copy()->subDays(41), $agent, SubscriptionStatus::Expired);
            $this->payment($m3, $s3, $monthly->price, PaymentMethod::Cash, $s3->start_date, $agent);
            $this->attendances($m3, $s3, 41, 12, $agent);

            // 4. Trimestriel en cours mais paiement partiel (solde dû)
            $m4 = $this->member('Samba', 'Prisca', 'F', '+242061000004', 'Bacongo', $today->copy()->subDays(20));
            $s4 = $this->subscription($m4, $quarterly, $today->copy()->subDays(20), $agent, SubscriptionStatus::Active);
            $this->payment($m4, $s4, 25000, PaymentMethod::BankTransfer, $s4->start_date, $agent, 'VIR-2026-1188');
            $this->attendances($m4, $s4, 20, 1, $agent);

            // 5. Actif mais absent depuis 20 jours (seuil 14)
            $m5 = $this->member('Loemba', 'Fabrice', 'M', '+242061000005', 'Ouenzé', $today->copy()->subDays(45));
            $s5 = $this->subscription($m5, $quarterly, $today->copy()->subDays(45), $agent, SubscriptionStatus::Active);
            $this->payment($m5, $s5, $quarterly->price, PaymentMethod::Cash, $s5->start_date, $agent);
            $this->attendances($m5, $s5, 45, 20, $agent);

            // 6. Carte 10 séances, 9 utilisées
            $m6 = $this->member('Itoua', 'Mireille', 'F', '+242061000006', 'Mfilou', $today->copy()->subDays(25));
            $s6 = $this->subscription($m6, $card10, $today->copy()->subDays(25), $agent, SubscriptionStatus::Active);
            $this->payment($m6, $s6, $card10->price, PaymentMethod::MobileMoney, $s6->start_date, $agent, 'MP2611.A77');
            $this->attendances($m6, $s6, 25, 2, $agent, 9);
            $s6->update(['sessions_used' => 9]);

            // 7. Demande d'adhésion sur place, non encore validée (pas de paiement)
            $this->member('Goma', 'Steve', 'M', '+242061000007', 'Madibou', $today->copy()->subDays(2), MemberStatus::Pending);

            // 7b. Demande d'adhésion depuis le site web, en attente
            $online = $this->member('Nkouka', 'Grâce', 'F', '+242061000011', 'Plateau', $today->copy()->subDay(), MemberStatus::Pending);
            $online->update(['registration_source' => RegistrationSource::Online]);

            // 8. Suspendu par le gérant malgré un abonnement valide
            $m8 = $this->member('Kouka', 'Nadège', 'F', '+242061000008', 'Djiri', $today->copy()->subDays(15));
            $s8 = $this->subscription($m8, $monthly, $today->copy()->subDays(15), $agent, SubscriptionStatus::Active);
            $this->payment($m8, $s8, $monthly->price, PaymentMethod::Cash, $s8->start_date, $agent);
            $m8->update(['status' => MemberStatus::Suspended]);

            // 9. Renouvellement anticipé : actif + pending
            $m9 = $this->member('Malonga', 'Rodrigue', 'M', '+242061000009', 'Moukondo', $today->copy()->subMonths(5));
            $s9a = $this->subscription($m9, $monthly, $today->copy()->subDays(27), $agent, SubscriptionStatus::Active);
            $this->payment($m9, $s9a, $monthly->price, PaymentMethod::Cash, $s9a->start_date, $agent);
            $s9b = $this->subscription($m9, $monthly, $s9a->end_date->copy()->addDay(), $agent, SubscriptionStatus::Pending);
            $this->payment($m9, $s9b, $monthly->price, PaymentMethod::MobileMoney, $today, $agent, 'MP2611.B12');
            $this->attendances($m9, $s9a, 27, 1, $agent);

            // 10. Paiement annulé (erreur de saisie) puis ressaisi
            $m10 = $this->member('Bouanga', 'Chantal', 'F', '+242061000010', 'Nkombo', $today->copy()->subDays(5));
            $s10 = $this->subscription($m10, $monthly, $today->copy()->subDays(5), $agent, SubscriptionStatus::Active);
            $wrong = $this->payment($m10, $s10, 10000, PaymentMethod::Cash, $s10->start_date, $agent);
            $wrong->update(['cancelled_at' => now(), 'cancel_reason' => 'Montant saisi par erreur']);
            $this->payment($m10, $s10, $monthly->price, PaymentMethod::Cash, $s10->start_date, $agent);
            $this->attendances($m10, $s10, 5, 1, $agent);

            // 11. Dépenses du mois
            $cat = fn (string $name) => ExpenseCategory::where('name', $name)->firstOrFail()->id;
            Expense::create(['expense_category_id' => $cat('Loyer'), 'label' => 'Loyer du mois', 'amount' => 150000, 'method' => 'bank_transfer', 'reference' => 'VIR-LOYER', 'spent_on' => $today->copy()->startOfMonth()->toDateString(), 'recorded_by' => $agent->id]);
            Expense::create(['expense_category_id' => $cat('Électricité'), 'label' => 'Facture E2C', 'amount' => 38000, 'method' => 'mobile_money', 'reference' => 'MP-E2C', 'spent_on' => $today->copy()->subDays(3)->toDateString(), 'recorded_by' => $agent->id]);
            Expense::create(['expense_category_id' => $cat('Entretien'), 'label' => 'Produits de nettoyage', 'amount' => 12500, 'method' => 'cash', 'spent_on' => $today->copy()->subDay()->toDateString(), 'recorded_by' => $agent->id]);

            // 12. Programme imposé par le coach pour l'adhérent 1
            $coach = User::where('email', 'coach@phenixclub.cg')->first();
            TrainingProgram::create([
                'member_id' => $m1->id,
                'kind' => 'training',
                'title' => 'Remise en forme 3 séances / semaine',
                'status' => 'imposed',
                'author_id' => $coach?->id,
                'starts_on' => $today->copy()->subDays(10)->toDateString(),
                'content' => [
                    'goal' => 'Renforcement général',
                    'days' => ['lundi', 'mercredi', 'vendredi'],
                    'sessions' => [
                        ['day' => 'lundi', 'exercises' => [['name' => 'Squat', 'sets' => 4, 'reps' => '12'], ['name' => 'Développé couché', 'sets' => 4, 'reps' => '10'], ['name' => 'Gainage', 'sets' => 3, 'reps' => '45 s']]],
                        ['day' => 'mercredi', 'exercises' => [['name' => 'Tapis de course', 'sets' => 1, 'reps' => '25 min'], ['name' => 'Fentes', 'sets' => 3, 'reps' => '12 / jambe']]],
                        ['day' => 'vendredi', 'exercises' => [['name' => 'Soulevé de terre', 'sets' => 4, 'reps' => '8'], ['name' => 'Tractions assistées', 'sets' => 3, 'reps' => '8']]],
                    ],
                ],
                'reviewed_by' => $coach?->id,
                'reviewed_at' => now(),
            ]);
            TrainingProgram::create([
                'member_id' => $m4->id,
                'kind' => 'nutrition',
                'title' => 'Plan alimentaire prise de masse',
                'status' => 'proposed',
                'author_id' => $coach?->id,
                'content' => [
                    'goal' => 'Prise de masse',
                    'meals' => [
                        ['time' => 'Petit-déjeuner', 'items' => ['Flocons d\'avoine', 'Œufs', 'Banane']],
                        ['time' => 'Déjeuner', 'items' => ['Riz', 'Poulet grillé', 'Légumes']],
                        ['time' => 'Dîner', 'items' => ['Foufou', 'Poisson', 'Saka-saka']],
                    ],
                    'advice' => 'Boire 2 litres d\'eau par jour, éviter les boissons sucrées.',
                ],
            ]);
        });
    }

    private function member(string $last, string $first, string $gender, string $phone, string $address, Carbon $joinedAt, MemberStatus $status = MemberStatus::Inactive): Member
    {
        return Member::create([
            'last_name' => $last,
            'first_name' => $first,
            'gender' => $gender,
            'phone' => $phone,
            'address' => $address,
            'joined_at' => $joinedAt->toDateString(),
            'status' => $status,
            'registration_source' => RegistrationSource::OnSite,
            'validated_at' => $status === MemberStatus::Pending ? null : $joinedAt->copy()->setTime(10, 0),
            // Accès à l'application mobile : téléphone + code 123456
            'app_password' => '123456',
            'app_access_granted_at' => now(),
        ]);
    }

    private function subscription(Member $member, SubscriptionPlan $plan, Carbon $start, User $agent, SubscriptionStatus $status): Subscription
    {
        $subscription = Subscription::create([
            'member_id' => $member->id,
            'subscription_plan_id' => $plan->id,
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays($plan->duration_days - 1)->toDateString(),
            'price_at_subscription' => $plan->price,
            'status' => $status,
            'created_by' => $agent->id,
        ]);

        if ($status === SubscriptionStatus::Active) {
            $member->update(['status' => MemberStatus::Active]);
        }

        return $subscription;
    }

    private function payment(Member $member, Subscription $subscription, int $amount, PaymentMethod $method, Carbon $paidAt, User $agent, ?string $reference = null): Payment
    {
        $this->receiptCounter++;

        return Payment::create([
            'receipt_number' => sprintf('RC-%s-%06d', $paidAt->format('Y'), $this->receiptCounter),
            'member_id' => $member->id,
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'method' => $method,
            'reference' => $reference,
            'paid_at' => $paidAt->copy()->setTime(10, 0),
            'period_start' => $subscription->start_date,
            'period_end' => $subscription->end_date,
            'received_by' => $agent->id,
        ]);
    }

    /**
     * Crée une présence tous les deux jours, de "il y a $fromDaysAgo jours" jusqu'à "il y a $untilDaysAgo jours".
     */
    private function attendances(Member $member, Subscription $subscription, int $fromDaysAgo, int $untilDaysAgo, User $agent, ?int $max = null): void
    {
        $count = 0;

        for ($d = $fromDaysAgo; $d >= $untilDaysAgo; $d -= 2) {
            if ($max !== null && $count >= $max) {
                break;
            }

            $at = Carbon::today()->subDays($d)->setTime(17 + ($d % 3), 15);

            Attendance::create([
                'member_id' => $member->id,
                'subscription_id' => $subscription->id,
                'attended_on' => $at->toDateString(),
                'checked_in_at' => $at,
                'method' => $count % 2 === 0 ? 'manual' : 'qr',
                'recorded_by' => $agent->id,
            ]);

            $count++;
        }
    }
}
