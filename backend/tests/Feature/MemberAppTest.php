<?php

use App\Enums\MemberStatus;
use App\Enums\UserRole;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\KioskTokenService;
use Database\Seeders\SettingSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed(SettingSeeder::class);
    $this->agent = User::factory()->create(['role' => UserRole::Agent]);
    $this->plan = SubscriptionPlan::factory()->create(['name' => 'Mensuel', 'duration_days' => 30, 'price' => 15000]);
    $this->member = Member::factory()->create(['phone' => '+242 06 100 0001', 'status' => MemberStatus::Active, 'validated_at' => now()]);
    $this->member->forceFill(['app_password' => '123456'])->save();
    $this->subscription = Subscription::factory()->for($this->member)->for($this->plan, 'plan')->create([
        'start_date' => now()->subDays(5)->toDateString(),
        'end_date' => now()->addDays(24)->toDateString(),
        'price_at_subscription' => 15000,
        'status' => 'active',
    ]);
});

function memberPayment(Member $member, ?Subscription $subscription, int $amount, string $number): Payment
{
    return Payment::create([
        'receipt_number' => $number, 'member_id' => $member->id, 'subscription_id' => $subscription?->id,
        'amount' => $amount, 'method' => 'cash', 'paid_at' => now(),
        'period_start' => $subscription?->start_date, 'period_end' => $subscription?->end_date,
    ]);
}

it('connecte un adhérent avec son téléphone, quel que soit le format, et son code', function () {
    $this->postJson('/api/member/login', ['phone' => '061000001', 'password' => '123456'])
        ->assertOk()
        ->assertJsonStructure(['token', 'member' => ['code', 'full_name', 'qr_token']]);

    $this->postJson('/api/member/login', ['phone' => '+242061000001', 'password' => 'mauvais'])->assertUnprocessable();

    expect($this->member->fresh()->app_last_login_at)->not->toBeNull();
});

it('refuse la connexion sans accès activé', function () {
    $this->member->forceFill(['app_password' => null])->save();

    $this->postJson('/api/member/login', ['phone' => '061000001', 'password' => '123456'])->assertUnprocessable();
});

it('cloisonne les tokens adhérent et personnel', function () {
    Sanctum::actingAs($this->member);
    $this->getJson('/api/members')->assertForbidden();
    $this->getJson('/api/me')->assertForbidden();
    $this->getJson('/api/kiosk/token')->assertForbidden();

    Sanctum::actingAs($this->agent);
    $this->getJson('/api/member/me')->assertForbidden();
});

it('résume abonnement, accès du jour et solde sur l\'écran d\'accueil', function () {
    memberPayment($this->member, $this->subscription, 10000, 'RC-2026-000001');
    Sanctum::actingAs($this->member);

    $this->getJson('/api/member/me')
        ->assertOk()
        ->assertJsonPath('data.subscription.plan', 'Mensuel')
        ->assertJsonPath('data.subscription.days_left', 25)
        ->assertJsonPath('data.subscription.balance_due', 5000)
        ->assertJsonPath('data.access.allowed', true)
        ->assertJsonPath('data.balance.is_late', false)
        ->assertJsonPath('data.club.name', 'Phenix Club');

    $this->getJson('/api/member/balance')
        ->assertOk()
        ->assertJsonPath('data.items.1.label', 'Solde Mensuel')
        ->assertJsonPath('data.items.1.amount', 5000);
});

it('ne montre à l\'adhérent que ses propres paiements et reçus', function () {
    $mine = memberPayment($this->member, $this->subscription, 15000, 'RC-2026-000001');
    $other = memberPayment(Member::factory()->create(), null, 5000, 'RC-2026-000002');
    Sanctum::actingAs($this->member);

    $this->getJson('/api/member/payments')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.receipt_number', 'RC-2026-000001');
    $this->getJson("/api/member/payments/{$mine->id}/receipt")->assertOk()->assertJsonPath('data.amount_in_words', 'quinze mille');
    $this->getJson("/api/member/payments/{$other->id}/receipt")->assertNotFound();
});

