@props([
    'resultados',
    'periodoAplicado',
])

<div class="prisma-revenue-summary">
    <div class="prisma-revenue-summary__visual" aria-hidden="true">
        <i class="fas fa-chart-line"></i>
    </div>

    <div>
        <span>Receita registrada</span>
        <strong>
            R$ {{ number_format((float) $resultados->receita_total, 2, ',', '.') }}
        </strong>
        <small>
            {{ $resultados->pagamentos_count }}
            {{ (int) $resultados->pagamentos_count === 1 ? 'pagamento' : 'pagamentos' }}
            entre {{ $periodoAplicado['inicio']->format('d/m/Y') }}
            e {{ $periodoAplicado['fim']->format('d/m/Y') }}.
        </small>
    </div>
</div>
