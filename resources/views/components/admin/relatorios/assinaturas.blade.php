@props(['resultados'])

<div class="table-responsive">
    <table class="table prisma-table">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Plano histórico</th>
                <th>Início</th>
                <th>Término</th>
                <th>Cancelamento</th>
                <th>Valor de referência</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($resultados as $assinatura)
                <tr>
                    <td>
                        <strong>{{ $assinatura->cliente?->name ?? 'Cliente não localizado' }}</strong>
                        @if ($assinatura->cliente?->trashed())
                            <small class="prisma-report-muted d-block">Cadastro arquivado</small>
                        @endif
                    </td>
                    <td>
                        {{ $assinatura->nome_plano }}
                        @if ($assinatura->plano?->trashed())
                            <small class="prisma-report-muted d-block">Plano arquivado</small>
                        @endif
                    </td>
                    <td>{{ $assinatura->inicio_em->format('d/m/Y H:i') }}</td>
                    <td>{{ $assinatura->termino_em->format('d/m/Y H:i') }}</td>
                    <td>{{ $assinatura->cancelada_em?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="prisma-money">
                        R$ {{ number_format((float) $assinatura->valor_referencia, 2, ',', '.') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        <div class="prisma-empty">
                            <i class="far fa-calendar-times" aria-hidden="true"></i>
                            <strong>Nenhuma assinatura encontrada</strong>
                            <span>Não há registros correspondentes aos filtros atuais.</span>
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
