<?php

namespace App\Services;

use App\Models\PeriodoRecuperacaoDoubleBranco;
use App\StatusPeriodoRecuperacaoDoubleBranco;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ControleRecuperacaoDoubleBrancoService
{
    public function __construct(
        private LeituraHistoricoDoubleService $leituraHistorico,
    ) {}

    public function atualizar(): ?PeriodoRecuperacaoDoubleBranco
    {
        $periodoAtivo = PeriodoRecuperacaoDoubleBranco::query()
            ->ativas()
            ->latest('id')
            ->first();

        if ($periodoAtivo) {
            return $this->registrarBrancos($periodoAtivo);
        }

        $quantidadeGatilho = max(
            (int) config(
                'double.tiros_sem_branco_para_recuperacao',
                35,
            ),
            1,
        );
        $sequencia = $this->leituraHistorico
            ->buscarAteGatilhoSemBranco($quantidadeGatilho);

        if ($sequencia->count() < $quantidadeGatilho) {
            return null;
        }

        $gatilho = $sequencia->last();

        return DB::transaction(function () use (
            $gatilho,
        ): PeriodoRecuperacaoDoubleBranco {
            $periodoAtivo = PeriodoRecuperacaoDoubleBranco::query()
                ->ativas()
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($periodoAtivo) {
                return $periodoAtivo;
            }

            return PeriodoRecuperacaoDoubleBranco::query()->firstOrCreate(
                ['gatilho_idblaze' => $gatilho['idblaze']],
                [
                    'status' => StatusPeriodoRecuperacaoDoubleBranco::Ativa,
                    'gatilho_resultado_id' => $gatilho['id'],
                    'iniciado_em' => $gatilho['resultado_em'],
                ],
            );
        }, attempts: 3);
    }

    private function registrarBrancos(
        PeriodoRecuperacaoDoubleBranco $periodo,
    ): PeriodoRecuperacaoDoubleBranco {
        $brancos = $this->leituraHistorico->buscarBrancosDepois(
            $periodo->gatilho_resultado_id,
            CarbonImmutable::instance($periodo->iniciado_em)->utc(),
            2,
        );

        return DB::transaction(function () use (
            $periodo,
            $brancos,
        ): PeriodoRecuperacaoDoubleBranco {
            $periodo = PeriodoRecuperacaoDoubleBranco::query()
                ->lockForUpdate()
                ->findOrFail($periodo->id);

            if (
                $periodo->status
                === StatusPeriodoRecuperacaoDoubleBranco::Encerrada
            ) {
                return $periodo;
            }

            $primeiroBranco = $brancos->get(0);
            $segundoBranco = $brancos->get(1);
            $dados = [];

            if ($primeiroBranco) {
                $dados = [
                    'primeiro_branco_resultado_id' => $primeiroBranco['id'],
                    'primeiro_branco_idblaze' => $primeiroBranco['idblaze'],
                    'primeiro_branco_em' => $primeiroBranco['resultado_em'],
                ];
            }

            if ($segundoBranco) {
                $dados = [
                    ...$dados,
                    'status' => StatusPeriodoRecuperacaoDoubleBranco::Encerrada,
                    'segundo_branco_resultado_id' => $segundoBranco['id'],
                    'segundo_branco_idblaze' => $segundoBranco['idblaze'],
                    'segundo_branco_em' => $segundoBranco['resultado_em'],
                    'encerrado_em' => $segundoBranco['resultado_em'],
                ];
            }

            if ($dados !== []) {
                $periodo->forceFill($dados)->save();
            }

            return $periodo;
        }, attempts: 3);
    }
}
