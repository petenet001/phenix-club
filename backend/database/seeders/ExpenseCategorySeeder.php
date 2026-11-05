<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Loyer', 'sort_order' => 1],
            ['name' => 'Salaires', 'sort_order' => 2],
            ['name' => 'Électricité', 'sort_order' => 3],
            ['name' => 'Eau', 'sort_order' => 4],
            ['name' => 'Matériel', 'sort_order' => 5],
            ['name' => 'Entretien', 'sort_order' => 6],
            ['name' => 'Communication', 'sort_order' => 7],
            ['name' => 'Autre', 'sort_order' => 99],
        ];

        foreach ($categories as $category) {
            ExpenseCategory::updateOrCreate(['name' => $category['name']], $category + ['is_active' => true]);
        }
    }
}
