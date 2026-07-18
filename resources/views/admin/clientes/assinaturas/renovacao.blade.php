@extends('adminlte::page')

@section('title', 'Renovar assinatura | Análises Prisma')

@section('content')
    <main class="prisma-admin">
        <x-admin.page-header
            titulo="Renovar assinatura"
            :subtitulo="'Amplie o acesso de '.$cliente->name.' ao plano '.$assinatura->nome_plano.'.'"
            icone="fas fa-sync-alt"
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
                    <strong>Não foi possível renovar a assinatura.</strong>
                    <span>Revise os campos destacados e tente novamente.</span>
                    @error('assinatura')
                        <span>{{ $message }}</span>
                    @enderror
                </div>
            </div>
        @endif

        <form
            method="POST"
            action="{{ route('admin.clientes.assinaturas.renovacao.store', [$cliente, $assinatura]) }}"
            class="prisma-contract-layout"
        >
            @csrf

            <div class="prisma-contract-main">
                <section class="prisma-panel">
                    <div class="prisma-panel__heading">
                        <span class="prisma-panel__icon">
                            <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                        </span>
                        <div>
                            <span class="prisma-panel__kicker">Período atual</span>
                            <h2>{{ $assinatura->nome_plano }}</h2>
                            <p>Confira a assinatura selecionada antes de registrar a renovação.</p>
                        </div>
                    </div>

                    <dl class="prisma-data-grid prisma-data-grid--compact">
                        <div>
                            <dt>Início atual</dt>
                            <dd>{{ $assinatura->inicio_em->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt>Vencimento atual</dt>
                            <dd>{{ $assinatura->termino_em->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div>
                            <dt>Situação</dt>
                            <dd><x-admin.assinatura-status :assinatura="$assinatura" /></dd>
                        </div>
                    </dl>
                </section>

                <section class="prisma-panel prisma-panel--form">
                    <div class="prisma-panel__heading">
                        <span class="prisma-panel__icon">
                            <i class="fas fa-receipt" aria-hidden="true"></i>
                        </span>
                        <div>
                            <span class="prisma-panel__kicker">Nova renovação</span>
                            <h2>Período e pagamento</h2>
                            <p>O lançamento será adicionado ao histórico financeiro existente.</p>
                        </div>
                    </div>

                    <div class="prisma-form">
                        <div class="prisma-form__grid">
                            <div class="form-group prisma-field">
                                <label for="dias_adicionados">Quantidade de dias</label>
                                <input
                                    id="dias_adicionados"
                                    name="dias_adicionados"
                                    type="number"
                                    min="1"
                                    max="3650"
                                    value="{{ old('dias_adicionados', $assinatura->duracao_dias) }}"
                                    class="form-control @error('dias_adicionados') is-invalid @enderror"
                                    required
                                >
                                @error('dias_adicionados')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            @if ($assinatura->estaVencida())
                                <div class="form-group prisma-field">
                                    <label for="nova_data_inicio">Nova data de início</label>
                                    <input
                                        id="nova_data_inicio"
                                        name="nova_data_inicio"
                                        type="datetime-local"
                                        value="{{ old('nova_data_inicio', now()->format('Y-m-d\TH:i')) }}"
                                        class="form-control @error('nova_data_inicio') is-invalid @enderror"
                                        required
                                    >
                                    @error('nova_data_inicio')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            @endif

                            <div class="form-group prisma-field">
                                <label for="valor_recebido">Valor recebido</label>
                                <input
                                    id="valor_recebido"
                                    name="valor_recebido"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value="{{ old('valor_recebido', $assinatura->valor_referencia) }}"
                                    class="form-control @error('valor_recebido') is-invalid @enderror"
                                    required
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
                                    required
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
                                    required
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

                            <div class="form-group prisma-field prisma-field--wide">
                                <label for="observacao">Observação <span class="text-muted">(opcional)</span></label>
                                <textarea
                                    id="observacao"
                                    name="observacao"
                                    rows="4"
                                    maxlength="2000"
                                    class="form-control prisma-textarea @error('observacao') is-invalid @enderror"
                                    placeholder="Condições ou informações relevantes desta renovação."
                                >{{ old('observacao') }}</textarea>
                                @error('observacao')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="prisma-form__actions">
                            <a href="{{ route('admin.clientes.show', $cliente) }}" class="btn prisma-btn prisma-btn--ghost">
                                Cancelar
                            </a>
                            <button type="submit" class="btn prisma-btn prisma-btn--primary">
                                <i class="fas fa-sync-alt" aria-hidden="true"></i>
                                Confirmar renovação
                            </button>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="prisma-contract-sidebar">
                <section class="prisma-panel prisma-renewal-summary">
                    <div class="prisma-panel__heading">
                        <span class="prisma-panel__icon">
                            <i class="fas fa-info-circle" aria-hidden="true"></i>
                        </span>
                        <div>
                            <span class="prisma-panel__kicker">Regra aplicada</span>
                            <h2>{{ $assinatura->estaVencida() ? 'Reinício do acesso' : 'Extensão do acesso' }}</h2>
                        </div>
                    </div>
                    <div class="prisma-renewal-summary__body">
                        @if ($assinatura->estaVencida())
                            <p>
                                Como a assinatura venceu, o novo período começará na data escolhida.
                            </p>
                        @else
                            <p>
                                Os dias serão adicionados depois do vencimento atual, sem reduzir o período já contratado.
                            </p>
                        @endif
                        <div>
                            <span>Histórico</span>
                            <strong>O pagamento anterior será preservado.</strong>
                        </div>
                    </div>
                </section>
            </aside>
        </form>
    </main>
@stop

@section('css')
    <x-admin.prisma-assets />
@stop
