<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PayComponent;

class PayComponentSeeder extends Seeder
{
    public function run(): void
    {
        // Basic salary
        PayComponent::updateOrCreate(
            ['code' => 'BASIC'],
            ['name' => 'Basic Salary', 'kind' => 'earning', 'calc_type' => 'fixed', 'default_amount' => 0, 'active' => true]
        );

        // Overtime
        PayComponent::updateOrCreate(
            ['code' => 'OT'],
            ['name' => 'Overtime', 'kind' => 'earning', 'calc_type' => 'hourly', 'default_amount' => null, 'active' => true]
        );

        // Meal allowance
        PayComponent::updateOrCreate(
            ['code' => 'MEAL'],
            ['name' => 'Meal Allowance', 'kind' => 'allowance', 'calc_type' => 'fixed', 'default_amount' => 0, 'active' => true]
        );
    }
}
