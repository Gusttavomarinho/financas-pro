<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->creditCardType,
            'bank' => $this->faker->company,
            'brand' => $this->faker->randomElement(['visa', 'mastercard', 'amex']),
            'holder_name' => $this->faker->name,
            'valid_thru' => $this->faker->numerify('##/####'),
            'last_4_digits' => $this->faker->numerify('####'),
            'credit_limit' => $this->faker->randomFloat(2, 1000, 50000),
            'closing_day' => $this->faker->numberBetween(1, 10),
            'due_day' => $this->faker->numberBetween(11, 28),
            'color' => $this->faker->hexColor(),
            'user_id' => 1, // Default user
        ];
    }
}
