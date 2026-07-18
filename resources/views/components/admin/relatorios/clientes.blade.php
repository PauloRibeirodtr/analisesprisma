@props([
    'resultados',
    'relatorioAtual',
])

<div class="table-responsive">
    <table class="table prisma-table">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Telefone</th>
                <th>Planos vigentes</th>
                <th>Próximo vencimento</th>
                <th class="text-right">Ação</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($resultados as $cliente)
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
                    <td>
                        @if ($cliente->assinaturas->isEmpty())
                            <span class="prisma-report-muted">Nenhum plano vigente</span>
                        @else
                            <div class="prisma-report-tags">
                                @foreach ($cliente->assinaturas as $assinatura)
                                    <span>{{ $assinatura->nome_plano }}</span>
                                @endforeach
                            </div>
                            @if ($relatorioAtual === 'clientes_multiplos_planos')
                                <small class="prisma-report-count">
                                    {{ $cliente->planos_vigentes_count }}
                                    planos distintos
                                </small>
                            @endif
                        @endif
                    </td>
                    <td>
                        {{ $cliente->assinaturas->min('termino_em')?->format('d/m/Y H:i') ?? 'Sem acesso vigente' }}
                    </td>
                    <td class="text-right">
                        <a
                            href="{{ route('admin.clientes.show', $cliente) }}"
                            class="btn prisma-icon-btn"
                            title="Ver cliente"
                        >
                            <i class="fas fa-eye" aria-hidden="true"></i>
                            <span class="sr-only">Ver cliente</span>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <div class="prisma-empty">
                            <i class="fas fa-chart-bar" aria-hidden="true"></i>
                            <strong>Nenhum cliente encontrado</strong>
                            <span>Ajuste os filtros ou selecione outro relatório.</span>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($resultados->hasPages())
    <div class="prisma-pagination">
        {{ $resultados->links() }}
    </div>
@endif
