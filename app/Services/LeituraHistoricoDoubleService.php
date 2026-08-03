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

    /**
     * @return Collection<int, array{
     *     id: int,
     *     idblaze: string,
     *     roll: int,
     *     resultado_em: CarbonImmutable
     * }>
     */
    public function buscarAteGatilhoSemBranco(int $quantidade): Collection
    {
        $quantidade = max($quantidade, 1);
        $ultimoBranco = ResultadoBlazeHistorico::query()
            ->select(['id', 'idblaze', 'created_at', 'roll'])
            ->where('roll', 0)
            ->whereNotNull('idblaze')
            ->where('idblaze', '<>', '')
            ->latest('created_at')
            ->latest('id')
            ->first();
        $consulta = ResultadoBlazeHistorico::query()
            ->select(['id', 'idblaze', 'created_at', 'roll'])
            ->whereBetween('roll', [0, 14])
            ->whereNotNull('idblaze')
            ->where('idblaze', '<>', '')
            ->oldest('created_at')
            ->oldest('id');

        if ($ultimoBranco) {
            $consulta->where(function ($query) use ($ultimoBranco): void {
                $query
                    ->where('created_at', '>', $ultimoBranco->created_at)
                    ->orWhere(function ($query) use ($ultimoBranco): void {
                        $query
                            ->where('created_at', $ultimoBranco->created_at)
                            ->where('id', '>', $ultimoBranco->getKey());
                    });
            });
        }

        $resultados = collect();
        $idblazeVistos = $ultimoBranco
            ? [(string) $ultimoBranco->idblaze => true]
            : [];

        foreach ($consulta->cursor() as $resultado) {
            $idblaze = trim((string) $resultado->idblaze);

            if ($idblaze === '' || isset($idblazeVistos[$idblaze])) {
                continue;
            }

            $idblazeVistos[$idblaze] = true;

            if ((int) $resultado->roll === 0) {
                $resultados = collect();

                continue;
            }

            $resultados->push($this->formatarResultado($resultado));

            if ($resultados->count() === $quantidade) {
                break;
            }
        }

        return $resultados->values();
    }

    /**
     * @return Collection<int, array{
     *     id: int,
     *     idblaze: string,
     *     roll: int,
     *     resultado_em: CarbonImmutable
     * }>
     */
    public function buscarBrancosDepois(
        int $resultadoId,
        CarbonImmutable $resultadoEm,
        int $limite = 2,
    ): Collection {
        $horarioHistorico = $this->horarioHistorico($resultadoEm);
        $consulta = ResultadoBlazeHistorico::query()
            ->select(['id', 'idblaze', 'created_at', 'roll'])
            ->where('roll', 0)
            ->whereNotNull('idblaze')
            ->where('idblaze', '<>', '')
            ->where(function ($query) use (
                $horarioHistorico,
                $resultadoId,
            ): void {
                $query
                    ->where('created_at', '>', $horarioHistorico)
                    ->orWhere(function ($query) use (
                        $horarioHistorico,
                        $resultadoId,
                    ): void {
                        $query
                            ->where('created_at', $horarioHistorico)
                            ->where('id', '>', $resultadoId);
                    });
            })
            ->oldest('created_at')
            ->oldest('id');
        $limite = max($limite, 1);
        $brancos = collect();
        $idblazeVistos = [];

        foreach ($consulta->cursor() as $resultado) {
            $idblaze = trim((string) $resultado->idblaze);

            if ($idblaze === '' || isset($idblazeVistos[$idblaze])) {
                continue;
            }

            $idblazeVistos[$idblaze] = true;
            $brancos->push($this->formatarResultado($resultado));

            if ($brancos->count() === $limite) {
                break;
            }
        }

        return $brancos->values();
    }

    private function formatarResultado(
        ResultadoBlazeHistorico $resultado,
    ): array {
        $timezone = (string) config(
            'double.historico_timezone',
            'America/Sao_Paulo',
        );

        return [
            'id' => (int) $resultado->getKey(),
            'idblaze' => (string) $resultado->idblaze,
            'roll' => (int) $resultado->roll,
            'resultado_em' => CarbonImmutable::createFromFormat(
                'Y-m-d H:i:s',
                (string) $resultado->created_at,
                $timezone,
            )->utc(),
        ];
    }

    private function horarioHistorico(CarbonImmutable $horario): string
    {
        return $horario
            ->setTimezone((string) config(
                'double.historico_timezone',
                'America/Sao_Paulo',
            ))
            ->format('Y-m-d H:i:s');
    }
}
