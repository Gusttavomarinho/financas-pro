<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'description' => $this->faker->sentence(3),
            'value' => $this->faker->randomFloat(2, 10, 1000),
            'date' => $this->faker->date(),
            'type' => 'despesa', // Default to expense
            'user_id' => 1,
            'status' => 'confirmada', // Changed from efetivado to confirmada
        ];
    }
}
