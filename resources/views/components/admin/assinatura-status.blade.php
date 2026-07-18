@props(['assinatura'])

@php
    if ($assinatura->estaCancelada()) {
        $situacao = 'Cancelada';
        $classe = 'prisma-status--cancelada';
        $icone = 'fas fa-ban';
    } elseif ($assinatura->inicio_em->gt(now())) {
        $situacao = 'Futura';
        $classe = 'prisma-status--futura';
        $icone = 'fas fa-hourglass-start';
    } elseif ($assinatura->estaVencida()) {
        $situacao = 'Vencida';
        $classe = 'prisma-status--vencida';
        $icone = 'fas fa-clock';
    } else {
        $situacao = 'Vigente';
        $classe = 'prisma-status--vigente';
        $icone = 'fas fa-check-circle';
    }
@endphp

<span {{ $attributes->merge(['class' => "prisma-status {$classe}"]) }}>
    <i class="{{ $icone }}" aria-hidden="true"></i>
    {{ $situacao }}
</span>
