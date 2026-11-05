<?php

use App\Enums\UserRole;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Reminder;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(SettingSeeder::class);
    $this->agent = User::factory()->create(['role' => UserRole::Agent]);
    $this->manager = User::factory()->create(['role' => UserRole::Manager]);
    $this->plan = SubscriptionPlan::factory()->create(['name' => 'Mensuel', 'duration_days' => 30, 'price' => 15000]);
    $this->member = Member::factory()->create();
    $this->subscription = Subscription::factory()->for($this->member)->for($this->plan, 'plan')->create(['price_at_subscription' => 15000]);
    Sanctum::actingAs($this->agent);
});

it('encaisse un paiement avec reçu numéroté et recalcule le statut', function () {
    $this->postJson('/api/payments', [
        'member_id' => $this->member->id,
        'subscription_id' => $this->subscription->id,
        'amount' => 15000,
        'method' => 'cash',
    ])
        ->assertCreated()
        ->assertJsonPath('data.receipt_number', 'RC-'.now()->format('Y').'-000001')
        ->assertJsonPath('data.type', 'subscription')
        ->assertJsonPath('data.subscription.balance_due', 0)
        ->assertJsonPath('data.period_start', $this->subscription->start_date->toDateString());

    expect($this->member->fresh()->status->value)->toBe('active');
});

it('accepte un acompte puis le solde exact et refuse le dépassement (RG-PAI-06)', function () {
    $base = ['member_id' => $this->member->id, 'subscription_id' => $this->subscription->id, 'method' => 'cash'];

    $this->postJson('/api/payments', $base + ['amount' => 10000])->assertCreated()->assertJsonPath('data.subscription.balance_due', 5000);
    $this->postJson('/api/payments', $base + ['amount' => 6000])->assertStatus(422)->assertJsonPath('error', 'amount_exceeds_balance')->assertJsonPath('details.balance_due', 5000);
    $this->postJson('/api/payments', $base + ['amount' => 5000])->assertCreated()->assertJsonPath('data.subscription.balance_due', 0);
    $this->postJson('/api/payments', $base + ['amount' => 1])->assertStatus(422)->assertJsonPath('error', 'amount_exceeds_balance');
});

it('exige une référence pour mobile money et virement', function () {
    $this->postJson('/api/payments', ['member_id' => $this->member->id, 'subscription_id' => $this->subscription->id, 'amount' => 5000, 'method' => 'mobile_money'])
        ->assertStatus(422)
        ->assertJsonPath('error', 'reference_required');

    $this->postJson('/api/payments', ['member_id' => $this->member->id, 'subscription_id' => $this->subscription->id, 'amount' => 5000, 'method' => 'mobile_money', 'reference' => 'MP123'])
        ->assertCreated();
});

it('valide une adhésion en attente au premier paiement (RG-PAI-09)', function () {
    $pending = Member::factory()->pending()->create();
    $sub = Subscription::factory()->for($pending)->for($this->plan, 'plan')->create();

    $this->postJson('/api/payments', ['member_id' => $pending->id, 'subscription_id' => $sub->id, 'amount' => 15000, 'method' => 'cash'])->assertCreated();

    $pending->refresh();
    expect($pending->status->value)->toBe('active')
        ->and($pending->validated_at)->not->toBeNull()
        ->and($pending->validated_by)->toBe($this->agent->id);
});

it('propose les frais d\'inscription une seule fois et les encaisse groupés (RG-PAI-08)', function () {
    $this->getJson("/api/members/{$this->member->id}/outstanding")
        ->assertOk()
        ->assertJsonPath('registration_fee_due', 5000)
        ->assertJsonPath('total_due', 20000);

    $this->postJson('/api/payments/batch', [
        'member_id' => $this->member->id,
        'method' => 'cash',
        'lines' => [
            ['type' => 'registration', 'amount' => 5000],
            ['type' => 'subscription', 'amount' => 15000, 'subscription_id' => $this->subscription->id],
        ],
    ])->assertCreated()->assertJsonCount(2, 'data');

    expect(Payment::valid()->count())->toBe(2)
        ->and(Payment::orderBy('id')->pluck('receipt_number')->all())->toBe(['RC-'.now()->format('Y').'-000001', 'RC-'.now()->format('Y').'-000002']);

    $this->getJson("/api/members/{$this->member->id}/outstanding")->assertJsonPath('registration_fee_due', 0)->assertJsonPath('total_due', 0);

    $this->postJson('/api/payments', ['member_id' => $this->member->id, 'type' => 'registration', 'amount' => 5000, 'method' => 'cash'])
        ->assertStatus(422)
        ->assertJsonPath('error', 'registration_already_paid');
});

it('utilise les frais d\'inscription propres à la formule quand ils existent', function () {
    $this->plan->update(['registration_fee' => 2000]);
    $this->getJson("/api/members/{$this->member->id}/outstanding")->assertJsonPath('registration_fee_due', 2000);
});

