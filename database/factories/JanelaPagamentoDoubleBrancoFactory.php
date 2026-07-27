<?php

namespace Database\Factories;

use App\Models\JanelaPagamentoDoubleBranco;
use App\StatusJanelaPagamentoDoubleBranco;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JanelaPagamentoDoubleBranco>
 */
class JanelaPagamentoDoubleBrancoFactory extends Factory
{
    protected $model = JanelaPagamentoDoubleBranco::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $inicioEm = now()->addHour()->startOfMinute();

        return [
            'origem_id' => fake()->unique()->numberBetween(1, 999999999),
            'inicio_em' => $inicioEm,
            'fim_em' => $inicioEm->copy()->addMinutes(3)->subMillisecond(),
            'quantidade_velas_crash' => 1,
            'maior_multiplicador' => fake()->randomFloat(2, 10, 100),
            'status' => StatusJanelaPagamentoDoubleBranco::Agendada,
            'tiros_na_janela' => 0,
            'tiros_acumulados_ciclo' => 0,
            'fonte_indisponivel' => false,
        ];
    }
}
