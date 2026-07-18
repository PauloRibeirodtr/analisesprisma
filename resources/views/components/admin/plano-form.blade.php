@props([
    'action',
    'method' => 'POST',
    'plano' => null,
    'textoBotao' => 'Salvar plano',
])

@php
    $editando = $plano !== null;
@endphp

<form method="POST" action="{{ $action }}" class="prisma-form" novalidate>
    @csrf

    @if (! in_array($method, ['GET', 'POST'], true))
        @method($method)
    @endif

    <div class="prisma-form__grid">
        <div class="form-group prisma-field prisma-field--wide">
            <label for="nome">Nome do plano</label>
            <input
                id="nome"
                name="nome"
                type="text"
                value="{{ old('nome', $plano?->nome) }}"
                class="form-control @error('nome') is-invalid @enderror"
                placeholder="Ex.: Análises Wheel Pro"
                maxlength="255"
                required
                autofocus
            >
            @error('nome')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group prisma-field prisma-field--wide">
            <label for="slug">Identificador do módulo</label>

            @if ($editando)
                <div class="prisma-slug-lock">
                    <i class="fas fa-lock" aria-hidden="true"></i>
                    <code>{{ $plano->slug }}</code>
                    <span>O identificador é permanente para preservar as regras de acesso.</span>
                </div>
            @else
                <input
                    id="slug"
                    name="slug"
                    type="text"
                    value="{{ old('slug') }}"
                    class="form-control @error('slug') is-invalid @enderror"
                    placeholder="Ex.: wheel-pro"
                    maxlength="100"
                    pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                    required
                >
                @error('slug')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
                <small class="prisma-field-help">
                    Use letras minúsculas, números e hífens. Esse valor será utilizado nas regras de acesso.
                </small>
            @endif
        </div>

        <div class="form-group prisma-field">
            <label for="valor_padrao">Valor padrão</label>
            <input
                id="valor_padrao"
                name="valor_padrao"
                type="number"
                value="{{ old('valor_padrao', $plano?->valor_padrao) }}"
                class="form-control @error('valor_padrao') is-invalid @enderror"
                placeholder="0,00"
                min="0"
                max="99999999.99"
                step="0.01"
                required
            >
            @error('valor_padrao')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group prisma-field">
            <label for="duracao_dias">Duração padrão em dias</label>
            <input
                id="duracao_dias"
                name="duracao_dias"
                type="number"
                value="{{ old('duracao_dias', $plano?->duracao_dias ?? 30) }}"
                class="form-control @error('duracao_dias') is-invalid @enderror"
                min="1"
                max="3650"
                required
            >
            @error('duracao_dias')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group prisma-field prisma-field--wide">
            <label for="descricao">Descrição <span class="text-muted">(opcional)</span></label>
            <textarea
                id="descricao"
                name="descricao"
                rows="5"
                maxlength="2000"
                class="form-control prisma-textarea @error('descricao') is-invalid @enderror"
                placeholder="Descreva os módulos e benefícios incluídos."
            >{{ old('descricao', $plano?->descricao) }}</textarea>
            @error('descricao')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="prisma-form__section">
            <span>Disponibilidade comercial</span>
            <small>
                Retirar um plano de venda impede novas contratações, mas não altera acessos ou históricos existentes.
            </small>
        </div>

        <div class="form-group prisma-field prisma-field--wide">
            <input type="hidden" name="disponivel_para_venda" value="0">
            <label class="prisma-switch" for="disponivel_para_venda">
                <input
                    id="disponivel_para_venda"
                    name="disponivel_para_venda"
                    type="checkbox"
                    value="1"
                    @checked((bool) old('disponivel_para_venda', $plano?->disponivel_para_venda ?? true))
                >
                <span class="prisma-switch__track" aria-hidden="true"></span>
                <span>
                    <strong>Disponível para novas contratações</strong>
                    <small>O plano aparecerá na tela de contratação manual.</small>
                </span>
            </label>
            @error('disponivel_para_venda')
                <span class="prisma-field-error">{{ $message }}</span>
            @enderror
        </div>
    </div>

    <div class="prisma-form__actions">
        <a href="{{ route('admin.planos.index') }}" class="btn prisma-btn prisma-btn--ghost">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            Cancelar
        </a>

        <button type="submit" class="btn prisma-btn prisma-btn--primary">
            <i class="fas fa-check" aria-hidden="true"></i>
            {{ $textoBotao }}
        </button>
    </div>
</form>
