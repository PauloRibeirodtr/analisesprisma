<?php

namespace Database\Factories;

use App\Models\Plano;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plano>
 */
class PlanoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->words(3, true),
            'slug' => fake()->unique()->slug(3),
            'descricao' => fake()->optional()->sentence(),
            'valor_padrao' => fake()->randomFloat(2, 0, 1000),
            'duracao_dias' => fake()->numberBetween(1, 365),
            'disponivel_para_venda' => true,
        ];
    }
}
