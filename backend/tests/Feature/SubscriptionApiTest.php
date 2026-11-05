<?php

use App\Enums\UserRole;
use App\Models\Member;
use App\Models\Payment;
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
});

it('souscrit un abonnement qui démarre aujourd\'hui, prix figé, fin = début + durée - 1 (RG-ABO-01, 02)', function () {
    Sanctum::actingAs($this->agent);

    $this->postJson('/api/subscriptions', [
        'member_id' => $this->member->id,
        'subscription_plan_id' => $this->plan->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.start_date', now()->toDateString())
        ->assertJsonPath('data.end_date', now()->addDays(29)->toDateString())
        ->assertJsonPath('data.price_at_subscription', 15000)
        ->assertJsonPath('data.balance_due', 15000)
        ->assertJsonPath('data.status', 'active');

    expect($this->member->fresh()->status->value)->toBe('active');

    $this->plan->update(['price' => 20000]);
    expect(Subscription::first()->price_at_subscription)->toBe(15000);
});

it('renouvelle en continuité de l\'abonnement en cours (RG-ABO-03)', function () {
    Sanctum::actingAs($this->agent);
    Subscription::factory()->for($this->member)->for($this->plan, 'plan')->create([
        'start_date' => now()->subDays(10)->toDateString(),
        'end_date' => now()->addDays(19)->toDateString(),
    ]);

    $this->getJson("/api/members/{$this->member->id}/subscriptions/suggested-start")
        ->assertOk()
        ->assertJsonPath('suggested_start_date', now()->addDays(20)->toDateString());

    $this->postJson('/api/subscriptions', [
        'member_id' => $this->member->id,
        'subscription_plan_id' => $this->plan->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.start_date', now()->addDays(20)->toDateString())
        ->assertJsonPath('data.status', 'pending');
});

it('refuse un chevauchement de période et propose une date', function () {
    Sanctum::actingAs($this->agent);
    Subscription::factory()->for($this->member)->for($this->plan, 'plan')->create();

    $this->postJson('/api/subscriptions', [
        'member_id' => $this->member->id,
        'subscription_plan_id' => $this->plan->id,
        'start_date' => now()->addDays(5)->toDateString(),
    ])
        ->assertStatus(422)
        ->assertJsonPath('error', 'subscription_overlap')
        ->assertJsonPath('details.suggested_start_date', now()->addDays(30)->toDateString());
});

it('refuse un second abonnement à venir (RG-ABO-04)', function () {
    Sanctum::actingAs($this->agent);
    Subscription::factory()->for($this->member)->for($this->plan, 'plan')->create();
    Subscription::factory()->for($this->member)->for($this->plan, 'plan')->pending()->create([
        'start_date' => now()->addDays(30)->toDateString(),
        'end_date' => now()->addDays(59)->toDateString(),
    ]);

    $this->postJson('/api/subscriptions', [
        'member_id' => $this->member->id,
        'subscription_plan_id' => $this->plan->id,
    ])
        ->assertStatus(422)
        ->assertJsonPath('error', 'pending_already_exists');
});

it('refuse la souscription pour un adhérent suspendu', function () {
    Sanctum::actingAs($this->agent);
    $suspended = Member::factory()->suspended()->create();

    $this->postJson('/api/subscriptions', [
        'member_id' => $suspended->id,
        'subscription_plan_id' => $this->plan->id,
    ])->assertStatus(422)->assertJsonPath('error', 'member_suspended');
});

it('démarre après une expiration : date proposée = aujourd\'hui', function () {
    Sanctum::actingAs($this->agent);
    Subscription::factory()->for($this->member)->for($this->plan, 'plan')->expiredDaysAgo(10)->create();

    $this->getJson("/api/members/{$this->member->id}/subscriptions/suggested-start")
        ->assertJsonPath('suggested_start_date', now()->toDateString());
});

it('annule un abonnement (gérant uniquement) et recalcule le statut de l\'adhérent', function () {
    $subscription = Subscription::factory()->for($this->member)->for($this->plan, 'plan')->create();
    $this->member->update(['status' => 'active']);

    Sanctum::actingAs($this->agent);
    $this->patchJson("/api/subscriptions/{$subscription->id}/cancel", ['reason' => 'Erreur'])->assertForbidden();

    Sanctum::actingAs($this->manager);
    $this->patchJson("/api/subscriptions/{$subscription->id}/cancel", ['reason' => 'Erreur de saisie'])
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    expect($this->member->fresh()->status->value)->toBe('inactive');

    $this->patchJson("/api/subscriptions/{$subscription->id}/cancel", ['reason' => 'Encore'])
        ->assertStatus(422)
        ->assertJsonPath('error', 'already_cancelled');
});

it('calcule le solde dû à partir des paiements non annulés (RG-ABO-06)', function () {
    Sanctum::actingAs($this->agent);
    $subscription = Subscription::factory()->for($this->member)->for($this->plan, 'plan')->create(['price_at_subscription' => 15000]);

    Payment::create(['receipt_number' => 'RC-2026-000001', 'member_id' => $this->member->id, 'subscription_id' => $subscription->id, 'amount' => 10000, 'method' => 'cash', 'paid_at' => now()]);
    Payment::create(['receipt_number' => 'RC-2026-000002', 'member_id' => $this->member->id, 'subscription_id' => $subscription->id, 'amount' => 3000, 'method' => 'cash', 'paid_at' => now(), 'cancelled_at' => now()]);

    $this->getJson("/api/subscriptions/{$subscription->id}")
        ->assertOk()
        ->assertJsonPath('data.amount_paid', 10000)
        ->assertJsonPath('data.balance_due', 5000);
});

it('détecte un retard de paiement sur la fiche adhérent (RG-RET-01)', function () {
    Sanctum::actingAs($this->agent);
    // Expiré depuis 12 jours, seuil 5 : 7 jours de retard, renouvellement attendu
    Subscription::factory()->for($this->member)->for($this->plan, 'plan')->expiredDaysAgo(12)->create();

    $this->getJson("/api/members/{$this->member->id}")
        ->assertOk()
        ->assertJsonPath('data.is_late', true)
        ->assertJsonPath('data.days_late', 7)
        ->assertJsonPath('data.amount_due', 15000);

    $this->getJson('/api/members?late=1')->assertOk()->assertJsonCount(1, 'data');
});

it('ne signale pas de retard à J+5 exactement', function () {
    Sanctum::actingAs($this->agent);
    Subscription::factory()->for($this->member)->for($this->plan, 'plan')->expiredDaysAgo(5)->create();

    $this->getJson("/api/members/{$this->member->id}")->assertJsonPath('data.is_late', false);
});

it('met à jour les statuts via la commande quotidienne (RG-ABO-07)', function () {
    $pending = Subscription::factory()->for($this->member)->for($this->plan, 'plan')->create([
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(29)->toDateString(),
        'status' => 'pending',
    ]);
    $other = Member::factory()->active()->create();
    $old = Subscription::factory()->for($other)->for($this->plan, 'plan')->create([
        'start_date' => now()->subDays(40)->toDateString(),
        'end_date' => now()->subDays(10)->toDateString(),
        'status' => 'active',
    ]);

    $this->artisan('subscriptions:refresh-status')->assertSuccessful();

    expect($pending->fresh()->status->value)->toBe('active')
        ->and($old->fresh()->status->value)->toBe('expired')
        ->and($this->member->fresh()->status->value)->toBe('active')
        ->and($other->fresh()->status->value)->toBe('inactive');
});

it('interdit la gestion des formules à un agent et empêche la suppression d\'une formule utilisée', function () {
    Sanctum::actingAs($this->agent);
    $this->postJson('/api/subscription-plans', ['name' => 'Test', 'duration_days' => 7, 'price' => 5000])->assertForbidden();

    Sanctum::actingAs($this->manager);
    $this->postJson('/api/subscription-plans', ['name' => 'Hebdo', 'duration_days' => 7, 'price' => 7000])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Hebdo');

    Subscription::factory()->for($this->member)->for($this->plan, 'plan')->create();
    $this->deleteJson("/api/subscription-plans/{$this->plan->id}")
        ->assertStatus(422)
        ->assertJsonPath('error', 'plan_in_use');
});
