<?php

namespace Database\Factories;

use App\Models\PeriodoRecuperacaoDoubleBranco;
use App\StatusPeriodoRecuperacaoDoubleBranco;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PeriodoRecuperacaoDoubleBranco>
 */
class PeriodoRecuperacaoDoubleBrancoFactory extends Factory
{
    protected $model = PeriodoRecuperacaoDoubleBranco::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $iniciadoEm = now()->subMinute();

        return [
            'status' => StatusPeriodoRecuperacaoDoubleBranco::Ativa,
            'gatilho_resultado_id' => fake()->unique()->numberBetween(1, 999999999),
            'gatilho_idblaze' => fake()->unique()->uuid(),
            'iniciado_em' => $iniciadoEm,
        ];
    }
}
