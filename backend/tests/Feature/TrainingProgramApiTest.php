<?php

use App\Enums\UserRole;
use App\Models\Member;
use App\Models\TrainingProgram;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(SettingSeeder::class);
    $this->agent = User::factory()->create(['role' => UserRole::Agent]);
    $this->coach = User::factory()->create(['role' => UserRole::Coach]);
    $this->manager = User::factory()->create(['role' => UserRole::Manager]);
    $this->member = Member::factory()->active()->create();
    $this->payload = [
        'kind' => 'training',
        'title' => 'Remise en forme',
        'content' => ['goal' => 'Perte de poids', 'days' => ['lundi', 'mercredi', 'vendredi'], 'sessions' => [['day' => 'lundi', 'exercises' => [['name' => 'Squat', 'sets' => 4, 'reps' => '12']]]]],
    ];
});

it('interdit la création de programme à un agent (RG-PRG-05)', function () {
    Sanctum::actingAs($this->agent);
    $this->postJson("/api/members/{$this->member->id}/programs", $this->payload)->assertForbidden();
});

it('permet au coach d\'imposer un programme, qui archive le précédent du même type (RG-PRG-04)', function () {
    Sanctum::actingAs($this->coach);

    $first = $this->postJson("/api/members/{$this->member->id}/programs", $this->payload + ['impose' => true])
        ->assertCreated()->assertJsonPath('data.status', 'imposed')->assertJsonPath('data.is_effective', true)->json('data');

    $this->postJson("/api/members/{$this->member->id}/programs", $this->payload + ['impose' => true, 'title' => 'Nouveau'])->assertCreated();

    expect(TrainingProgram::find($first['id'])->status->value)->toBe('archived')
        ->and($this->member->trainingPrograms()->effective()->count())->toBe(1);
});

it('valide ou refuse une proposition, refus avec motif obligatoire', function () {
    Sanctum::actingAs($this->coach);
    $proposed = $this->postJson("/api/members/{$this->member->id}/programs", $this->payload)->assertCreated()->assertJsonPath('data.status', 'proposed')->json('data');

    Sanctum::actingAs($this->manager);
    $this->getJson('/api/programs?status=proposed')->assertOk()->assertJsonCount(1, 'data');
    $this->patchJson("/api/programs/{$proposed['id']}/decline", [])->assertStatus(422);
    $this->patchJson("/api/programs/{$proposed['id']}/decline", ['note' => 'Trop intense pour un débutant'])->assertOk()->assertJsonPath('data.status', 'declined');
    $this->patchJson("/api/programs/{$proposed['id']}/approve")->assertStatus(422)->assertJsonPath('error', 'program_not_proposed');

    Sanctum::actingAs($this->coach);
    $second = $this->postJson("/api/members/{$this->member->id}/programs", $this->payload)->json('data');
    Sanctum::actingAs($this->manager);
    $this->patchJson("/api/programs/{$second['id']}/approve", ['note' => 'OK'])->assertOk()->assertJsonPath('data.status', 'approved')->assertJsonPath('data.reviewed_by.id', $this->manager->id);
});

it('calcule l\'assiduité par rapport aux jours prévus (RG-PRG-06)', function () {
    Sanctum::actingAs($this->coach);
    $this->postJson("/api/members/{$this->member->id}/programs", $this->payload + ['impose' => true])->assertCreated();

    $this->member->attendances()->create(['attended_on' => now()->toDateString(), 'checked_in_at' => now(), 'method' => 'manual']);

    Sanctum::actingAs($this->agent);
    $response = $this->getJson("/api/members/{$this->member->id}/programs")->assertOk()->assertJsonCount(1, 'data');
    expect($response->json('meta.adherence.planned_days'))->toBe(['lundi', 'mercredi', 'vendredi'])
        ->and($response->json('meta.adherence.attended_sessions_4w'))->toBe(1)
        ->and($response->json('meta.adherence.planned_sessions_4w'))->toBeGreaterThan(10);
});
