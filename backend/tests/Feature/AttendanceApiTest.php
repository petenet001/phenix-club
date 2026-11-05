<?php

use App\Enums\UserRole;
use App\Models\Member;
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
    $this->plan = SubscriptionPlan::factory()->create(['duration_days' => 30, 'price' => 15000]);
    $this->member = Member::factory()->active()->create();
    Sanctum::actingAs($this->agent);
});

it('pointe un adhérent avec un abonnement valide', function () {
    Subscription::factory()->for($this->member)->for($this->plan, 'plan')->create();

    $this->postJson('/api/attendances', ['member_id' => $this->member->id])
        ->assertCreated()
        ->assertJsonPath('warning', null)
        ->assertJsonPath('data.method', 'manual')
        ->assertJsonPath('data.member.id', $this->member->id);
});

it('pointe par QR code', function () {
    Subscription::factory()->for($this->member)->for($this->plan, 'plan')->create();

    $this->postJson('/api/attendances', ['qr_token' => $this->member->qr_token])
        ->assertCreated()
        ->assertJsonPath('data.method', 'qr');
});

it('refuse un second pointage le même jour (RG-PTG-03)', function () {
    Subscription::factory()->for($this->member)->for($this->plan, 'plan')->create();

    $this->postJson('/api/attendances', ['member_id' => $this->member->id])->assertCreated();
    $this->postJson('/api/attendances', ['member_id' => $this->member->id])
        ->assertStatus(422)
        ->assertJsonPath('error', 'already_checked_in_today')
        ->assertJsonPath('details.forceable', false);
});

it('accepte le jour exact de l\'échéance', function () {
    Subscription::factory()->for($this->member)->for($this->plan, 'plan')->create([
        'start_date' => now()->subDays(29)->toDateString(),
        'end_date' => now()->toDateString(),
    ]);

    $this->postJson('/api/attendances', ['member_id' => $this->member->id])->assertCreated()->assertJsonPath('warning', null);
});

it('accepte avec avertissement dans la fenêtre de tolérance (RG-PTG-02)', function () {
    Subscription::factory()->for($this->member)->for($this->plan, 'plan')->expiredDaysAgo(2)->create();

    $response = $this->postJson('/api/attendances', ['member_id' => $this->member->id])->assertCreated();
    expect($response->json('warning'))->toContain('expiré depuis 2 jour');
});

it('refuse le lendemain de la fin de tolérance', function () {
    Subscription::factory()->for($this->member)->for($this->plan, 'plan')->expiredDaysAgo(4)->create();

    $this->postJson('/api/attendances', ['member_id' => $this->member->id])
        ->assertStatus(422)
        ->assertJsonPath('error', 'subscription_expired')
        ->assertJsonPath('details.forceable', true);
});

it('applique immédiatement un changement de tolérance', function () {
    Subscription::factory()->for($this->member)->for($this->plan, 'plan')->expiredDaysAgo(4)->create();
    Setting::set('checkin_tolerance_days', 7);

    $this->postJson('/api/attendances', ['member_id' => $this->member->id])->assertCreated();
});

it('refuse un abonnement qui démarre demain', function () {
    Subscription::factory()->for($this->member)->for($this->plan, 'plan')->pending()->create();

    $this->postJson('/api/attendances', ['member_id' => $this->member->id])
        ->assertStatus(422)
        ->assertJsonPath('error', 'subscription_not_started');
});

it('refuse un adhérent suspendu même avec abonnement valide', function () {
    $suspended = Member::factory()->suspended()->create();
    Subscription::factory()->for($suspended)->for($this->plan, 'plan')->create();

    $this->postJson('/api/attendances', ['member_id' => $suspended->id])
        ->assertStatus(422)
        ->assertJsonPath('error', 'member_suspended');
});

it('refuse une adhésion en attente de validation (RG-ADH-05)', function () {
    $pending = Member::factory()->pending()->create();
    Subscription::factory()->for($pending)->for($this->plan, 'plan')->create();

    $this->postJson('/api/attendances', ['member_id' => $pending->id])
        ->assertStatus(422)
        ->assertJsonPath('error', 'membership_pending')
        ->assertJsonPath('details.forceable', false);
});

it('consomme les séances d\'une carte et refuse quand elles sont épuisées (RG-PTG-05)', function () {
    $card = SubscriptionPlan::factory()->withSessions(2)->create(['duration_days' => 60]);
    $sub = Subscription::factory()->for($this->member)->for($card, 'plan')->create(['sessions_used' => 1]);

    $this->postJson('/api/attendances', ['member_id' => $this->member->id])->assertCreated()->assertJsonPath('data.subscription.sessions_used', 2);
    expect($sub->fresh()->sessions_used)->toBe(2);

    $this->travel(1)->days();
    $this->postJson('/api/attendances', ['member_id' => $this->member->id])
        ->assertStatus(422)
        ->assertJsonPath('error', 'sessions_exhausted');
});

it('ne permet qu\'au gérant de forcer un pointage refusé, avec motif (RG-PTG-06)', function () {
    $this->postJson('/api/attendances', ['member_id' => $this->member->id, 'force' => true, 'force_reason' => 'Invité du gérant'])
        ->assertStatus(403)
        ->assertJsonPath('error', 'force_requires_manager');

    Sanctum::actingAs($this->manager);
    $this->postJson('/api/attendances', ['member_id' => $this->member->id, 'force' => true])
        ->assertStatus(422);

    $this->postJson('/api/attendances', ['member_id' => $this->member->id, 'force' => true, 'force_reason' => 'Invité du gérant'])
        ->assertCreated()
        ->assertJsonPath('data.forced', true);

    $this->getJson('/api/attendances?forced=1')->assertOk()->assertJsonCount(1, 'data');
});

it('prévisualise le pointage sans l\'enregistrer', function () {
    $this->getJson('/api/attendances/check?member_id='.$this->member->id)
        ->assertOk()
        ->assertJsonPath('allowed', false)
        ->assertJsonPath('error', 'no_valid_subscription')
        ->assertJsonPath('member.id', $this->member->id);

    expect($this->member->attendances()->count())->toBe(0);
});

it('liste les présences du jour', function () {
    Subscription::factory()->for($this->member)->for($this->plan, 'plan')->create();
    $this->postJson('/api/attendances', ['member_id' => $this->member->id])->assertCreated();

    $this->getJson('/api/attendances')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/attendances?date='.now()->subDay()->toDateString())->assertOk()->assertJsonCount(0, 'data');
    $this->getJson("/api/members/{$this->member->id}/attendances")->assertOk()->assertJsonCount(1, 'data');
});
