<?php

namespace Database\Factories;

use App\Models\Assinatura;
use App\Models\Plano;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assinatura>
 */
class AssinaturaFactory extends Factory
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
            'plano_id' => Plano::factory(),
            'nome_plano' => fake()->words(3, true),
            'valor_referencia' => fake()->randomFloat(2, 0, 1000),
            'duracao_dias' => 30,
            'inicio_em' => now()->subDay(),
            'termino_em' => now()->addDays(29),
            'cancelada_em' => null,
            'observacao' => fake()->optional()->sentence(),
        ];
    }

    public function vigente(): static
    {
        return $this->state(fn (array $attributes) => [
            'inicio_em' => now()->subDay(),
            'termino_em' => now()->addDays(29),
            'cancelada_em' => null,
        ]);
    }

    public function futura(): static
    {
        return $this->state(fn (array $attributes) => [
            'inicio_em' => now()->addDay(),
            'termino_em' => now()->addDays(31),
            'cancelada_em' => null,
        ]);
    }

    public function vencida(): static
    {
        return $this->state(fn (array $attributes) => [
            'inicio_em' => now()->subDays(60),
            'termino_em' => now()->subDays(30),
            'cancelada_em' => null,
        ]);
    }

    public function cancelada(): static
    {
        return $this->state(fn (array $attributes) => [
            'cancelada_em' => now(),
        ]);
    }
}
