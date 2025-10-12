<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PayComponentFactory extends Factory
{
    public function definition(): array
    {
        $kinds = ['earning','allowance','deduction','reimbursement'];
        $calcs = ['fixed','hourly','percent','formula'];
        return [
            'code' => strtoupper($this->faker->bothify('PC###')),
            'name' => $this->faker->words(2, true),
            'kind' => $this->faker->randomElement($kinds),
            'calc_type' => $this->faker->randomElement($calcs),
            'default_amount' => $this->faker->randomFloat(2, 0, 1000000),
            'active' => true,
        ];
    }
}
