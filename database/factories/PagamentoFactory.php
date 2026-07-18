<?php

namespace Database\Factories;

use App\Models\Assinatura;
use App\Models\Pagamento;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pagamento>
 */
class PagamentoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'assinatura_id' => fn (array $attributes) => Assinatura::factory()->create([
                'user_id' => $attributes['user_id'],
            ])->getKey(),
            'valor' => fake()->randomFloat(2, 0, 1000),
            'data_pagamento' => now(),
            'tipo' => fake()->randomElement(Pagamento::TIPOS),
            'forma_pagamento' => fake()->randomElement(Pagamento::FORMAS_PAGAMENTO),
            'comprovante' => null,
            'registrado_por' => User::factory()->state([
                'perfil' => 'analista',
            ]),
            'observacao' => fake()->optional()->sentence(),
            'gateway' => null,
            'gateway_payment_id' => null,
            'dados_gateway' => null,
        ];
    }
}
