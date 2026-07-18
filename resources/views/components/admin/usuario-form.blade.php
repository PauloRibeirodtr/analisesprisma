@props([
    'action',
    'method' => 'POST',
    'usuario' => null,
    'textoBotao' => 'Salvar usuário',
    'podeAdministrarPerfis' => false,
])

<form method="POST" action="{{ $action }}" class="prisma-form" novalidate>
    @csrf

    @if (! in_array($method, ['GET', 'POST'], true))
        @method($method)
    @endif

    <div class="prisma-form__grid">
        <div class="form-group prisma-field prisma-field--wide">
            <label for="name">Nome completo</label>
            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $usuario?->name) }}"
                class="form-control @error('name') is-invalid @enderror"
                placeholder="Nome do usuário"
                required
                autofocus
            >
            @error('name')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group prisma-field prisma-field--wide">
            <label for="email">E-mail</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email', $usuario?->email) }}"
                class="form-control @error('email') is-invalid @enderror"
                placeholder="usuario@exemplo.com"
                required
            >
            @error('email')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group prisma-field">
            <label for="cpf_cnpj">CPF ou CNPJ</label>
            <input
                id="cpf_cnpj"
                name="cpf_cnpj"
                type="text"
                value="{{ old('cpf_cnpj', $usuario?->cpf_cnpj) }}"
                class="form-control @error('cpf_cnpj') is-invalid @enderror"
                placeholder="Opcional"
                maxlength="18"
            >
            @error('cpf_cnpj')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group prisma-field">
            <label for="telefone">Telefone</label>
            <input
                id="telefone"
                name="telefone"
                type="text"
                value="{{ old('telefone', $usuario?->telefone) }}"
                class="form-control @error('telefone') is-invalid @enderror"
                placeholder="Opcional"
                maxlength="20"
            >
            @error('telefone')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group prisma-field">
            <label for="perfil">Perfil de acesso</label>
            <select
                id="perfil"
                name="perfil"
                class="form-control @error('perfil') is-invalid @enderror"
                required
            >
                <option value="cliente" @selected(old('perfil', $usuario?->perfil ?? 'cliente') === 'cliente')>
                    Cliente
                </option>
                @if ($podeAdministrarPerfis)
                    <option value="analista" @selected(old('perfil', $usuario?->perfil) === 'analista')>
                        Analista
                    </option>
                    <option value="administrador" @selected(old('perfil', $usuario?->perfil) === 'administrador')>
                        Administrador
                    </option>
                @endif
            </select>
            @error('perfil')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
            @unless ($podeAdministrarPerfis)
                <small class="form-text text-muted">
                    Analistas podem cadastrar e editar somente clientes.
                </small>
            @endunless
        </div>

        @if ($usuario === null)
            <div class="prisma-form__section prisma-field--wide">
                <span>Credenciais iniciais</span>
                <small>O usuário poderá alterar a senha pelos recursos de autenticação.</small>
            </div>

            <div class="form-group prisma-field">
                <label for="password">Senha</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="Senha segura"
                    required
                    autocomplete="new-password"
                >
                @error('password')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group prisma-field">
                <label for="password_confirmation">Confirmar senha</label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    class="form-control"
                    placeholder="Repita a senha"
                    required
                    autocomplete="new-password"
                >
            </div>
        @endif
    </div>

    <div class="prisma-form__actions">
        <a href="{{ route('admin.clientes.index') }}" class="btn prisma-btn prisma-btn--ghost">
            <i class="fas fa-arrow-left" aria-hidden="true"></i>
            Cancelar
        </a>

        <button type="submit" class="btn prisma-btn prisma-btn--primary">
            <i class="fas fa-check" aria-hidden="true"></i>
            {{ $textoBotao }}
        </button>
    </div>
</form>
