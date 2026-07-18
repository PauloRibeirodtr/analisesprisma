@extends('adminlte::page')

@section('title', 'Reativar assinatura | Análises Prisma')

@php
    $registrarPagamento = (bool) old('registrar_pagamento', true);
@endphp

@section('content')
    <main class="prisma-admin">
        <x-admin.page-header
            titulo="Reativar assinatura"
            :subtitulo="'Reabra o acesso de '.$cliente->name.' ao plano '.$assinatura->nome_plano.'.'"
            icone="fas fa-redo-alt"
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
                    <strong>Não foi possível reativar a assinatura.</strong>
                    <span>Revise a confirmação e os campos destacados.</span>
                    @error('assinatura')
                        <span>{{ $message }}</span>
                    @enderror
                </div>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('admin.clientes.assinaturas.reativacao.store', [$cliente, $assinatura]) }}"
            class="prisma-contract-layout"
            data-reactivation-form
        >
            @csrf

            <div class="prisma-contract-main">
                <section class="prisma-panel">
                    <div class="prisma-panel__heading">
                        <span class="prisma-panel__icon">
                            <i class="fas fa-ban" aria-hidden="true"></i>
                        </span>
                        <div>
                            <span class="prisma-panel__kicker">Assinatura cancelada</span>
                            <h2>{{ $assinatura->nome_plano }}</h2>
                            <p>A reativação substituirá o período cancelado por um novo período de acesso.</p>
                        </div>
                    </div>

                    <dl class="prisma-data-grid prisma-data-grid--compact">
                        <div>
                            <dt>Período anterior</dt>
                            <dd>{{ $assinatura->inicio_em->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt>Vencimento anterior</dt>
                            <dd>{{ $assinatura->termino_em->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt>Cancelada em</dt>
                            <dd>{{ $assinatura->cancelada_em->format('d/m/Y H:i') }}</dd>
                        </div>
                    </dl>
                </section>

                <section class="prisma-panel prisma-panel--form">
                    <div class="prisma-panel__heading">
                        <span class="prisma-panel__icon">
                            <i class="fas fa-calendar-check" aria-hidden="true"></i>
                        </span>
                        <div>
                            <span class="prisma-panel__kicker">Novo acesso</span>
                            <h2>Período e justificativa</h2>
                            <p>A operação será registrada na observação histórica da assinatura.</p>
                        </div>
                    </div>

                    <div class="prisma-form">
                        <div class="prisma-form__grid">
                            <div class="form-group prisma-field">
                                <label for="inicio_em">Novo início</label>
                                <input
                                    id="inicio_em"
                                    name="inicio_em"
                                    type="datetime-local"
                                    value="{{ old('inicio_em', now()->format('Y-m-d\TH:i')) }}"
                                    class="form-control @error('inicio_em') is-invalid @enderror"
                                    required
                                >
                                @error('inicio_em')
                                    <span class="invalid-feedback">{{ $message }}</span>
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
                                    value="{{ old('duracao_dias', $assinatura->duracao_dias) }}"
                                    class="form-control @error('duracao_dias') is-invalid @enderror"
                                    required
                                >
                                @error('duracao_dias')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group prisma-field prisma-field--wide">
                                <label for="observacao">Motivo da reativação</label>
                                <textarea
                                    id="observacao"
                                    name="observacao"
                                    rows="4"
                                    minlength="10"
                                    maxlength="2000"
                                    class="form-control prisma-textarea @error('observacao') is-invalid @enderror"
                                    placeholder="Explique por que esta assinatura está sendo reativada."
                                    required
                                >{{ old('observacao') }}</textarea>
                                @error('observacao')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="prisma-form__section">
                                <span>Registro financeiro</span>
                                <small>Escolha entre pagamento e cortesia antes de confirmar.</small>
                            </div>

                            <div class="form-group prisma-field prisma-field--wide">
                                <input type="hidden" name="registrar_pagamento" value="0">
                                <label class="prisma-switch" for="registrar_pagamento">
                                    <input
                                        id="registrar_pagamento"
                                        name="registrar_pagamento"
                                        type="checkbox"
                                        value="1"
                                        data-payment-toggle
                                        @checked($registrarPagamento)
                                    >
                                    <span class="prisma-switch__track" aria-hidden="true"></span>
                                    <span>
                                        <strong>Registrar pagamento nesta reativação</strong>
                                        <small>Desative para conceder o novo período como cortesia.</small>
                                    </span>
                                </label>
                            </div>

                            <div class="prisma-reactivation-payment prisma-field--wide" data-payment-fields>
                                <div class="form-group prisma-field">
                                    <label for="valor_recebido">Valor recebido</label>
                                    <input
                                        id="valor_recebido"
                                        name="valor_recebido"
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        value="{{ old('valor_recebido', $assinatura->valor_referencia) }}"
                                        class="form-control @error('valor_recebido') is-invalid @enderror"
                                        @disabled(! $registrarPagamento)
                                    >
                                    @error('valor_recebido')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group prisma-field">
                                    <label for="data_pagamento">Data do pagamento</label>
                                    <input
                                        id="data_pagamento"
                                        name="data_pagamento"
                                        type="datetime-local"
                                        value="{{ old('data_pagamento', now()->format('Y-m-d\TH:i')) }}"
                                        class="form-control @error('data_pagamento') is-invalid @enderror"
                                        @disabled(! $registrarPagamento)
                                    >
                                    @error('data_pagamento')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group prisma-field">
                                    <label for="forma_pagamento">Forma de pagamento</label>
                                    <select
                                        id="forma_pagamento"
                                        name="forma_pagamento"
                                        class="form-control @error('forma_pagamento') is-invalid @enderror"
                                        @disabled(! $registrarPagamento)
                                    >
                                        <option value="">Selecione</option>
                                        @foreach (\App\Models\Pagamento::FORMAS_PAGAMENTO as $formaPagamento)
                                            <option value="{{ $formaPagamento }}" @selected(old('forma_pagamento') === $formaPagamento)>
                                                {{ ucfirst($formaPagamento) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('forma_pagamento')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div class="prisma-confirmation prisma-field--wide">
                                <label for="confirmada">
                                    <input
                                        id="confirmada"
                                        name="confirmada"
                                        type="checkbox"
                                        value="1"
                                        @checked(old('confirmada'))
                                        required
                                    >
                                    <span>
                                        Confirmo que revisei o período anterior e desejo reativar esta assinatura.
                                    </span>
                                </label>
                                @error('confirmada')
                                    <span class="prisma-field-error">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="prisma-form__actions">
                            <a href="{{ route('admin.clientes.show', $cliente) }}" class="btn prisma-btn prisma-btn--ghost">
                                Cancelar
                            </a>
                            <button type="submit" class="btn prisma-btn prisma-btn--primary">
                                <i class="fas fa-redo-alt" aria-hidden="true"></i>
                                Confirmar reativação
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="prisma-contract-sidebar">
                <section class="prisma-panel prisma-reactivation-warning">
                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                    <span>Operação explícita</span>
                    <strong>O cancelamento será removido.</strong>
                    <p>O período antigo continuará documentado no histórico da assinatura.</p>
                </section>
            </aside>
        </form>
    </main>
@stop

@section('css')
    <x-admin.prisma-assets />
@stop

@push('js')
    @vite('resources/js/admin/reativacao-assinatura.js')
@endpush
