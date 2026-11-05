<?php

use App\Enums\UserRole;
use App\Models\Member;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(SettingSeeder::class);
    $this->agent = User::factory()->create(['role' => UserRole::Agent]);
    $this->manager = User::factory()->create(['role' => UserRole::Manager]);
});

it('refuse l\'accès sans token', function () {
    $this->getJson('/api/members')->assertStatus(401)->assertJson(['error' => 'unauthenticated']);
});

it('crée un adhérent avec un code et un QR token générés', function () {
    Sanctum::actingAs($this->agent);

    $response = $this->postJson('/api/members', [
        'last_name' => 'Mbemba',
        'first_name' => 'Alain',
        'phone' => '+242 06 123 45 67',
        'gender' => 'M',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.code', 'PHX-000001')
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.registration_source', 'on_site')
        ->assertJsonPath('data.validated_at', null)
        ->assertJsonPath('data.phone', '+242061234567')
        ->assertJsonPath('data.is_late', false);

    expect($response->json('data.qr_token'))->toBeString()->toHaveLength(36);
});

it('incrémente le code adhérent', function () {
    Sanctum::actingAs($this->agent);
    Member::factory()->count(2)->create();

    $this->postJson('/api/members', ['last_name' => 'A', 'first_name' => 'B', 'phone' => '+242060000099'])
        ->assertCreated()
        ->assertJsonPath('data.code', 'PHX-000003');
});

it('refuse un téléphone déjà utilisé', function () {
    Sanctum::actingAs($this->agent);
    Member::factory()->create(['phone' => '+242061111111']);

    $this->postJson('/api/members', ['last_name' => 'A', 'first_name' => 'B', 'phone' => '+242061111111'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['phone']);
});

it('recherche par nom, prénom ou code sans tenir compte de la casse', function () {
    Sanctum::actingAs($this->agent);
    Member::factory()->create(['last_name' => 'Okemba', 'first_name' => 'Dieudonné', 'code' => 'PHX-000010']);
    Member::factory()->create(['last_name' => 'Samba', 'first_name' => 'Prisca', 'code' => 'PHX-000011']);

    $this->getJson('/api/members?search=okemba')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/members?search=dieudonné okemba')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/members?search=000011')->assertOk()->assertJsonPath('data.0.last_name', 'Samba');
});

it('retrouve un adhérent par QR token', function () {
    Sanctum::actingAs($this->agent);
    $member = Member::factory()->create();

    $this->getJson('/api/members/by-qr/'.$member->qr_token)->assertOk()->assertJsonPath('data.id', $member->id);
    $this->getJson('/api/members/by-qr/00000000-0000-0000-0000-000000000000')->assertNotFound();
});

it('interdit la suppression et la suspension à un agent', function () {
    Sanctum::actingAs($this->agent);
    $member = Member::factory()->create();

    $this->deleteJson("/api/members/{$member->id}")->assertForbidden();
    $this->patchJson("/api/members/{$member->id}/status", ['status' => 'suspended'])->assertForbidden();
});

it('valide une demande d\'adhésion manuellement (RG-ADH-06)', function () {
    Sanctum::actingAs($this->agent);
    $member = Member::factory()->pending()->create();

    $this->postJson("/api/members/{$member->id}/validate")
        ->assertOk()
        ->assertJsonPath('data.status', 'inactive');

    expect($member->fresh()->validated_at)->not->toBeNull()
        ->and($member->fresh()->validated_by)->toBe($this->agent->id);

    // idempotent
    $this->postJson("/api/members/{$member->id}/validate")->assertOk();
});

it('refuse la validation d\'un adhérent suspendu', function () {
    Sanctum::actingAs($this->agent);
    $member = Member::factory()->suspended()->create();

    $this->postJson("/api/members/{$member->id}/validate")
        ->assertStatus(422)
        ->assertJsonPath('error', 'member_suspended');
});

it('filtre les demandes en attente et par source', function () {
    Sanctum::actingAs($this->agent);
    Member::factory()->pending()->create();
    Member::factory()->pending()->online()->create();
    Member::factory()->create();

    $this->getJson('/api/members?status=pending')->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/api/members?status=pending&source=online')->assertOk()->assertJsonCount(1, 'data');
});

it('ne signale jamais de retard pour une demande en attente', function () {
    Sanctum::actingAs($this->agent);
    $member = Member::factory()->pending()->create();

    $this->getJson("/api/members/{$member->id}")->assertJsonPath('data.is_late', false);
});

it('refuse l\'inscription en ligne quand le paramètre est désactivé', function () {
    $this->postJson('/api/public/registrations', ['last_name' => 'Web', 'first_name' => 'Test', 'phone' => '+242061000099'])
        ->assertStatus(422)
        ->assertJsonPath('error', 'online_registration_disabled');
});

it('enregistre une demande d\'adhésion depuis le site web', function () {
    Setting::set('online_registration_enabled', true);

    $this->postJson('/api/public/registrations', ['last_name' => 'Web', 'first_name' => 'Test', 'phone' => '+242 06 100 00 99'])
        ->assertCreated()
        ->assertJsonPath('status', 'pending')
        ->assertJsonMissingPath('qr_token');

    $member = Member::where('phone', '+242061000099')->first();
    expect($member->registration_source->value)->toBe('online')
        ->and($member->status->value)->toBe('pending');

    // pot de miel rempli = bot
    $this->postJson('/api/public/registrations', ['last_name' => 'Bot', 'first_name' => 'X', 'phone' => '+242061000098', 'website' => 'http://spam'])
        ->assertStatus(422);
});

it('permet au gérant de suspendre puis réactiver un adhérent sans abonnement', function () {
    Sanctum::actingAs($this->manager);
    $member = Member::factory()->create();

    $this->patchJson("/api/members/{$member->id}/status", ['status' => 'suspended', 'reason' => 'Comportement'])
        ->assertOk()
        ->assertJsonPath('data.status', 'suspended');

    expect($member->fresh()->notes)->toContain('Comportement');

    $this->patchJson("/api/members/{$member->id}/status", ['status' => 'active'])
        ->assertOk()
        ->assertJsonPath('data.status', 'inactive');
});

it('supprime logiquement un adhérent', function () {
    Sanctum::actingAs($this->manager);
    $member = Member::factory()->create();

    $this->deleteJson("/api/members/{$member->id}")->assertNoContent();

    $this->assertSoftDeleted('members', ['id' => $member->id]);
    $this->getJson("/api/members/{$member->id}")->assertNotFound();
});
