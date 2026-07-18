<?php

namespace App\Queries\Admin;

use App\Models\Assinatura;
use App\Models\Pagamento;
use App\Models\Plano;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Pagination\LengthAwarePaginator;
use stdClass;

class RelatorioAdministrativo
{
    public const RELATORIOS = [
        'clientes_vigentes' => [
            'titulo' => 'Clientes com acesso vigente',
            'descricao' => 'Clientes com pelo menos uma assinatura liberando acesso agora.',
            'icone' => 'fas fa-user-check',
            'tipo' => 'clientes',
        ],
        'clientes_sem_vigencia' => [
            'titulo' => 'Clientes sem acesso vigente',
            'descricao' => 'Clientes sem assinatura válida no momento da consulta.',
            'icone' => 'fas fa-user-clock',
            'tipo' => 'clientes',
        ],
        'vencimentos_7_dias' => [
            'titulo' => 'Vencimentos em 7 dias',
            'descricao' => 'Assinaturas vigentes que encerram nos próximos sete dias.',
            'icone' => 'fas fa-calendar-day',
            'tipo' => 'assinaturas',
        ],
        'assinaturas_vencidas' => [
            'titulo' => 'Assinaturas vencidas',
            'descricao' => 'Períodos encerrados que não foram cancelados.',
            'icone' => 'fas fa-calendar-times',
            'tipo' => 'assinaturas',
        ],
        'assinaturas_canceladas' => [
            'titulo' => 'Assinaturas canceladas',
            'descricao' => 'Acessos cancelados, independentemente da data de término.',
            'icone' => 'fas fa-ban',
            'tipo' => 'assinaturas',
        ],
        'clientes_multiplos_planos' => [
            'titulo' => 'Clientes com múltiplos planos',
            'descricao' => 'Clientes com mais de um plano distinto vigente.',
            'icone' => 'fas fa-layer-group',
            'tipo' => 'clientes',
        ],
        'pagamentos_mes' => [
            'titulo' => 'Pagamentos no mês',
            'descricao' => 'Registros financeiros do mês ou período informado.',
            'icone' => 'fas fa-receipt',
            'tipo' => 'pagamentos',
        ],
        'receita_periodo' => [
            'titulo' => 'Receita por período',
            'descricao' => 'Total efetivamente registrado no período selecionado.',
            'icone' => 'fas fa-coins',
            'tipo' => 'receita_periodo',
        ],
        'receita_plano' => [
            'titulo' => 'Receita por plano',
            'descricao' => 'Receita agrupada pelo plano registrado na assinatura.',
            'icone' => 'fas fa-chart-pie',
            'tipo' => 'receita_planos',
        ],
        'historico_cliente' => [
            'titulo' => 'Histórico de pagamentos',
            'descricao' => 'Pagamentos preservados de cada cliente.',
            'icone' => 'fas fa-history',
            'tipo' => 'pagamentos',
        ],
    ];

    /**
     * @param  array<string, mixed>  $filtros
     * @return array{
     *     filtros: array<string, mixed>,
     *     meta: array{titulo: string, descricao: string, icone: string, tipo: string},
     *     periodoAplicado: array{inicio: CarbonImmutable, fim: CarbonImmutable}|null,
     *     relatorioAtual: string,
     *     resultados: LengthAwarePaginator|stdClass
     * }
     */
    public function consultar(array $filtros): array
    {
        $relatorioAtual = $filtros['relatorio'] ?? 'clientes_vigentes';
        $meta = self::RELATORIOS[$relatorioAtual];
        $periodoAplicado = null;

        if (in_array($relatorioAtual, [
            'pagamentos_mes',
            'receita_periodo',
            'receita_plano',
            'historico_cliente',
        ], true)) {
            $periodoAplicado = $this->periodoFinanceiro($filtros);
        }

        $resultados = match ($relatorioAtual) {
            'clientes_vigentes' => $this->clientesPorVigencia($filtros, true),
            'clientes_sem_vigencia' => $this->clientesPorVigencia($filtros, false),
            'vencimentos_7_dias' => $this->vencimentosProximos($filtros),
            'assinaturas_vencidas' => $this->assinaturasVencidas($filtros),
            'assinaturas_canceladas' => $this->assinaturasCanceladas($filtros),
            'clientes_multiplos_planos' => $this->clientesComMultiplosPlanos($filtros),
            'pagamentos_mes', 'historico_cliente' => $this->pagamentos(
                $filtros,
                $periodoAplicado,
            ),
            'receita_periodo' => $this->receitaPorPeriodo(
                $filtros,
                $periodoAplicado,
            ),
            'receita_plano' => $this->receitaPorPlano(
                $filtros,
                $periodoAplicado,
            ),
        };

        return compact(
            'filtros',
            'meta',
            'periodoAplicado',
            'relatorioAtual',
            'resultados',
        );
    }

