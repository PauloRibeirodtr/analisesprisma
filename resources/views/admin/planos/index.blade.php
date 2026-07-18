@extends('adminlte::page')

@section('title', 'Planos | Análises Prisma')

@section('content')
    <main class="prisma-admin">
        <x-admin.page-header
            titulo="Planos"
            subtitulo="Gerencie produtos, valores sugeridos e disponibilidade para novas contratações."
            icone="fas fa-layer-group"
        >
            <x-slot:acoes>
                <a href="{{ route('admin.planos.create') }}" class="btn prisma-btn prisma-btn--primary">
                    <i class="fas fa-plus" aria-hidden="true"></i>
                    Novo plano
                </a>
            </x-slot:acoes>
        </x-admin.page-header>

        @if (session('sucesso'))
            <div class="prisma-alert" role="alert">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                {{ session('sucesso') }}
            </div>
        @endif

        <section class="prisma-panel">
            <div class="prisma-panel__toolbar prisma-plan-toolbar">
                <div>
                    <span class="prisma-panel__kicker">Catálogo de acesso</span>
                    <h2>{{ $planos->total() }} {{ $planos->total() === 1 ? 'plano encontrado' : 'planos encontrados' }}</h2>
                </div>

                <form method="GET" action="{{ route('admin.planos.index') }}" class="prisma-plan-filters">
                    <div class="prisma-search">
                        <label for="busca" class="sr-only">Pesquisar planos</label>
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <input
                            id="busca"
                            name="busca"
                            type="search"
                            value="{{ $busca }}"
                            placeholder="Nome, identificador ou descrição"
                        >
                    </div>

                    <label for="situacao" class="sr-only">Filtrar por situação</label>
                    <select id="situacao" name="situacao" class="form-control prisma-filter-select">
                        <option value="todos" @selected($situacao === 'todos')>Todos</option>
                        <option value="venda" @selected($situacao === 'venda')>Disponíveis para venda</option>
                        <option value="indisponiveis" @selected($situacao === 'indisponiveis')>Indisponíveis</option>
                        <option value="arquivados" @selected($situacao === 'arquivados')>Arquivados</option>
                    </select>

                    <button type="submit" class="btn prisma-btn prisma-btn--primary">
                        Filtrar
                    </button>

                    @if ($busca !== '' || $situacao !== 'todos')
                        <a href="{{ route('admin.planos.index') }}" class="btn prisma-btn prisma-btn--ghost">
                            Limpar
                        </a>
                    @endif
                </form>
            </div>

            <div class="table-responsive">
                <table class="table prisma-table">
                    <thead>
                        <tr>
                            <th>Plano</th>
                            <th>Valor padrão</th>
                            <th>Duração</th>
                            <th class="text-center">Assinaturas</th>
                            <th>Situação</th>
                            <th>Atualização</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($planos as $plano)
                            <tr class="{{ $plano->trashed() ? 'prisma-row--muted' : '' }}">
                                <td>
                                    <div class="prisma-plan-identity">
                                        <span class="prisma-plan-identity__icon">
                                            <i class="fas {{ $plano->ePlanoSistema() ? 'fa-shield-alt' : 'fa-cube' }}" aria-hidden="true"></i>
                                        </span>
                                        <span>
                                            <strong>{{ $plano->nome }}</strong>
                                            <code>{{ $plano->slug }}</code>
                                            @if ($plano->descricao)
                                                <small>
                                                    {{ \Illuminate\Support\Str::limit($plano->descricao, 80) }}
                                                </small>
                                            @endif
                                        </span>
                                    </div>
                                </td>
                                <td class="prisma-money">
                                    R$ {{ number_format((float) $plano->valor_padrao, 2, ',', '.') }}
                                </td>
                                <td>
                                    {{ $plano->duracao_dias }}
                                    {{ $plano->duracao_dias === 1 ? 'dia' : 'dias' }}
                                </td>
                                <td class="text-center">
                                    <span class="prisma-count">{{ $plano->assinaturas_count }}</span>
                                </td>
                                <td>
                                    @if ($plano->trashed())
                                        <span class="prisma-catalog-status prisma-catalog-status--archived">
                                            <i class="fas fa-archive" aria-hidden="true"></i>
                                            Arquivado
                                        </span>
                                    @elseif ($plano->ePlanoSistema())
                                        <span class="prisma-catalog-status prisma-catalog-status--system">
                                            <i class="fas fa-lock" aria-hidden="true"></i>
                                            Sistema
                                        </span>
                                    @elseif ($plano->disponivel_para_venda)
                                        <span class="prisma-catalog-status prisma-catalog-status--sale">
                                            <i class="fas fa-check-circle" aria-hidden="true"></i>
                                            À venda
                                        </span>
                                    @else
                                        <span class="prisma-catalog-status prisma-catalog-status--unavailable">
                                            <i class="fas fa-pause-circle" aria-hidden="true"></i>
                                            Indisponível
                                        </span>
                                    @endif
                                </td>
                                <td>{{ $plano->updated_at->format('d/m/Y H:i') }}</td>
                                <td class="text-right">
                                    <div class="prisma-row-actions">
                                        @if ($plano->ePlanoSistema())
                                            <button
                                                class="btn prisma-icon-btn"
                                                type="button"
                                                title="Plano interno protegido"
                                                disabled
                                            >
                                                <i class="fas fa-lock" aria-hidden="true"></i>
                                                <span class="sr-only">Plano interno protegido</span>
                                            </button>
                                        @elseif ($plano->trashed())
                                            <form method="POST" action="{{ route('admin.planos.restore', $plano) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button
                                                    class="btn prisma-icon-btn prisma-icon-btn--restore"
                                                    type="submit"
                                                    title="Restaurar plano"
                                                >
                                                    <i class="fas fa-undo" aria-hidden="true"></i>
                                                    <span class="sr-only">Restaurar plano</span>
                                                </button>
                                            </form>
                                        @else
                                            <a
                                                href="{{ route('admin.planos.edit', $plano) }}"
                                                class="btn prisma-icon-btn"
                                                title="Editar plano"
                                            >
                                                <i class="fas fa-pen" aria-hidden="true"></i>
                                                <span class="sr-only">Editar plano</span>
                                            </a>
                                            <form method="POST" action="{{ route('admin.planos.destroy', $plano) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    class="btn prisma-icon-btn prisma-icon-btn--archive"
                                                    type="submit"
                                                    title="Arquivar plano"
                                                >
                                                    <i class="fas fa-archive" aria-hidden="true"></i>
                                                    <span class="sr-only">Arquivar plano</span>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="prisma-empty">
                                        <i class="fas fa-layer-group" aria-hidden="true"></i>
                                        <strong>Nenhum plano encontrado</strong>
                                        <span>Ajuste os filtros ou cadastre um novo produto.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($planos->hasPages())
                <div class="prisma-pagination">
                    {{ $planos->links() }}
                </div>
            @endif
        </section>
    </main>
@stop

@section('css')
    <x-admin.prisma-assets />
@stop