it('pointe en scannant le QR de la borne, une seule fois par jour', function () {
    Sanctum::actingAs($this->member);
    $code = app(KioskTokenService::class)->current()['payload'];

    $this->postJson('/api/member/check-in', ['code' => $code])
        ->assertCreated()
        ->assertJsonPath('data.plan', 'Mensuel');

    $this->assertDatabaseHas('attendances', ['member_id' => $this->member->id, 'method' => 'app', 'recorded_by' => null]);

    $this->postJson('/api/member/check-in', ['code' => $code])
        ->assertUnprocessable()
        ->assertJsonPath('error', 'already_checked_in_today');
});

it('refuse un QR expiré ou falsifié', function () {
    Sanctum::actingAs($this->member);
    $kiosk = app(KioskTokenService::class);

    $old = $kiosk->current(time() - 3 * KioskTokenService::PERIOD)['payload'];
    $this->postJson('/api/member/check-in', ['code' => $old])->assertUnprocessable()->assertJsonPath('error', 'invalid_checkin_code');

    $this->postJson('/api/member/check-in', ['code' => 'PHENIX-CHECKIN:123.'.str_repeat('a', 32)])->assertJsonPath('error', 'invalid_checkin_code');

    // Le code de la période précédente reste valable (délai de scan)
    expect($kiosk->isValid($kiosk->current(time() - KioskTokenService::PERIOD)['payload']))->toBeTrue();
});

it('applique les règles de pointage : abonnement expiré refusé, sans forçage possible', function () {
    $this->subscription->update(['start_date' => now()->subDays(40)->toDateString(), 'end_date' => now()->subDays(10)->toDateString(), 'status' => 'expired']);
    Sanctum::actingAs($this->member);

    $this->postJson('/api/member/check-in', ['code' => app(KioskTokenService::class)->current()['payload']])
        ->assertUnprocessable()
        ->assertJsonPath('error', 'subscription_expired');
});

it('génère un code d\'accès depuis l\'accueil et le retire', function () {
    Sanctum::actingAs($this->agent);

    $code = $this->postJson("/api/members/{$this->member->id}/app-access")->assertCreated()->json('code');
    expect($code)->toMatch('/^\d{6}$/');

    $this->postJson('/api/member/login', ['phone' => '061000001', 'password' => $code])->assertOk();

    $this->deleteJson("/api/members/{$this->member->id}/app-access")->assertNoContent();
    expect($this->member->fresh()->tokens()->count())->toBe(0);
    $this->postJson('/api/member/login', ['phone' => '061000001', 'password' => $code])->assertUnprocessable();
});

it('refuse un token dont l\'accès a été retiré', function () {
    $token = $this->member->createToken('test')->plainTextToken;
    $this->member->forceFill(['app_password' => null])->save();

    $this->withToken($token)->getJson('/api/member/me')->assertUnauthorized();
});

it('change le code d\'accès et déconnecte les autres appareils', function () {
    $other = $this->member->createToken('autre')->plainTextToken;
    $current = $this->member->createToken('courant')->plainTextToken;

    $this->withToken($current)->putJson('/api/member/password', ['current_password' => '123456', 'password' => '654321', 'password_confirmation' => '654321'])->assertOk();

    expect($this->member->tokens()->count())->toBe(1);
    $this->postJson('/api/member/login', ['phone' => '061000001', 'password' => '654321'])->assertOk();
});

it('ne montre pas les notes internes du coach', function () {
    $this->member->trainingPrograms()->create(['kind' => 'training', 'title' => 'Force', 'status' => 'imposed', 'content' => ['goal' => 'x'], 'notes' => 'Note interne']);
    $this->member->trainingPrograms()->create(['kind' => 'training', 'title' => 'Ancien', 'status' => 'archived', 'content' => []]);
    Sanctum::actingAs($this->member);

    $this->getJson('/api/member/programs')->assertOk()->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.notes');
});

it('donne l\'adresse de la photo sur l\'hôte utilisé par le téléphone, pas sur APP_URL', function () {
    $this->member->update(['photo_path' => 'members/photo.jpg']);
    Sanctum::actingAs($this->member);

    $this->getJson('http://192.168.1.20:8000/api/member/me')
        ->assertOk()
        ->assertJsonPath('data.profile.photo_url', 'http://192.168.1.20:8000/storage/members/photo.jpg')
        ->assertJsonPath('data.club.logo_url', 'http://192.168.1.20:8000/images/logo-phenix.png');
});
