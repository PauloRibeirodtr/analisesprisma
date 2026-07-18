@extends('adminlte::page')

@section('title', 'Clientes | Análises Prisma')

@section('content')
    <main class="prisma-admin">
        <x-admin.page-header
            titulo="Clientes"
            subtitulo="Acompanhe cadastros, acessos vigentes e os próximos vencimentos."
        >
            <x-slot:acoes>
                <a href="{{ route('admin.clientes.create') }}" class="btn prisma-btn prisma-btn--primary">
                    <i class="fas fa-user-plus" aria-hidden="true"></i>
                    Novo usuário
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
            <div class="prisma-panel__toolbar">
                <div>
                    <span class="prisma-panel__kicker">Base de clientes</span>
                    <h2>{{ $clientes->total() }} {{ $clientes->total() === 1 ? 'cliente' : 'clientes' }}</h2>
                </div>

                <form method="GET" action="{{ route('admin.clientes.index') }}" class="prisma-search">
                    <label for="busca" class="sr-only">Pesquisar clientes</label>
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <input
                        id="busca"
                        name="busca"
                        type="search"
                        value="{{ $busca }}"
                        placeholder="Nome, e-mail, documento ou telefone"
                    >
                    <button type="submit">Pesquisar</button>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table prisma-table">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Telefone</th>
                            <th class="text-center">Acessos vigentes</th>
                            <th>Próximo vencimento</th>
                            <th>Cadastro</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clientes as $cliente)
                            <tr>
                                <td>
                                    <div class="prisma-person">
                                        <span class="prisma-person__avatar">
                                            {{ mb_strtoupper(mb_substr($cliente->name, 0, 1)) }}
                                        </span>
                                        <span>
                                            <strong>{{ $cliente->name }}</strong>
                                            <small>{{ $cliente->email }}</small>
                                        </span>
                                    </div>
                                </td>
                                <td>{{ $cliente->telefone ?: 'Não informado' }}</td>
                                <td class="text-center">
                                    <span class="prisma-count">
                                        {{ $cliente->assinaturas_vigentes_count }}
                                    </span>
                                </td>
                                <td>
                                    {{ $cliente->proximo_vencimento?->format('d/m/Y H:i') ?? 'Sem acesso vigente' }}
                                </td>
                                <td>{{ $cliente->created_at->format('d/m/Y') }}</td>
                                <td class="text-right">
                                    <div class="prisma-row-actions">
                                        <a
                                            href="{{ route('admin.clientes.show', $cliente) }}"
                                            class="btn prisma-icon-btn"
                                            title="Ver detalhes"
                                        >
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                            <span class="sr-only">Ver detalhes</span>
                                        </a>
                                        <a
                                            href="{{ route('admin.clientes.edit', $cliente) }}"
                                            class="btn prisma-icon-btn"
                                            title="Editar usuário"
                                        >
                                            <i class="fas fa-pen" aria-hidden="true"></i>
                                            <span class="sr-only">Editar usuário</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="prisma-empty">
                                        <i class="fas fa-users" aria-hidden="true"></i>
                                        <strong>Nenhum cliente encontrado</strong>
                                        <span>Ajuste a pesquisa ou cadastre um novo usuário.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($clientes->hasPages())
                <div class="prisma-pagination">
                    {{ $clientes->links() }}
                </div>
            @endif
        </section>
    </main>
@stop

@section('css')
    <x-admin.prisma-assets />
@stop
