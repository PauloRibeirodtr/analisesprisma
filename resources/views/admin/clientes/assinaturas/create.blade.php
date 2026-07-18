@extends('adminlte::page')

@section('title', 'Contratar plano | Análises Prisma')

@section('content')
    <main class="prisma-admin">
        <x-admin.page-header
            titulo="Contratar plano"
            :subtitulo="'Registre um novo acesso para '.$cliente->name.'.'"
            icone="fas fa-layer-group"
        >
            <x-slot:acoes>
                <a href="{{ route('admin.clientes.show', $cliente) }}" class="btn prisma-btn prisma-btn--ghost">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    Voltar ao cliente
                </a>
            </x-slot:acoes>
        </x-admin.page-header>

        @if ($errors->any())
            <div class="prisma-alert prisma-alert--danger" role="alert">
                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                <div>
                    <strong>Não foi possível registrar a contratação.</strong>
                    <span>Revise os campos destacados e tente novamente.</span>
                </div>
            </div>
        @endif

        <form
            action="{{ route('admin.clientes.assinaturas.store', $cliente) }}"
            method="POST"
            class="prisma-contract-layout"
            data-contract-form
        >
            @csrf

            <div class="prisma-contract-main">
                <section class="prisma-panel prisma-panel--form">
                    <div class="prisma-panel__heading">
                        <span class="prisma-panel__icon">
                            <i class="fas fa-file-signature" aria-hidden="true"></i>
                        </span>
                        <div>
                            <span class="prisma-panel__kicker">Nova contratação</span>
                            <h2>Plano e período de acesso</h2>
                            <p>Somente planos disponíveis para venda são apresentados.</p>
                        </div>
                    </div>

                    <div class="prisma-form">
                        <div class="prisma-form__grid">
                            <div class="form-group prisma-field prisma-field--wide">
                                <label for="plano_id">Plano</label>
                                <select
                                    id="plano_id"
                                    name="plano_id"
                                    class="form-control @error('plano_id') is-invalid @enderror"
                                    data-contract-plan-select
                                    required
                                    @disabled($planos->isEmpty())
                                >
                                    <option value="">Selecione um plano</option>
                                    @foreach ($planos as $plano)
                                        @php
                                            $estaVigente = $planosVigentes->contains($plano->id);
                                        @endphp
                                        <option
                                            value="{{ $plano->id }}"
                                            data-nome="{{ $plano->nome }}"
                                            data-valor="{{ $plano->valor_padrao }}"
                                            data-duracao="{{ $plano->duracao_dias }}"
                                            data-vigente="{{ $estaVigente ? '1' : '0' }}"
                                            @selected((string) old('plano_id') === (string) $plano->id)
                                            @disabled($estaVigente)
                                        >
                                            {{ $plano->nome }}
                                            — R$ {{ number_format((float) $plano->valor_padrao, 2, ',', '.') }}
                                            / {{ $plano->duracao_dias }} dias
                                            {{ $estaVigente ? '— já vigente' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('plano_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                @error('plano')
                                    <div class="prisma-field-error">{{ $message }}</div>
                                @enderror
                                @if ($planos->isEmpty())
                                    <small class="prisma-field-help">
                                        Não há planos disponíveis para venda neste momento.
                                    </small>
                                @else
                                    <small class="prisma-field-help">
                                        Planos já vigentes são identificados e devem ser renovados em vez de contratados novamente.
                                    </small>
                                @endif
                            </div>

                            <div class="form-group prisma-field">
                                <label for="inicio_em">Início do acesso</label>
                                <input
                                    id="inicio_em"
                                    name="inicio_em"
                                    type="datetime-local"
                                    class="form-control @error('inicio_em') is-invalid @enderror"
                                    value="{{ old('inicio_em', now()->format('Y-m-d\TH:i')) }}"
                                    required
                                >
                                @error('inicio_em')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group prisma-field">
                                <label for="duracao_dias">Duração em dias</label>
                                <input
                                    id="duracao_dias"
                                    name="duracao_dias"
                                    type="number"
                                    min="1"
                                    max="3650"
                                    class="form-control @error('duracao_dias') is-invalid @enderror"
                                    value="{{ old('duracao_dias') }}"
                                    data-contract-duration
                                    required
                                >
                                @error('duracao_dias')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </section>

                <section class="prisma-panel prisma-panel--form prisma-contract-payment">
                    <div class="prisma-panel__heading">
                        <span class="prisma-panel__icon">
                            <i class="fas fa-wallet" aria-hidden="true"></i>
                        </span>
                        <div>
                            <span class="prisma-panel__kicker">Pagamento</span>
                            <h2>Dados do recebimento</h2>
                            <p>O valor sugerido pelo plano pode ser ajustado para esta contratação.</p>
                        </div>
                    </div>

                    <div class="prisma-form">
                        <div class="prisma-form__grid">
                            <div class="form-group prisma-field">
                                <label for="valor_pago">Valor pago</label>
                                <input
                                    id="valor_pago"
                                    name="valor_pago"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="form-control @error('valor_pago') is-invalid @enderror"
                                    value="{{ old('valor_pago') }}"
                                    data-contract-value
                                    required
                                >
                                @error('valor_pago')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group prisma-field">
                                <label for="data_pagamento">Data do pagamento</label>
                                <input
                                    id="data_pagamento"
                                    name="data_pagamento"
                                    type="datetime-local"
                                    class="form-control @error('data_pagamento') is-invalid @enderror"
                                    value="{{ old('data_pagamento', now()->format('Y-m-d\TH:i')) }}"
                                    required
                                >
                                @error('data_pagamento')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group prisma-field">
                                <label for="forma_pagamento">Forma de pagamento</label>
                                <select
                                    id="forma_pagamento"
                                    name="forma_pagamento"
                                    class="form-control @error('forma_pagamento') is-invalid @enderror"
                                    required
                                >
                                    <option value="">Selecione</option>
                                    @foreach (\App\Models\Pagamento::FORMAS_PAGAMENTO as $formaPagamento)
                                        <option
                                            value="{{ $formaPagamento }}"
                                            @selected(old('forma_pagamento') === $formaPagamento)
                                        >
                                            {{ ucfirst($formaPagamento) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('forma_pagamento')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group prisma-field prisma-field--wide">
                                <label for="observacao">Observação <span class="text-muted">(opcional)</span></label>
                                <textarea
                                    id="observacao"
                                    name="observacao"
                                    rows="4"
                                    maxlength="2000"
                                    class="form-control prisma-textarea @error('observacao') is-invalid @enderror"
                                    placeholder="Inclua condições especiais ou informações úteis sobre esta contratação."
                                >{{ old('observacao') }}</textarea>
                                @error('observacao')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="prisma-form__actions d-flex">
                            <a href="{{ route('admin.clientes.show', $cliente) }}" class="btn prisma-btn prisma-btn--ghost">
                                Cancelar
                            </a>
                            <button
                                type="submit"
                                class="btn prisma-btn prisma-btn--primary"
                                @disabled($planos->isEmpty())
                            >
                                <i class="fas fa-check" aria-hidden="true"></i>
                                Confirmar contratação
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="prisma-contract-sidebar">
                <section class="prisma-panel prisma-contract-summary">
                    <div class="prisma-panel__heading">
                        <span class="prisma-panel__icon">
                            <i class="fas fa-receipt" aria-hidden="true"></i>
                        </span>
                        <div>
                            <span class="prisma-panel__kicker">Resumo</span>
                            <h2>Registro financeiro</h2>
                        </div>
                    </div>

                    <div class="prisma-contract-summary__body">
                        <span>Plano selecionado</span>
                        <strong data-contract-summary-name>Nenhum plano selecionado</strong>
                        <dl>
                            <div>
                                <dt>Valor sugerido</dt>
                                <dd data-contract-summary-value>—</dd>
                            </div>
                            <div>
                                <dt>Duração sugerida</dt>
                                <dd data-contract-summary-duration>—</dd>
                            </div>
                        </dl>
                    </div>
                </section>
            </aside>
        </form>
    </main>
@stop

@section('css')
    <x-admin.prisma-assets />
@stop

@push('js')
    @vite('resources/js/admin/contratacao-plano.js')
@endpush