    /**
     * @return array{
     *     clientes: Collection<int, User>,
     *     formasPagamento: array<int, string>,
     *     planos: Collection<int, Plano>,
     *     responsaveis: Collection<int, User>,
     *     tiposPagamento: array<int, string>
     * }
     */
    public function opcoesDosFiltros(): array
    {
        $clientes = User::query()
            ->withTrashed()
            ->select(['id', 'name', 'deleted_at'])
            ->where('perfil', 'cliente')
            ->orderBy('name')
            ->get();
        $planos = Plano::query()
            ->withTrashed()
            ->select(['id', 'nome', 'deleted_at'])
            ->orderBy('nome')
            ->get();
        $responsaveis = User::query()
            ->withTrashed()
            ->select(['id', 'name', 'deleted_at'])
            ->whereIn('perfil', User::PERFIS_ACESSO_IRRESTRITO)
            ->orderBy('name')
            ->get();

        return [
            'clientes' => $clientes,
            'formasPagamento' => Pagamento::FORMAS_PAGAMENTO,
            'planos' => $planos,
            'responsaveis' => $responsaveis,
            'tiposPagamento' => Pagamento::TIPOS,
        ];
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function clientesPorVigencia(
        array $filtros,
        bool $comVigencia,
    ): LengthAwarePaginator {
        $assinaturasVigentes = $this->assinaturasVigentes($filtros)
            ->select('user_id');

        return User::query()
            ->select(['id', 'name', 'email', 'telefone', 'created_at'])
            ->where('perfil', 'cliente')
            ->when(
                $comVigencia,
                fn (Builder $query) => $query->whereIn('id', $assinaturasVigentes),
                fn (Builder $query) => $query->whereNotIn('id', $assinaturasVigentes),
            )
            ->when(
                $filtros['cliente_id'] ?? null,
                fn (Builder $query, int $clienteId) => $query->whereKey($clienteId),
            )
            ->with([
                'assinaturas' => function (HasMany $query) use ($filtros): void {
                    $query
                        ->select([
                            'id',
                            'user_id',
                            'plano_id',
                            'nome_plano',
                            'inicio_em',
                            'termino_em',
                            'cancelada_em',
                        ])
                        ->vigentes()
                        ->when(
                            $filtros['plano_id'] ?? null,
                            fn (Builder $assinaturas, int $planoId) => $assinaturas
                                ->where('plano_id', $planoId),
                        )
                        ->with('plano:id,nome,deleted_at')
                        ->oldest('termino_em');
                },
            ])
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function vencimentosProximos(array $filtros): LengthAwarePaginator
    {
        $agora = CarbonImmutable::now();

        return $this->assinaturasParaRelatorio()
            ->whereNull('cancelada_em')
            ->where('inicio_em', '<=', $agora)
            ->whereBetween('termino_em', [
                $agora,
                $agora->addDays(7)->endOfDay(),
            ])
            ->tap(fn (Builder $query) => $this->aplicarFiltrosAssinatura(
                $query,
                $filtros,
            ))
            ->oldest('termino_em')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function assinaturasVencidas(array $filtros): LengthAwarePaginator
    {
        return $this->assinaturasParaRelatorio()
            ->whereNull('cancelada_em')
            ->vencidas()
            ->tap(fn (Builder $query) => $this->aplicarFiltrosAssinatura(
                $query,
                $filtros,
                'termino_em',
            ))
            ->latest('termino_em')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function assinaturasCanceladas(array $filtros): LengthAwarePaginator
    {
        return $this->assinaturasParaRelatorio()
            ->canceladas()
            ->tap(fn (Builder $query) => $this->aplicarFiltrosAssinatura(
                $query,
                $filtros,
                'cancelada_em',
            ))
            ->latest('cancelada_em')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function clientesComMultiplosPlanos(
        array $filtros,
    ): LengthAwarePaginator {
        $usuariosTable = (new User)->getTable();
        $usuariosComMultiplosPlanos = Assinatura::query()
            ->vigentes()
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(DISTINCT plano_id) > 1');

        return User::query()
            ->select(['id', 'name', 'email', 'telefone', 'created_at'])
            ->addSelect([
                'planos_vigentes_count' => Assinatura::query()
                    ->selectRaw('COUNT(DISTINCT plano_id)')
                    ->whereColumn('user_id', "{$usuariosTable}.id")
                    ->vigentes(),
            ])
            ->where('perfil', 'cliente')
            ->whereIn('id', $usuariosComMultiplosPlanos)
            ->when(
                $filtros['cliente_id'] ?? null,
                fn (Builder $query, int $clienteId) => $query->whereKey($clienteId),
            )
            ->when(
                $filtros['plano_id'] ?? null,
                fn (Builder $query, int $planoId) => $query->whereIn(
                    'id',
                    Assinatura::query()
                        ->vigentes()
                        ->where('plano_id', $planoId)
                        ->select('user_id'),
                ),
            )
            ->with([
                'assinaturas' => fn (HasMany $query) => $query
                    ->select([
                        'id',
                        'user_id',
                        'plano_id',
                        'nome_plano',
                        'inicio_em',
                        'termino_em',
                        'cancelada_em',
                    ])
                    ->vigentes()
                    ->with('plano:id,nome,deleted_at')
                    ->oldest('termino_em'),
            ])
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    private function assinaturasParaRelatorio(): Builder
    {
        return Assinatura::query()
            ->select([
                'id',
                'user_id',
                'plano_id',
                'nome_plano',
                'valor_referencia',
                'inicio_em',
                'termino_em',
                'cancelada_em',
            ])
            ->with([
                'cliente' => fn (BelongsTo $query) => $query
                    ->withTrashed()
                    ->select(['id', 'name', 'email', 'deleted_at']),
                'plano:id,nome,deleted_at',
            ]);
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function aplicarFiltrosAssinatura(
        Builder $query,
        array $filtros,
        ?string $colunaPeriodo = null,
    ): void {
        $query
            ->when(
                $filtros['cliente_id'] ?? null,
                fn (Builder $assinaturas, int $clienteId) => $assinaturas
                    ->where('user_id', $clienteId),
            )
            ->when(
                $filtros['plano_id'] ?? null,
                fn (Builder $assinaturas, int $planoId) => $assinaturas
                    ->where('plano_id', $planoId),
            );

        if ($colunaPeriodo !== null) {
            $query
                ->when(
                    $filtros['inicio'] ?? null,
                    fn (Builder $assinaturas, string $inicio) => $assinaturas
                        ->where(
                            $colunaPeriodo,
                            '>=',
                            CarbonImmutable::parse($inicio)->startOfDay(),
                        ),
                )
                ->when(
                    $filtros['fim'] ?? null,
                    fn (Builder $assinaturas, string $fim) => $assinaturas
                        ->where(
                            $colunaPeriodo,
                            '<=',
                            CarbonImmutable::parse($fim)->endOfDay(),
                        ),
                );
        }
    }

    /**
     * @param  array<string, mixed>  $filtros
     */
    private function assinaturasVigentes(array $filtros): Builder
    {
        return Assinatura::query()
            ->vigentes()
            ->when(
                $filtros['plano_id'] ?? null,
                fn (Builder $query, int $planoId) => $query
                    ->where('plano_id', $planoId),
            );
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @param  array{inicio: CarbonImmutable, fim: CarbonImmutable}  $periodo
     */
    private function pagamentos(
        array $filtros,
        array $periodo,
    ): LengthAwarePaginator {
        return $this->consultaPagamentos($filtros, $periodo)
            ->select([
                'id',
                'user_id',
                'assinatura_id',
                'valor',
                'data_pagamento',
                'tipo',
                'forma_pagamento',
                'registrado_por',
                'observacao',
            ])
            ->with([
                'assinatura:id,user_id,plano_id,nome_plano',
                'cliente' => fn (BelongsTo $query) => $query
                    ->withTrashed()
                    ->select(['id', 'name', 'email', 'deleted_at']),
                'registradoPor' => fn (BelongsTo $query) => $query
                    ->withTrashed()
                    ->select(['id', 'name', 'deleted_at']),
            ])
            ->latest('data_pagamento')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @param  array{inicio: CarbonImmutable, fim: CarbonImmutable}  $periodo
     */
    private function receitaPorPeriodo(array $filtros, array $periodo): stdClass
    {
        return $this->consultaPagamentos($filtros, $periodo)
            ->toBase()
            ->selectRaw('COUNT(*) as pagamentos_count')
            ->selectRaw('COALESCE(SUM(valor), 0) as receita_total')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @param  array{inicio: CarbonImmutable, fim: CarbonImmutable}  $periodo
     */
    private function receitaPorPlano(
        array $filtros,
        array $periodo,
    ): LengthAwarePaginator {
        $pagamentosTable = (new Pagamento)->getTable();
        $assinaturasTable = (new Assinatura)->getTable();

        return $this->consultaPagamentos($filtros, $periodo)
            ->join(
                $assinaturasTable,
                "{$assinaturasTable}.id",
                '=',
                "{$pagamentosTable}.assinatura_id",
            )
            ->select([
                "{$assinaturasTable}.plano_id",
                "{$assinaturasTable}.nome_plano",
            ])
            ->selectRaw("COUNT({$pagamentosTable}.id) as pagamentos_count")
            ->selectRaw(
                "COALESCE(SUM({$pagamentosTable}.valor), 0) as receita_total",
            )
            ->groupBy([
                "{$assinaturasTable}.plano_id",
                "{$assinaturasTable}.nome_plano",
            ])
            ->orderByDesc('receita_total')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @param  array{inicio: CarbonImmutable, fim: CarbonImmutable}  $periodo
     */
    private function consultaPagamentos(array $filtros, array $periodo): Builder
    {
        return Pagamento::query()
            ->whereBetween('data_pagamento', [
                $periodo['inicio'],
                $periodo['fim'],
            ])
            ->when(
                $filtros['cliente_id'] ?? null,
                fn (Builder $query, int $clienteId) => $query
                    ->where('user_id', $clienteId),
            )
            ->when(
                $filtros['plano_id'] ?? null,
                fn (Builder $query, int $planoId) => $query->whereIn(
                    'assinatura_id',
                    Assinatura::query()
                        ->where('plano_id', $planoId)
                        ->select('id'),
                ),
            )
            ->when(
                $filtros['forma_pagamento'] ?? null,
                fn (Builder $query, string $formaPagamento) => $query
                    ->where('forma_pagamento', $formaPagamento),
            )
            ->when(
                $filtros['tipo'] ?? null,
                fn (Builder $query, string $tipo) => $query
                    ->where('tipo', $tipo),
            )
            ->when(
                $filtros['registrado_por'] ?? null,
                fn (Builder $query, int $responsavelId) => $query
                    ->where('registrado_por', $responsavelId),
            );
    }

    /**
     * @param  array<string, mixed>  $filtros
     * @return array{inicio: CarbonImmutable, fim: CarbonImmutable}
     */
    private function periodoFinanceiro(array $filtros): array
    {
        $inicioInformado = isset($filtros['inicio'])
            ? CarbonImmutable::parse($filtros['inicio'])
            : null;
        $fimInformado = isset($filtros['fim'])
            ? CarbonImmutable::parse($filtros['fim'])
            : null;
        $referencia = $inicioInformado ?? $fimInformado ?? CarbonImmutable::now();

        return [
            'inicio' => ($inicioInformado ?? $referencia->startOfMonth())
                ->startOfDay(),
            'fim' => ($fimInformado ?? $referencia->endOfMonth())
                ->endOfDay(),
        ];
    }
}
