@extends('adminlte::page')

@section('title', $cliente->name.' | Análises Prisma')

@section('content')
    <main class="prisma-admin">
        <x-admin.page-header
            :titulo="$cliente->name"
            subtitulo="Visão completa dos dados, acessos e movimentações financeiras."
            icone="fas fa-user-circle"
        >
            <x-slot:acoes>
                <a href="{{ route('admin.clientes.index') }}" class="btn prisma-btn prisma-btn--ghost">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    Clientes
                </a>
                <a href="{{ route('admin.clientes.edit', $cliente) }}" class="btn prisma-btn prisma-btn--primary">
                    <i class="fas fa-pen" aria-hidden="true"></i>
                    Editar
                </a>
            </x-slot:acoes>
        </x-admin.page-header>

        @if (session('sucesso'))
            <div class="prisma-alert" role="alert">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                {{ session('sucesso') }}
            </div>
        @endif

        @if (session('erro'))
            <div class="prisma-alert prisma-alert--danger" role="alert">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                {{ session('erro') }}
            </div>
        @endif

        <section class="prisma-panel">
            <div class="prisma-panel__heading">
                <span class="prisma-panel__icon">
                    <i class="fas fa-address-card" aria-hidden="true"></i>
                </span>
                <div>
                    <span class="prisma-panel__kicker">Dados cadastrais</span>
                    <h2>Informações do usuário</h2>
                </div>
            </div>

            <dl class="prisma-data-grid">
                <div>
                    <dt>Nome</dt>
                    <dd>{{ $cliente->name }}</dd>
                </div>
                <div>
                    <dt>E-mail</dt>
                    <dd>{{ $cliente->email }}</dd>
                </div>
                <div>
                    <dt>Telefone</dt>
                    <dd>{{ $cliente->telefone ?: 'Não informado' }}</dd>
                </div>
                <div>
                    <dt>CPF ou CNPJ</dt>
                    <dd>{{ $cliente->cpf_cnpj ?: 'Não informado' }}</dd>
                </div>
                <div>
                    <dt>Perfil</dt>
                    <dd>
                        <span class="prisma-profile prisma-profile--{{ $cliente->perfil }}">
                            {{ ucfirst($cliente->perfil) }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt>Data do cadastro</dt>
                    <dd>{{ $cliente->created_at->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>
        </section>

        <section class="prisma-panel">
            <div class="prisma-panel__heading prisma-panel__heading--actions">
                <span class="prisma-panel__icon">
                    <i class="fas fa-layer-group" aria-hidden="true"></i>
                </span>
                <div>
                    <span class="prisma-panel__kicker">Controle de acesso</span>
                    <h2>Assinaturas</h2>
                    <p>A situação é calculada em tempo real pelas datas e pelo cancelamento.</p>
                </div>
                @if ($cliente->perfil === 'cliente')
                    <a
                        href="{{ route('admin.clientes.assinaturas.create', $cliente) }}"
                        class="btn prisma-btn prisma-btn--primary"
                    >
                        <i class="fas fa-plus" aria-hidden="true"></i>
                        Contratar plano
                    </a>
                @else
                    <button
                        class="btn prisma-btn prisma-btn--disabled"
                        type="button"
                        title="Apenas clientes recebem planos"
                        disabled
                    >
                        <i class="fas fa-plus" aria-hidden="true"></i>
                        Contratar plano
                    </button>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table prisma-table">
                    <thead>
                        <tr>
                            <th>Plano</th>
                            <th>Início</th>
                            <th>Término</th>
                            <th>Situação</th>
                            <th>Dias restantes</th>
                            <th>Cancelamento</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assinaturas as $assinatura)
                            @php
                                $diasRestantes = $assinatura->estaCancelada() || $assinatura->estaVencida()
                                    ? 0
                                    : (int) ceil(now()->diffInDays($assinatura->termino_em));
                            @endphp

                            <tr>
                                <td>
                                    <strong>{{ $assinatura->nome_plano }}</strong>
                                    @if ($assinatura->plano?->trashed())
                                        <small class="d-block text-muted">Plano arquivado</small>
                                    @endif
                                </td>
                                <td>{{ $assinatura->inicio_em->format('d/m/Y H:i') }}</td>
                                <td>{{ $assinatura->termino_em->format('d/m/Y H:i') }}</td>
                                <td>
                                    <x-admin.assinatura-status :assinatura="$assinatura" />
                                </td>
                                <td>
                                    {{ $diasRestantes }}
                                    {{ $diasRestantes === 1 ? 'dia' : 'dias' }}
                                </td>
                                <td>
                                    {{ $assinatura->cancelada_em?->format('d/m/Y H:i') ?? '—' }}
                                </td>
                                <td class="text-right">
                                    <div class="prisma-row-actions">
                                        @if ($assinatura->estaCancelada())
                                            <a
                                                href="{{ route('admin.clientes.assinaturas.reativacao.create', [$cliente, $assinatura]) }}"
                                                class="btn prisma-icon-btn prisma-icon-btn--restore"
                                                title="Reativar assinatura cancelada"
                                            >
                                                <i class="fas fa-redo-alt" aria-hidden="true"></i>
                                                <span class="sr-only">Reativar</span>
                                            </a>
                                        @elseif ($assinatura->inicio_em->gt(now()))
                                            <button
                                                class="btn prisma-icon-btn"
                                                type="button"
                                                disabled
                                                title="A assinatura ainda não iniciou"
                                            >
                                                <i class="fas fa-clock" aria-hidden="true"></i>
                                                <span class="sr-only">Aguardando início</span>
                                            </button>
                                        @else
                                            <a
                                                href="{{ route('admin.clientes.assinaturas.renovacao.create', [$cliente, $assinatura]) }}"
                                                class="btn prisma-icon-btn"
                                                title="Renovar assinatura"
                                            >
                                                <i class="fas fa-sync-alt" aria-hidden="true"></i>
                                                <span class="sr-only">Renovar</span>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="prisma-empty">
                                        <i class="fas fa-layer-group" aria-hidden="true"></i>
                                        <strong>Nenhuma assinatura registrada</strong>
                                        <span>Use a opção “Contratar plano” para registrar o primeiro acesso.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($assinaturas->hasPages())
                <div class="prisma-pagination">
                    {{ $assinaturas->links() }}
                </div>
            @endif
        </section>

        <section class="prisma-panel">
            <div class="prisma-panel__heading">
                <span class="prisma-panel__icon">
                    <i class="fas fa-receipt" aria-hidden="true"></i>
                </span>
                <div>
                    <span class="prisma-panel__kicker">Histórico preservado</span>
                    <h2>Pagamentos</h2>
                    <p>Os lançamentos anteriores nunca são sobrescritos.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table prisma-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Plano</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Forma</th>
                            <th>Responsável</th>
                            <th>Observação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pagamentos as $pagamento)
                            <tr>
                                <td>{{ $pagamento->data_pagamento->format('d/m/Y H:i') }}</td>
                                <td>{{ $pagamento->assinatura->nome_plano }}</td>
                                <td>
                                    <span class="prisma-payment-type">
                                        {{ ucfirst($pagamento->tipo) }}
                                    </span>
                                </td>
                                <td class="prisma-money">
                                    R$ {{ number_format((float) $pagamento->valor, 2, ',', '.') }}
                                </td>
                                <td>{{ $pagamento->forma_pagamento ? ucfirst($pagamento->forma_pagamento) : '—' }}</td>
                                <td>{{ $pagamento->registradoPor?->name ?? 'Usuário removido' }}</td>
                                <td class="prisma-observation">
                                    {{ $pagamento->observacao ?: '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="prisma-empty">
                                        <i class="fas fa-receipt" aria-hidden="true"></i>
                                        <strong>Nenhum pagamento registrado</strong>
                                        <span>Os lançamentos financeiros aparecerão aqui.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($pagamentos->hasPages())
                <div class="prisma-pagination">
                    {{ $pagamentos->links() }}
                </div>
            @endif
        </section>
    </main>
@stop

@section('css')
    <x-admin.prisma-assets />
@stop
