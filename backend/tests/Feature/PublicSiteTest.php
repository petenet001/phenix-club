<?php

use App\Models\Setting;
use App\Models\SubscriptionPlan;
use Database\Seeders\SettingSeeder;

beforeEach(fn () => $this->seed(SettingSeeder::class));

it('expose les formules actives et les coordonnées du club sans authentification', function () {
    SubscriptionPlan::factory()->create(['name' => 'Mensuel', 'price' => 15000, 'is_active' => true]);
    SubscriptionPlan::factory()->create(['name' => 'Ancienne', 'is_active' => false]);

    $this->getJson('/api/public/club')
        ->assertOk()
        ->assertJsonPath('club.name', 'Phenix Club')
        ->assertJsonCount(1, 'plans')
        ->assertJsonPath('plans.0.name', 'Mensuel')
        ->assertJsonPath('registration_fee', 5000)
        ->assertJsonPath('online_registration_enabled', false)
        ->assertJsonMissingPath('plans.0.subscriptions_count');
});

it('indique quand les inscriptions en ligne sont ouvertes', function () {
    Setting::where('key', 'online_registration_enabled')->update(['value' => '1']);
    \Illuminate\Support\Facades\Cache::flush();

    $this->getJson('/api/public/club')->assertJsonPath('online_registration_enabled', true);
});

it('autorise le site vitrine en CORS', function () {
    $this->withHeaders(['Origin' => 'http://localhost:8080'])
        ->getJson('/api/public/club')
        ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:8080');
});