it('annule un paiement (gérant) : le solde remonte et le reçu est marqué annulé (RG-PAI-07)', function () {
    $payment = $this->postJson('/api/payments', ['member_id' => $this->member->id, 'subscription_id' => $this->subscription->id, 'amount' => 15000, 'method' => 'cash'])->json('data');

    $this->patchJson("/api/payments/{$payment['id']}/cancel", ['reason' => 'Erreur'])->assertForbidden();

    Sanctum::actingAs($this->manager);
    $this->patchJson("/api/payments/{$payment['id']}/cancel", ['reason' => 'Erreur de saisie'])
        ->assertOk()
        ->assertJsonPath('data.subscription.balance_due', 15000);

    $this->getJson("/api/payments/{$payment['id']}/receipt")->assertOk()->assertJsonPath('data.cancelled_at', fn ($value) => $value !== null);
});

it('fournit le journal de caisse du jour avec totaux par mode', function () {
    $this->postJson('/api/payments', ['member_id' => $this->member->id, 'subscription_id' => $this->subscription->id, 'amount' => 10000, 'method' => 'cash'])->assertCreated();
    $this->postJson('/api/payments', ['member_id' => $this->member->id, 'type' => 'registration', 'amount' => 5000, 'method' => 'mobile_money', 'reference' => 'MP1'])->assertCreated();

    $this->getJson('/api/payments')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.totals.total', 15000)
        ->assertJsonPath('meta.totals.by_method.cash', 10000)
        ->assertJsonPath('meta.totals.by_method.mobile_money', 5000)
        ->assertJsonPath('meta.totals.by_type.registration', 5000);
});

it('renvoie le reçu en JSON avec le montant en lettres', function () {
    $payment = $this->postJson('/api/payments', ['member_id' => $this->member->id, 'subscription_id' => $this->subscription->id, 'amount' => 15000, 'method' => 'cash'])->json('data');

    $this->getJson("/api/payments/{$payment['id']}/receipt?format=json")
        ->assertOk()
        ->assertJsonPath('data.receipt_number', $payment['receipt_number'])
        ->assertJsonPath('data.amount_in_words', 'quinze mille')
        ->assertJsonPath('data.member.full_name', $this->member->full_name)
        ->assertJsonPath('data.club.name', 'Phenix Club')
        ->assertJsonPath('data.club.logo_url', asset('images/logo-phenix.png'));
});

it('liste les retards et envoie une relance au plus une fois par 7 jours (RG-REL-04)', function () {
    $late = Member::factory()->create();
    Subscription::factory()->for($late)->for($this->plan, 'plan')->expiredDaysAgo(12)->create();

    $this->getJson('/api/late-payments')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.days_late', 7)
        ->assertJsonPath('data.0.amount_due', 15000)
        ->assertJsonPath('summary.amount', 15000);

    $this->postJson('/api/reminders', ['member_id' => $late->id, 'type' => 'late_payment', 'channel' => 'whatsapp'])
        ->assertCreated()
        ->assertJsonPath('data.status', 'sent');

    expect(Reminder::first()->message)->toContain('15 000 FCFA');

    $this->postJson('/api/reminders', ['member_id' => $late->id, 'type' => 'late_payment', 'channel' => 'sms'])
        ->assertStatus(422)
        ->assertJsonPath('error', 'reminder_too_recent');

    $this->postJson('/api/reminders', ['member_id' => $late->id, 'type' => 'late_payment', 'channel' => 'sms', 'override' => true])->assertForbidden();

    Sanctum::actingAs($this->manager);
    $this->postJson('/api/reminders', ['member_id' => $late->id, 'type' => 'late_payment', 'channel' => 'sms', 'override' => true])->assertCreated();

    $this->getJson('/api/late-payments')->assertJsonPath('data.0.last_reminder_at', fn ($v) => $v !== null);
});

it('liste les absents selon le seuil et exclut les retards (RG-ABS-01, 02)', function () {
    $absent = Member::factory()->active()->create(['joined_at' => now()->subDays(40)->toDateString()]);
    Subscription::factory()->for($absent)->for($this->plan, 'plan')->create(['start_date' => now()->subDays(40)->toDateString(), 'end_date' => now()->addDays(50)->toDateString()]);
    $absent->attendances()->create(['attended_on' => now()->subDays(20)->toDateString(), 'checked_in_at' => now()->subDays(20), 'method' => 'manual']);

    $assiduous = Member::factory()->active()->create();
    Subscription::factory()->for($assiduous)->for($this->plan, 'plan')->create();
    $assiduous->attendances()->create(['attended_on' => now()->subDays(2)->toDateString(), 'checked_in_at' => now()->subDays(2), 'method' => 'manual']);

    $this->getJson('/api/absences')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.member.id', $absent->id)
        ->assertJsonPath('data.0.days_absent', 20);

    $this->getJson('/api/absences?days=25')->assertJsonCount(0, 'data');
});
