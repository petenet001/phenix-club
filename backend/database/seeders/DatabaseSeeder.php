<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            UserSeeder::class,
            SubscriptionPlanSeeder::class,
            ExpenseCategorySeeder::class,
        ]);

        if (app()->environment(['local', 'testing'])) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
