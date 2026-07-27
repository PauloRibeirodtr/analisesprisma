<?php

namespace App\Actions\DoubleBranco;

use App\Models\JanelaPagamentoDoubleBranco;
use App\Services\CrashWindowService;
use Illuminate\Support\Facades\DB;

class SincronizarJanelasPagamentoCrash
{
    public function __construct(
        private CrashWindowService $crashWindowService,
    ) {}

    public function handle(): int
    {
        $janelas = $this->crashWindowService->buscar();
        $sincronizadaEm = now();

        DB::transaction(function () use ($janelas, $sincronizadaEm): void {
            foreach ($janelas as $dados) {
                JanelaPagamentoDoubleBranco::query()->updateOrCreate(
                    ['origem_id' => $dados['origem_id']],
                    [
                        'inicio_em' => $dados['inicio_em'],
                        'fim_em' => $dados['fim_em'],
                        'quantidade_velas_crash' => $dados[
                            'quantidade_velas_crash'
                        ],
                        'maior_multiplicador' => $dados[
                            'maior_multiplicador'
                        ],
                        'sincronizada_em' => $sincronizadaEm,
                    ],
                );
            }
        }, attempts: 3);

        return $janelas->count();
    }
}
