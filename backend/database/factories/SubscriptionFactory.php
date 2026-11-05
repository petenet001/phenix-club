<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Member;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subscription> */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $start = now()->startOfDay();

        return [
            'member_id' => Member::factory(),
            'subscription_plan_id' => SubscriptionPlan::factory(),
            'start_date' => $start->toDateString(),
            'end_date' => $start->copy()->addDays(29)->toDateString(),
            'price_at_subscription' => 15000,
            'sessions_used' => 0,
            'status' => SubscriptionStatus::Active,
        ];
    }

    public function expiredDaysAgo(int $days): static
    {
        return $this->state(fn () => [
            'start_date' => now()->subDays($days + 29)->toDateString(),
            'end_date' => now()->subDays($days)->toDateString(),
            'status' => SubscriptionStatus::Expired,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'status' => SubscriptionStatus::Pending,
        ]);
    }
}
