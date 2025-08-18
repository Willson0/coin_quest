<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paymentMethods = ['sbp', 'tbank', 'sber'];

        return [
            'user'              => $this->faker->userName(),
            'user_avatar'       => $this->faker->imageUrl(100, 100, 'people'),
            'is_safe'           => $this->faker->boolean(70), // 70% надежные
            'remain'            => $this->faker->randomFloat(6, 0.001, 10),
            'payment_method'    => $this->faker->randomElement($paymentMethods),
            'price'             => $this->faker->randomFloat(2, 1000, 50000),
            'currency_id'       => \App\Models\Currency::inRandomOrder()->first()->id ?? 1,
            'fiat_currency_id'  => \App\Models\FiatCurrency::inRandomOrder()->first()->id ?? 1,
        ];
    }
}
