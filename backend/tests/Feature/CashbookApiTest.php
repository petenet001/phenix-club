<?php

use App\Enums\UserRole;
use App\Models\ExpenseCategory;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\ExpenseCategorySeeder;
use Database\Seeders\SettingSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->seed([SettingSeeder::class, ExpenseCategorySeeder::class]);
    $this->agent = User::factory()->create(['role' => UserRole::Agent]);
    $this->manager = User::factory()->create(['role' => UserRole::Manager]);
    $this->plan = SubscriptionPlan::factory()->create(['price' => 15000]);
    $this->member = Member::factory()->create();
    $this->subscription = Subscription::factory()->for($this->member)->for($this->plan, 'plan')->create(['price_at_subscription' => 15000]);
    $this->rent = ExpenseCategory::where('name', 'Loyer')->first();
    Sanctum::actingAs($this->agent);
});

it('enregistre une dépense et refuse l\'annulation à un agent (RG-CAI-02)', function () {
    $expense = $this->postJson('/api/expenses', [
        'expense_category_id' => $this->rent->id, 'label' => 'Loyer novembre', 'amount' => 150000, 'method' => 'cash', 'spent_on' => now()->toDateString(),
    ])->assertCreated()->assertJsonPath('data.category.name', 'Loyer')->json('data');

    $this->patchJson("/api/expenses/{$expense['id']}/cancel", ['reason' => 'Doublon'])->assertForbidden();

    Sanctum::actingAs($this->manager);
    $this->patchJson("/api/expenses/{$expense['id']}/cancel", ['reason' => 'Doublon'])->assertOk()->assertJsonPath('data.cancel_reason', 'Doublon');
    $this->getJson('/api/expenses')->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('meta.totals.total', 0);
});

it('calcule la caisse : entrées - sorties = solde, et les manquants (RG-CAI-03, 04)', function () {
    $this->postJson('/api/payments', ['member_id' => $this->member->id, 'subscription_id' => $this->subscription->id, 'amount' => 10000, 'method' => 'cash'])->assertCreated();
    $this->postJson('/api/expenses', ['expense_category_id' => $this->rent->id, 'label' => 'Loyer', 'amount' => 4000, 'method' => 'cash', 'spent_on' => now()->toDateString()])->assertCreated();

    $this->getJson('/api/cashbook')
        ->assertOk()
        ->assertJsonPath('income.total', 10000)
        ->assertJsonPath('expenses.total', 4000)
        ->assertJsonPath('expenses.by_category.Loyer', 4000)
        ->assertJsonPath('balance', 6000)
        ->assertJsonPath('outstanding.expected', 15000)
        ->assertJsonPath('outstanding.collected', 10000)
        ->assertJsonPath('outstanding.missing', 5000)
        ->assertJsonPath('outstanding.unpaid_subscriptions', 1);

    $this->getJson('/api/cashbook/export')->assertForbidden();
    Sanctum::actingAs($this->manager);
    $this->get('/api/cashbook/export')->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

it('empêche la suppression d\'une catégorie utilisée (RG-CAI-05)', function () {
    $this->postJson('/api/expenses', ['expense_category_id' => $this->rent->id, 'label' => 'Loyer', 'amount' => 1000, 'method' => 'cash', 'spent_on' => now()->toDateString()])->assertCreated();

    Sanctum::actingAs($this->manager);
    $this->deleteJson("/api/expense-categories/{$this->rent->id}")->assertStatus(422)->assertJsonPath('error', 'category_in_use');
});

it('renvoie le tableau de bord au gérant uniquement', function () {
    $this->getJson('/api/dashboard')->assertForbidden();

    Sanctum::actingAs($this->manager);
    $this->getJson('/api/dashboard')
        ->assertOk()
        ->assertJsonStructure(['members' => ['active', 'pending', 'new_this_month'], 'attendance' => ['today', 'last_30_days'], 'revenue' => ['this_month', 'by_method', 'balance_this_month'], 'late_payments' => ['count', 'amount']]);
});

it('gère les paramètres et les utilisateurs (gérant)', function () {
    $this->putJson('/api/settings', ['checkin_tolerance_days' => 5])->assertForbidden();

    Sanctum::actingAs($this->manager);
    $this->putJson('/api/settings', ['checkin_tolerance_days' => 5, 'registration_fee' => 7000])->assertOk()->assertJsonPath('data.checkin_tolerance_days', 5)->assertJsonPath('data.registration_fee', 7000);
    $this->getJson('/api/settings')->assertOk()->assertJsonPath('data.registration_fee', 7000);

    $user = $this->postJson('/api/users', ['name' => 'Coach B', 'email' => 'coachb@phenix.cg', 'password' => 'password123', 'role' => 'coach'])->assertCreated()->json('data');
    $this->deleteJson("/api/users/{$this->manager->id}")->assertStatus(422)->assertJsonPath('error', 'cannot_deactivate_self');
    $this->deleteJson("/api/users/{$user['id']}")->assertNoContent();
    expect(User::find($user['id'])->is_active)->toBeFalse();
});
