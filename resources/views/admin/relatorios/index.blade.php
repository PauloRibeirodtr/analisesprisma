@extends('adminlte::page')

@section('title', 'Relatórios | Análises Prisma')

@section('content')
    <main class="prisma-admin">
        <x-admin.page-header
            titulo="Relatórios administrativos"
            subtitulo="Acompanhe acessos, vencimentos e movimentações financeiras por diferentes ângulos."
            icone="fas fa-chart-line"
        />

        @if ($errors->any())
            <div class="prisma-alert prisma-alert--danger" role="alert">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                <div>
                    <strong>Revise os filtros informados.</strong>
                    <span>{{ $errors->first() }}</span>
                </div>
            </div>
        @endif

        <nav class="prisma-report-grid" aria-label="Tipos de relatório">
            @foreach ($relatorios as $chave => $relatorio)
                <a
                    href="{{ route('admin.relatorios.index', ['relatorio' => $chave]) }}#resultado-relatorio"
                    class="prisma-report-card {{ $relatorioAtual === $chave ? 'is-active' : '' }}"
                    @if ($relatorioAtual === $chave) aria-current="page" @endif
                >
                    <span class="prisma-report-card__icon">
                        <i class="{{ $relatorio['icone'] }}" aria-hidden="true"></i>
                    </span>
                    <span>
                        <strong>{{ $relatorio['titulo'] }}</strong>
                        <small>{{ $relatorio['descricao'] }}</small>
                    </span>
                </a>
            @endforeach
        </nav>

        <section class="prisma-panel">
            <div class="prisma-panel__heading">
                <span class="prisma-panel__icon">
                    <i class="fas fa-filter" aria-hidden="true"></i>
                </span>
                <div>
                    <span class="prisma-panel__kicker">Refinar consulta</span>
                    <h2>Filtros do relatório</h2>
                    <p>Filtros não aplicáveis ao relatório selecionado são ignorados com segurança.</p>
                </div>
            </div>

            <form
                method="GET"
                action="{{ route('admin.relatorios.index') }}#resultado-relatorio"
                class="prisma-report-filters"
            >
                <input type="hidden" name="relatorio" value="{{ $relatorioAtual }}">

                <div class="prisma-field">
                    <label for="cliente_id">Cliente</label>
                    <select id="cliente_id" name="cliente_id" class="form-control">
                        <option value="">Todos os clientes</option>
                        @foreach ($clientes as $cliente)
                            <option
                                value="{{ $cliente->id }}"
                                @selected((string) ($filtros['cliente_id'] ?? '') === (string) $cliente->id)
                            >
                                {{ $cliente->name }}{{ $cliente->trashed() ? ' — arquivado' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="prisma-field">
                    <label for="plano_id">Plano</label>
                    <select id="plano_id" name="plano_id" class="form-control">
                        <option value="">Todos os planos</option>
                        @foreach ($planos as $plano)
                            <option
                                value="{{ $plano->id }}"
                                @selected((string) ($filtros['plano_id'] ?? '') === (string) $plano->id)
                            >
                                {{ $plano->nome }}{{ $plano->trashed() ? ' — arquivado' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="prisma-field">
                    <label for="inicio">Período inicial</label>
                    <input
                        id="inicio"
                        name="inicio"
                        type="date"
                        class="form-control"
                        value="{{ old('inicio', $filtros['inicio'] ?? '') }}"
                    >
                </div>

                <div class="prisma-field">
                    <label for="fim">Período final</label>
                    <input
                        id="fim"
                        name="fim"
                        type="date"
                        class="form-control"
                        value="{{ old('fim', $filtros['fim'] ?? '') }}"
                    >
                </div>

                <div class="prisma-field">
                    <label for="forma_pagamento">Forma de pagamento</label>
                    <select id="forma_pagamento" name="forma_pagamento" class="form-control">
                        <option value="">Todas as formas</option>
                        @foreach ($formasPagamento as $formaPagamento)
                            <option
                                value="{{ $formaPagamento }}"
                                @selected(($filtros['forma_pagamento'] ?? '') === $formaPagamento)
                            >
                                {{ Illuminate\Support\Str::headline($formaPagamento) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="prisma-field">
                    <label for="tipo">Tipo de pagamento</label>
                    <select id="tipo" name="tipo" class="form-control">
                        <option value="">Todos os tipos</option>
                        @foreach ($tiposPagamento as $tipoPagamento)
                            <option
                                value="{{ $tipoPagamento }}"
                                @selected(($filtros['tipo'] ?? '') === $tipoPagamento)
                            >
                                {{ Illuminate\Support\Str::headline($tipoPagamento) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="prisma-field">
                    <label for="registrado_por">Responsável pelo registro</label>
                    <select id="registrado_por" name="registrado_por" class="form-control">
                        <option value="">Todos os responsáveis</option>
                        @foreach ($responsaveis as $responsavel)
                            <option
                                value="{{ $responsavel->id }}"
                                @selected((string) ($filtros['registrado_por'] ?? '') === (string) $responsavel->id)
                            >
                                {{ $responsavel->name }}{{ $responsavel->trashed() ? ' — arquivado' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="prisma-report-filters__actions">
                    <a
                        href="{{ route('admin.relatorios.index', ['relatorio' => $relatorioAtual]) }}#resultado-relatorio"
                        class="btn prisma-btn prisma-btn--ghost"
                    >
                        <i class="fas fa-eraser" aria-hidden="true"></i>
                        Limpar
                    </a>
                    <button type="submit" class="btn prisma-btn prisma-btn--primary">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        Aplicar filtros
                    </button>
                </div>
            </form>
        </section>

        <section
            id="resultado-relatorio"
            class="prisma-panel prisma-report-result"
            aria-labelledby="titulo-resultado-relatorio"
        >
            <div class="prisma-panel__heading prisma-panel__heading--actions">
                <span class="prisma-panel__icon">
                    <i class="{{ $meta['icone'] }}" aria-hidden="true"></i>
                </span>
                <div>
                    <span class="prisma-panel__kicker">Resultado calculado</span>
                    <h2 id="titulo-resultado-relatorio">{{ $meta['titulo'] }}</h2>
                    <p>{{ $meta['descricao'] }}</p>
                </div>

                @if ($periodoAplicado)
                    <span class="prisma-report-period">
                        <i class="far fa-calendar-alt" aria-hidden="true"></i>
                        {{ $periodoAplicado['inicio']->format('d/m/Y') }}
                        a
                        {{ $periodoAplicado['fim']->format('d/m/Y') }}
                    </span>
                @endif
            </div>

            @switch($meta['tipo'])
                @case('clientes')
                    <x-admin.relatorios.clientes
                        :resultados="$resultados"
                        :relatorio-atual="$relatorioAtual"
                    />
                    @break

                @case('assinaturas')
                    <x-admin.relatorios.assinaturas :resultados="$resultados" />
                    @break

                @case('pagamentos')
                    <x-admin.relatorios.pagamentos :resultados="$resultados" />
                    @break

                @case('receita_periodo')
                    <x-admin.relatorios.receita-periodo
                        :resultados="$resultados"
                        :periodo-aplicado="$periodoAplicado"
                    />
                    @break

                @case('receita_planos')
                    <x-admin.relatorios.receita-planos :resultados="$resultados" />
                    @break
            @endswitch
        </section>
    </main>
@stop

@section('css')
    <x-admin.prisma-assets />
@stop
