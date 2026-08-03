<?php

namespace App\Services;

use App\Models\CicloDoubleBranco;
use App\Models\JanelaPagamentoDoubleBranco;
use App\Models\PeriodoRecuperacaoDoubleBranco;
use App\StatusJanelaPagamentoDoubleBranco;
use Carbon\CarbonImmutable;

class PainelJanelasDoubleBrancoService
{
    /**
     * @return array<string, mixed>
     */
    public function montar(?CarbonImmutable $agora = null): array
    {
        $agora ??= CarbonImmutable::now('UTC');
        $proximas = JanelaPagamentoDoubleBranco::query()
            ->select([
                'id',
                'origem_id',
                'ciclo_id',
                'inicio_em',
                'fim_em',
                'status',
                'ordem_no_ciclo',
                'tiros_na_janela',
                'tiros_acumulados_ciclo',
                'fonte_indisponivel',
            ])
            ->with('ciclo:id,total_tiros')
            ->where('fim_em', '>=', $agora)
            ->orderBy('inicio_em')
            ->orderBy('id')
            ->limit((int) config('double.painel_proximas', 3))
            ->get()
            ->map(
                fn (JanelaPagamentoDoubleBranco $janela): array => (
                    $this->formatarJanela($janela)
                ),
            )
            ->values();
        $cicloAtual = CicloDoubleBranco::query()
            ->abertos()
            ->latest('id')
            ->first();
        $janelasAnteriores = JanelaPagamentoDoubleBranco::query()
            ->select([
                'id',
                'origem_id',
                'ciclo_id',
                'inicio_em',
                'fim_em',
                'status',
                'ordem_no_ciclo',
                'tiros_na_janela',
                'tiros_acumulados_ciclo',
                'fonte_indisponivel',
            ])
            ->finalizadas()
            ->where('fim_em', '<', $agora)
            ->where(function ($query) use ($cicloAtual): void {
                $query->where(
                    'status',
                    StatusJanelaPagamentoDoubleBranco::Win,
                );

                if ($cicloAtual) {
                    $query->orWhere(function ($query) use (
                        $cicloAtual,
                    ): void {
                        $query
                            ->where(
                                'status',
                                StatusJanelaPagamentoDoubleBranco::Loss,
                            )
                            ->where('ciclo_id', $cicloAtual->id);
                    });
                }
            })
            ->orderByDesc('fim_em')
            ->orderByDesc('id')
            ->limit((int) config('double.painel_anteriores', 10))
            ->get()
            ->values();
        $anteriores = $janelasAnteriores
            ->map(function (
                JanelaPagamentoDoubleBranco $janela,
                int $indice,
            ): array {
                return [
                    ...$this->formatarJanela($janela),
                    'posicaoAnterior' => $indice + 1,
                ];
            });
        $ultimoWin = JanelaPagamentoDoubleBranco::query()
            ->where('status', StatusJanelaPagamentoDoubleBranco::Win)
            ->whereNotNull('acerto_em')
            ->latest('acerto_em')
            ->first([
                'acerto_em',
                'tiros_acumulados_ciclo',
            ]);
        $recuperacaoAtiva = PeriodoRecuperacaoDoubleBranco::query()
            ->ativas()
            ->latest('id')
            ->first();
        $atualizadoEm = JanelaPagamentoDoubleBranco::query()
            ->latest('updated_at')
            ->value('updated_at');
        $recuperacaoAtualizadaEm = $recuperacaoAtiva?->updated_at;

        if (
            $recuperacaoAtualizadaEm
            && (
                ! $atualizadoEm
                || $recuperacaoAtualizadaEm->greaterThan($atualizadoEm)
            )
        ) {
            $atualizadoEm = $recuperacaoAtualizadaEm;
        }

        return [
            'tipo' => 'double-branco-janelas-pagamento',
            'timezone' => 'UTC',
            'referenciaEm' => $this->iso($agora),
            'atualizadoEm' => $atualizadoEm
                ? $this->iso(CarbonImmutable::parse($atualizadoEm))
                : null,
            'proximas' => $proximas,
            'anteriores' => $anteriores,
            'recuperacao' => $recuperacaoAtiva ? [
                'ativa' => true,
                'iniciadaEm' => $this->iso(
                    CarbonImmutable::instance($recuperacaoAtiva->iniciado_em),
                ),
                'brancosRecebidos' => $recuperacaoAtiva->primeiro_branco_em
                    ? 1
                    : 0,
                'brancosNecessarios' => 2,
            ] : [
                'ativa' => false,
                'iniciadaEm' => null,
                'brancosRecebidos' => 0,
                'brancosNecessarios' => 2,
            ],
            'cicloAtual' => $cicloAtual ? [
                'id' => $cicloAtual->id,
                'ordemJanela' => $cicloAtual->total_janelas,
                'totalTiros' => $cicloAtual->total_tiros,
                'sequenciaLosses' => $cicloAtual->total_losses,
            ] : null,
            'ultimoWin' => $ultimoWin ? [
                'acertoEm' => $this->iso(
                    CarbonImmutable::instance($ultimoWin->acerto_em),
                ),
                'totalTiros' => $ultimoWin->tiros_acumulados_ciclo,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatarJanela(
        JanelaPagamentoDoubleBranco $janela,
    ): array {
        $inicioEm = CarbonImmutable::instance($janela->inicio_em)->utc();

        return [
            'id' => $janela->id,
            'origemId' => (string) $janela->origem_id,
            'inicioEm' => $this->iso($inicioEm),
            'fimEm' => $this->iso(
                CarbonImmutable::instance($janela->fim_em),
            ),
            'horarioAlvoEm' => $this->iso($inicioEm->addMinute()),
            'status' => $janela->status->value,
            'ordemNoCiclo' => $janela->ordem_no_ciclo,
            'tirosNaJanela' => $janela->tiros_na_janela,
            'tirosAcumuladosCiclo' => $janela->tiros_acumulados_ciclo,
            'tirosNoCicloAtual' => $janela->ciclo?->total_tiros,
            'fonteIndisponivel' => $janela->fonte_indisponivel,
        ];
    }

    private function iso(CarbonImmutable $data): string
    {
        return $data->utc()->format('Y-m-d\TH:i:s.v\Z');
    }
}
