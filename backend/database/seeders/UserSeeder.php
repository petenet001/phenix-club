<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'gerant@phenixclub.cg'],
            [
                'name' => 'Gérant Phenix',
                'password' => 'password',
                'role' => UserRole::Manager,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'coach@phenixclub.cg'],
            [
                'name' => 'Coach Phenix',
                'password' => 'password',
                'role' => UserRole::Coach,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'accueil@phenixclub.cg'],
            [
                'name' => 'Accueil',
                'password' => 'password',
                'role' => UserRole::Agent,
                'is_active' => true,
            ]
        );
    }
}
