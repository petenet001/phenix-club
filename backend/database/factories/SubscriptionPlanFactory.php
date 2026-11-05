<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SubscriptionPlan> */
class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        return [
            'name' => 'Formule '.fake()->unique()->word(),
            'duration_days' => 30,
            'price' => 15000,
            'sessions_count' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function withSessions(int $count): static
    {
        return $this->state(['sessions_count' => $count]);
    }
}
