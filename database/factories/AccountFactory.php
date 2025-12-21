<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(['corrente', 'poupanca', 'investimento']),
            'initial_balance' => $this->faker->randomFloat(2, 0, 10000),
            'color' => $this->faker->hexColor(),
            'user_id' => 1, // Default user
        ];
    }
}
