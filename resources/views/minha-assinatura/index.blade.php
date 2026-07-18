@extends('adminlte::page')

@section('title', 'Minha Assinatura | Análises Prisma')

@section('content')
    <main class="prisma-admin prisma-account">
        <x-admin.page-header
            titulo="Minha Assinatura"
            subtitulo="Consulte seus dados, seu acesso atual e todo o histórico de assinaturas."
            icone="fas fa-id-card"
            contexto="Área do usuário"
        />

        @if (session('sucesso_senha'))
            <div class="prisma-alert" role="status">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                {{ session('sucesso_senha') }}
            </div>
        @endif

        <section class="prisma-account-summary">
            <div class="prisma-account-summary__identity">
                <span>
                    {{ mb_strtoupper(mb_substr($usuario->name, 0, 1)) }}
                </span>
                <div>
                    <small>Conta Análises Prisma</small>
                    <h2>{{ $usuario->name }}</h2>
                    <p>{{ $usuario->email }}</p>
                </div>
            </div>

            <div class="prisma-account-summary__access">
                @if ($acessoAdministrativo)
                    <span class="prisma-account-summary__label">Tipo de acesso</span>
                    <strong>Acesso administrativo</strong>
                    <small>Este perfil não depende de uma assinatura.</small>
                @elseif ($proximoVencimento)
                    <span class="prisma-account-summary__label">Próximo vencimento</span>
                    <strong>{{ $proximoVencimento->format('d/m/Y') }}</strong>
                    <small>
                        às {{ $proximoVencimento->format('H:i') }}
                        · {{ $assinaturasVigentes->count() }}
                        {{ $assinaturasVigentes->count() === 1 ? 'acesso vigente' : 'acessos vigentes' }}
                    </small>
                @else
                    <span class="prisma-account-summary__label">Situação atual</span>
                    <strong>Sem assinatura vigente</strong>
                    <small>Seu histórico permanece disponível para consulta.</small>
                @endif
            </div>
        </section>

        <div class="prisma-account-layout">
            <section class="prisma-panel">
                <div class="prisma-panel__heading">
                    <span class="prisma-panel__icon">
                        <i class="fas fa-user" aria-hidden="true"></i>
                    </span>
                    <div>
                        <span class="prisma-panel__kicker">Dados básicos</span>
                        <h2>Meu cadastro</h2>
                        <p>Informações vinculadas à sua conta.</p>
                    </div>
                </div>

                <dl class="prisma-account-data">
                    <div>
                        <dt>Nome</dt>
                        <dd>{{ $usuario->name }}</dd>
                    </div>
                    <div>
                        <dt>E-mail</dt>
                        <dd>{{ $usuario->email }}</dd>
                    </div>
                    <div>
                        <dt>Perfil</dt>
                        <dd>{{ ucfirst($usuario->perfil) }}</dd>
                    </div>
                    <div>
                        <dt>Conta criada em</dt>
                        <dd>{{ $usuario->created_at?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="prisma-panel">
                <div class="prisma-panel__heading">
                    <span class="prisma-panel__icon">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                    </span>
                    <div>
                        <span class="prisma-panel__kicker">Segurança</span>
                        <h2>Trocar senha</h2>
                        <p>Confirme sua senha atual antes de definir uma nova.</p>
                    </div>
                </div>

                <form
                    method="POST"
                    action="{{ route('minha-assinatura.senha.update') }}"
                    class="prisma-account-password"
                >
                    @csrf
                    @method('PATCH')

                    <div class="form-group prisma-field">
                        <label for="current_password">Senha atual</label>
                        <input
                            id="current_password"
                            name="current_password"
                            type="password"
                            class="form-control @error('current_password') is-invalid @enderror"
                            required
                            autocomplete="current-password"
                        >
                        @error('current_password')
                            <span class="invalid-feedback">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="prisma-account-password__grid">
                        <div class="form-group prisma-field">
                            <label for="password">Nova senha</label>
                            <input
                                id="password"
                                name="password"
                                type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                required
                                autocomplete="new-password"
                            >
                            @error('password')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group prisma-field">
                            <label for="password_confirmation">Confirmar nova senha</label>
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                class="form-control"
                                required
                                autocomplete="new-password"
                            >
                        </div>
                    </div>

                    <button type="submit" class="btn prisma-btn prisma-btn--primary">
                        <i class="fas fa-shield-alt" aria-hidden="true"></i>
                        Atualizar senha
                    </button>
                </form>
            </section>
        </div>

        @unless ($acessoAdministrativo)
            <section class="prisma-panel">
                <div class="prisma-panel__heading">
                    <span class="prisma-panel__icon">
                        <i class="fas fa-check-double" aria-hidden="true"></i>
                    </span>
                    <div>
                        <span class="prisma-panel__kicker">Acesso atual</span>
                        <h2>Assinaturas vigentes</h2>
                        <p>Todos os módulos atualmente liberados para sua conta.</p>
                    </div>
                </div>

                <div class="prisma-current-subscriptions">
                    @forelse ($assinaturasVigentes as $assinatura)
                        <article>
                            <div>
                                <x-admin.assinatura-status :assinatura="$assinatura" />
                                <h3>{{ $assinatura->nome_plano }}</h3>
                                <p>
                                    Início em {{ $assinatura->inicio_em->format('d/m/Y H:i') }}
                                </p>
                            </div>
                            <div>
                                <small>Válida até</small>
                                <strong>{{ $assinatura->termino_em->format('d/m/Y H:i') }}</strong>
                            </div>
                        </article>
                    @empty
                        <div class="prisma-empty">
                            <i class="fas fa-hourglass-end" aria-hidden="true"></i>
                            <strong>Nenhuma assinatura vigente</strong>
                            <span>Assinaturas anteriores continuam disponíveis no histórico abaixo.</span>
                        </div>
                    @endforelse
                </div>
            </section>
        @endunless

        <section class="prisma-panel">
            <div class="prisma-panel__heading">
                <span class="prisma-panel__icon">
                    <i class="fas fa-history" aria-hidden="true"></i>
                </span>
                <div>
                    <span class="prisma-panel__kicker">Histórico preservado</span>
                    <h2>Histórico de assinaturas</h2>
                    <p>Períodos vigentes, vencidos, futuros ou cancelados vinculados à sua conta.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table prisma-table">
                    <thead>
                        <tr>
                            <th>Plano</th>
                            <th>Início</th>
                            <th>Vencimento</th>
                            <th>Situação</th>
                            <th>Cancelamento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assinaturas as $assinatura)
                            <tr>
                                <td>
                                    <strong>{{ $assinatura->nome_plano }}</strong>
                                    @if ($assinatura->plano?->trashed())
                                        <small class="d-block text-muted">Plano arquivado</small>
                                    @endif
                                </td>
                                <td>{{ $assinatura->inicio_em->format('d/m/Y H:i') }}</td>
                                <td>{{ $assinatura->termino_em->format('d/m/Y H:i') }}</td>
                                <td>
                                    <x-admin.assinatura-status :assinatura="$assinatura" />
                                </td>
                                <td>{{ $assinatura->cancelada_em?->format('d/m/Y H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="prisma-empty">
                                        <i class="fas fa-layer-group" aria-hidden="true"></i>
                                        <strong>Nenhuma assinatura registrada</strong>
                                        <span>Quando houver um período de acesso, ele aparecerá aqui.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($assinaturas->hasPages())
                <div class="prisma-pagination">
                    {{ $assinaturas->links() }}
                </div>
            @endif
        </section>
    </main>
@stop

@section('css')
    <x-admin.prisma-assets />
@stop
