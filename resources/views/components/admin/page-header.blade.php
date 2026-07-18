@props([
    'titulo',
    'subtitulo',
    'icone' => 'fas fa-users',
    'contexto' => 'Administração',
])

<div {{ $attributes->merge(['class' => 'prisma-page-header']) }}>
    <div class="prisma-page-header__identity">
        <x-auth.prisma-brand class="prisma-admin-brand" />

        <div class="prisma-page-header__copy">
            <span class="prisma-eyebrow">
                <i class="{{ $icone }}" aria-hidden="true"></i>
                {{ $contexto }}
            </span>

            <h1>{{ $titulo }}</h1>
            <p>{{ $subtitulo }}</p>
        </div>
    </div>

    @isset($acoes)
        <div class="prisma-page-header__actions">
            {{ $acoes }}
        </div>
    @endisset
</div>
