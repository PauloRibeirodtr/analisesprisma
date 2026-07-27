<?php

namespace App\Services;

use App\Models\CicloDoubleBranco;
use App\Models\JanelaPagamentoDoubleBranco;
use App\StatusCicloDoubleBranco;
use App\StatusJanelaPagamentoDoubleBranco;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class ApuracaoJanelaDoubleBrancoService
{
    public function __construct(
        private LeituraHistoricoDoubleService $leituraHistorico,
    ) {}

    public function apurar(?CarbonImmutable $agora = null): int
    {
        $agora ??= CarbonImmutable::now('UTC');
        $janelaIds = JanelaPagamentoDoubleBranco::query()
            ->pendentesDeApuracao($agora)
            ->orderBy('inicio_em')
            ->orderBy('id')
            ->pluck('id');
        $fonteFalhou = false;
        $processadas = 0;

        foreach ($janelaIds as $janelaId) {
            $janela = JanelaPagamentoDoubleBranco::query()->find($janelaId);

            if (! $janela) {
                continue;
            }

            $resultados = collect();
            $fonteDisponivel = ! $fonteFalhou;

            if ($fonteDisponivel) {
                try {
                    $resultados = $this->leituraHistorico->buscarEntre(
                        CarbonImmutable::instance($janela->inicio_em)->utc(),
                        CarbonImmutable::instance($janela->fim_em)->utc(),
                    );
                } catch (Throwable) {
                    $fonteDisponivel = false;
                    $fonteFalhou = true;
                }
            }

            $this->apurarJanela(
                (int) $janelaId,
                $resultados,
                $fonteDisponivel,
                $agora,
            );
            $processadas++;
        }

        return $processadas;
    }

    /**
     * @param  Collection<int, array{
     *     id: int,
     *     idblaze: string,
     *     roll: int,
     *     resultado_em: CarbonImmutable
     * }>  $resultados
     */
    private function apurarJanela(
        int $janelaId,
        Collection $resultados,
        bool $fonteDisponivel,
        CarbonImmutable $agora,
    ): void {
        DB::transaction(function () use (
            $janelaId,
            $resultados,
            $fonteDisponivel,
            $agora,
        ): void {
            $janela = JanelaPagamentoDoubleBranco::query()
                ->lockForUpdate()
                ->find($janelaId);

            if (
                ! $janela
                || in_array($janela->status, [
                    StatusJanelaPagamentoDoubleBranco::Win,
                    StatusJanelaPagamentoDoubleBranco::Loss,
                ], true)
            ) {
                return;
            }

            $ciclo = $this->obterCiclo($janela);
            $fimComCarencia = CarbonImmutable::instance($janela->fim_em)
                ->utc()
                ->addSeconds(
                    max(
                        (int) config(
                            'double.finalizacao_grace_seconds',
                            15,
                        ),
                        0,
                    ),
                );
            $janelaEncerrada = $agora->greaterThanOrEqualTo(
                $fimComCarencia,
            );
            $indiceAcerto = $fonteDisponivel
                ? $resultados->search(
                    fn (array $resultado): bool => $resultado['roll'] === 0,
                )
                : false;
            $acerto = $indiceAcerto !== false
                ? $resultados->get($indiceAcerto)
                : null;

            if (! $fonteDisponivel && ! $janelaEncerrada) {
                $janela->forceFill([
                    'status' => StatusJanelaPagamentoDoubleBranco::Executando,
                    'fonte_indisponivel' => true,
                    'apurada_em' => $agora,
                ])->save();
                $this->atualizarTotaisCiclo($ciclo, $janela);

                return;
            }

            $tirosNaJanela = $fonteDisponivel
                ? ($indiceAcerto === false
                    ? $resultados->count()
                    : $indiceAcerto + 1)
                : $this->estimarTiros($janela);
            $status = match (true) {
                $acerto !== null => StatusJanelaPagamentoDoubleBranco::Win,
                $janelaEncerrada => StatusJanelaPagamentoDoubleBranco::Loss,
                default => StatusJanelaPagamentoDoubleBranco::Executando,
            };

            $janela->forceFill([
                'status' => $status,
                'tiros_na_janela' => $tirosNaJanela,
                'acerto_idblaze' => $acerto['idblaze'] ?? null,
                'acerto_em' => $acerto['resultado_em'] ?? null,
                'fonte_indisponivel' => ! $fonteDisponivel,
                'apurada_em' => $agora,
                'finalizada_em' => match ($status) {
                    StatusJanelaPagamentoDoubleBranco::Win => $acerto[
                        'resultado_em'
                    ],
                    StatusJanelaPagamentoDoubleBranco::Loss => $agora,
                    default => null,
                },
            ])->save();

            $this->atualizarTotaisCiclo($ciclo, $janela);
        }, attempts: 3);
    }

    private function obterCiclo(
        JanelaPagamentoDoubleBranco $janela,
    ): CicloDoubleBranco {
        if ($janela->ciclo_id) {
            return CicloDoubleBranco::query()
                ->lockForUpdate()
                ->findOrFail($janela->ciclo_id);
        }

        $ciclo = CicloDoubleBranco::query()
            ->abertos()
            ->latest('id')
            ->lockForUpdate()
            ->first();

        if (! $ciclo) {
            $ciclo = CicloDoubleBranco::query()->create([
                'status' => StatusCicloDoubleBranco::Aberto,
                'iniciado_em' => $janela->inicio_em,
            ]);
        }

        $janela->forceFill([
            'ciclo_id' => $ciclo->id,
            'ordem_no_ciclo' => (
                JanelaPagamentoDoubleBranco::query()
                    ->whereBelongsTo($ciclo, 'ciclo')
                    ->max('ordem_no_ciclo') ?? 0
            ) + 1,
        ])->save();

        return $ciclo;
    }

    private function atualizarTotaisCiclo(
        CicloDoubleBranco $ciclo,
        JanelaPagamentoDoubleBranco $janela,
    ): void {
        $janelasDoCiclo = JanelaPagamentoDoubleBranco::query()
            ->whereBelongsTo($ciclo, 'ciclo');
        $totalTiros = (int) (clone $janelasDoCiclo)
            ->sum('tiros_na_janela');
        $totalJanelas = (int) (clone $janelasDoCiclo)->count();
        $totalLosses = (int) (clone $janelasDoCiclo)
            ->where('status', StatusJanelaPagamentoDoubleBranco::Loss)
            ->count();

        $janela->forceFill([
            'tiros_acumulados_ciclo' => $totalTiros,
        ])->save();

        $dadosCiclo = [
            'total_janelas' => $totalJanelas,
            'total_tiros' => $totalTiros,
            'total_losses' => $totalLosses,
        ];

        if ($janela->status === StatusJanelaPagamentoDoubleBranco::Win) {
            $dadosCiclo = [
                ...$dadosCiclo,
                'status' => StatusCicloDoubleBranco::Win,
                'encerrado_em' => $janela->acerto_em,
                'acerto_idblaze' => $janela->acerto_idblaze,
                'acerto_em' => $janela->acerto_em,
            ];
        }

        $ciclo->forceFill($dadosCiclo)->save();
    }

    private function estimarTiros(
        JanelaPagamentoDoubleBranco $janela,
    ): int {
        $inicio = CarbonImmutable::instance($janela->inicio_em)->utc();
        $fim = CarbonImmutable::instance($janela->fim_em)->utc();
        $duracaoSegundos = max(
            ($fim->getTimestamp() - $inicio->getTimestamp()) + 1,
            1,
        );
        $minutos = (int) ceil($duracaoSegundos / 60);

        return $minutos * max(
            (int) config('double.tiros_estimados_por_minuto', 2),
            1,
        );
    }
}
