<?php

namespace Database\Factories;

use App\Models\CicloDoubleBranco;
use App\StatusCicloDoubleBranco;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CicloDoubleBranco>
 */
class CicloDoubleBrancoFactory extends Factory
{
    protected $model = CicloDoubleBranco::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => StatusCicloDoubleBranco::Aberto,
            'iniciado_em' => now()->startOfMinute(),
            'total_janelas' => 0,
            'total_tiros' => 0,
            'total_losses' => 0,
        ];
    }
}
