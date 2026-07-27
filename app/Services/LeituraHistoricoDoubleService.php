<?php

namespace App\Services;

use App\Models\ResultadoBlazeHistorico;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class LeituraHistoricoDoubleService
{
    /**
     * @return Collection<int, array{
     *     id: int,
     *     idblaze: string,
     *     roll: int,
     *     resultado_em: CarbonImmutable
     * }>
     */
    public function buscarEntre(
        CarbonImmutable $inicioEm,
        CarbonImmutable $fimEm,
    ): Collection {
        $timezone = (string) config(
            'double.historico_timezone',
            'America/Sao_Paulo',
        );
        $inicioHistorico = $inicioEm
            ->setTimezone($timezone)
            ->format('Y-m-d H:i:s');
        $fimHistorico = $fimEm
            ->setTimezone($timezone)
            ->format('Y-m-d H:i:s');

        return ResultadoBlazeHistorico::query()
            ->select(['id', 'idblaze', 'created_at', 'roll'])
            ->whereBetween('created_at', [
                $inicioHistorico,
                $fimHistorico,
            ])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->filter(
                fn (ResultadoBlazeHistorico $resultado): bool => (
                    trim((string) $resultado->idblaze) !== ''
                    && $resultado->roll >= 0
                    && $resultado->roll <= 14
                ),
            )
            ->unique(fn (ResultadoBlazeHistorico $resultado): string => (
                (string) $resultado->idblaze
            ))
            ->map(
                fn (ResultadoBlazeHistorico $resultado): array => [
                    'id' => (int) $resultado->getKey(),
                    'idblaze' => (string) $resultado->idblaze,
                    'roll' => (int) $resultado->roll,
                    'resultado_em' => CarbonImmutable::createFromFormat(
                        'Y-m-d H:i:s',
                        (string) $resultado->created_at,
                        $timezone,
                    )->utc(),
                ],
            )
            ->values();
    }
}
