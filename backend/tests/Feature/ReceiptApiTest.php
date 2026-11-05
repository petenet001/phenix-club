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
    $plan = SubscriptionPlan::factory()->create(['name' => 'Mensuel', 'price' => 15000]);
    $member = Member::factory()->create();
    $subscription = Subscription::factory()->for($member)->for($plan, 'plan')->create(['price_at_subscription' => 15000]);
    $this->payment = Payment::create([
        'receipt_number' => 'RC-2026-000001',
        'member_id' => $member->id,
        'subscription_id' => $subscription->id,
        'amount' => 10000,
        'method' => 'cash',
        'paid_at' => now(),
        'received_by' => $this->agent->id,
    ]);
});

it('renvoie 401 JSON sans redirection à un appel API non authentifié', function () {
    $this->get("/api/payments/{$this->payment->id}/receipt")
        ->assertUnauthorized()
        ->assertJsonPath('error', 'unauthenticated');

    $this->get('/api/cashbook/export')->assertUnauthorized();
});

it('renvoie le reçu en JSON par défaut', function () {
    Sanctum::actingAs($this->agent);

    $this->getJson("/api/payments/{$this->payment->id}/receipt")
        ->assertOk()
        ->assertJsonPath('data.receipt_number', 'RC-2026-000001')
        ->assertJsonPath('data.amount_in_words', 'dix mille')
        ->assertJsonPath('data.club.name', 'Phenix Club')
        ->assertJsonPath('data.plan', 'Mensuel')
        ->assertJsonPath('data.balance_due', 5000)
        ->assertJsonPath('data.received_by', $this->agent->name)
        ->assertJsonPath('data.cancelled_at', null);
});

it('signale un PDF indisponible sans dompdf', function () {
    Sanctum::actingAs($this->agent);

    $this->getJson("/api/payments/{$this->payment->id}/receipt?format=pdf")
        ->assertStatus(422)
        ->assertJsonPath('error', 'pdf_unavailable');
})->skip(fn () => class_exists(\Barryvdh\DomPDF\Facade\Pdf::class), 'dompdf installé');

it('refuse un format de reçu inconnu', function () {
    Sanctum::actingAs($this->agent);

    $this->getJson("/api/payments/{$this->payment->id}/receipt?format=html")->assertUnprocessable();
});
