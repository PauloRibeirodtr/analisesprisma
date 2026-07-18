@props(['resultados'])

<div class="table-responsive">
    <table class="table prisma-table">
        <thead>
            <tr>
                <th>Plano registrado</th>
                <th class="text-center">Pagamentos</th>
                <th class="text-right">Receita registrada</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($resultados as $plano)
                <tr>
                    <td>
                        <strong>{{ $plano->nome_plano }}</strong>
                        <small class="prisma-report-muted d-block">
                            Referência histórica do plano #{{ $plano->plano_id }}
                        </small>
                    </td>
                    <td class="text-center">
                        <span class="prisma-count">{{ $plano->pagamentos_count }}</span>
                    </td>
                    <td class="prisma-money text-right">
                        R$ {{ number_format((float) $plano->receita_total, 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">
                        <div class="prisma-empty">
                            <i class="fas fa-chart-pie" aria-hidden="true"></i>
                            <strong>Nenhuma receita encontrada</strong>
                            <span>Não há pagamentos no período e filtros informados.</span>
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
