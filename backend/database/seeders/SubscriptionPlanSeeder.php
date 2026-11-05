<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Tarifs indicatifs à confirmer avec le gérant.
     * Modifiables ensuite depuis l'écran Paramètres > Formules.
     */
    public function run(): void
    {
        $plans = [
            ['name' => 'Séance unique', 'duration_days' => 1, 'price' => 2000, 'sessions_count' => 1, 'sort_order' => 1, 'description' => 'Accès pour une journée'],
            ['name' => 'Hebdomadaire', 'duration_days' => 7, 'price' => 7000, 'sessions_count' => null, 'sort_order' => 2, 'description' => '7 jours en illimité'],
            ['name' => 'Mensuel', 'duration_days' => 30, 'price' => 15000, 'sessions_count' => null, 'sort_order' => 3, 'description' => '30 jours en illimité'],
            ['name' => 'Trimestriel', 'duration_days' => 90, 'price' => 40000, 'sessions_count' => null, 'sort_order' => 4, 'description' => '90 jours en illimité'],
            ['name' => 'Carte 10 séances', 'duration_days' => 60, 'price' => 12000, 'sessions_count' => 10, 'sort_order' => 5, 'description' => '10 séances à utiliser sous 60 jours'],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(['name' => $plan['name']], $plan + ['is_active' => true]);
        }
    }
}
