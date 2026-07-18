@props(['resultados'])

<div class="table-responsive">
    <table class="table prisma-table">
        <thead>
            <tr>
                <th>Data</th>
                <th>Cliente</th>
                <th>Plano histórico</th>
                <th>Tipo</th>
                <th>Forma</th>
                <th>Responsável</th>
                <th class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($resultados as $pagamento)
                <tr>
                    <td>{{ $pagamento->data_pagamento->format('d/m/Y H:i') }}</td>
                    <td>
                        <strong>{{ $pagamento->cliente?->name ?? 'Cliente não localizado' }}</strong>
                        @if ($pagamento->cliente?->trashed())
                            <small class="prisma-report-muted d-block">Cadastro arquivado</small>
                        @endif
                    </td>
                    <td>{{ $pagamento->assinatura->nome_plano }}</td>
                    <td>
                        <span class="prisma-payment-type">
                            {{ Illuminate\Support\Str::headline($pagamento->tipo) }}
                        </span>
                    </td>
                    <td>{{ Illuminate\Support\Str::headline($pagamento->forma_pagamento ?: 'não informada') }}</td>
                    <td>
                        {{ $pagamento->registradoPor?->name ?? 'Responsável não localizado' }}
                        @if ($pagamento->registradoPor?->trashed())
                            <small class="prisma-report-muted d-block">Cadastro arquivado</small>
                        @endif
                    </td>
                    <td class="prisma-money text-right">
                        R$ {{ number_format((float) $pagamento->valor, 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">
                        <div class="prisma-empty">
                            <i class="fas fa-receipt" aria-hidden="true"></i>
                            <strong>Nenhum pagamento encontrado</strong>
                            <span>Altere o período ou os filtros financeiros.</span>
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
