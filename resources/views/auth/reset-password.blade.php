@extends('adminlte::auth.auth-page', [
    'authType' => 'login',
])

@section('title', 'Nova senha | Análises Prisma')

@section('auth_header')
    <x-auth.prisma-brand />
@stop

@section('auth_body')
    <div class="prisma-auth-intro">
        <span class="prisma-auth-intro__icon fas fa-lock" aria-hidden="true"></span>
        <h1>Defina uma nova senha</h1>
        <p>Escolha uma senha segura para voltar a acessar suas análises.</p>
    </div>

    <form action="{{ route('password.update') }}" method="POST">
        @csrf

        <input
            type="hidden"
            name="token"
            value="{{ $request->route('token') }}"
        >

        <div class="form-group">
            <label for="email">E-mail</label>

            <div class="input-group prisma-auth-input">
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email', $request->email) }}"
                    class="form-control @error('email') is-invalid @enderror"
                    placeholder="seu@email.com"
                    required
                    autocomplete="email"
                    @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                >

                <div class="input-group-append">
                    <div class="input-group-text" aria-hidden="true">
                        <span class="far fa-envelope"></span>
                    </div>
                </div>

                @error('email')
                    <span id="email-error" class="invalid-feedback">
                        {{ $message }}
                    </span>
                @enderror
            </div>
        </div>

        <div class="form-group">
            <label for="password">Nova senha</label>

            <div class="input-group prisma-auth-input">
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="Digite a nova senha"
                    required
                    autofocus
                    autocomplete="new-password"
                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                >

                <div class="input-group-append">
                    <div class="input-group-text" aria-hidden="true">
                        <span class="fas fa-lock"></span>
                    </div>
                </div>

                @error('password')
                    <span id="password-error" class="invalid-feedback">
                        {{ $message }}
                    </span>
                @enderror
            </div>
        </div>

        <div class="form-group">
            <label for="password_confirmation">Confirme a nova senha</label>

            <div class="input-group prisma-auth-input">
                <input
                    id="password_confirmation"
                    type="password"
                    name="password_confirmation"
                    class="form-control"
                    placeholder="Digite a senha novamente"
                    required
                    autocomplete="new-password"
                >

                <div class="input-group-append">
                    <div class="input-group-text" aria-hidden="true">
                        <span class="fas fa-shield-alt"></span>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn prisma-auth-button btn-block">
            Salvar nova senha
            <span class="fas fa-check" aria-hidden="true"></span>
        </button>
    </form>
@stop

@section('auth_footer')
    <p>
        Deseja tentar novamente?
        <a href="{{ route('login') }}">Voltar para o login</a>
    </p>
@stop

@section('css')
    <x-auth.prisma-styles />
@stop
