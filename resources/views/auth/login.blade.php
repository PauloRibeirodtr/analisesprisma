@extends('adminlte::auth.auth-page', [
    'authType' => 'login',
])

@section('title', 'Entrar | Análises Prisma')

@section('auth_header')
    <x-auth.prisma-brand />
@stop

@section('auth_body')
    <div class="prisma-auth-intro">
        <h1>Bem-vindo de volta</h1>
        <p>Acesse sua conta para acompanhar suas análises.</p>
    </div>

    <form action="{{ route('login') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="email">E-mail</label>

            <div class="input-group prisma-auth-input">
                <input
                    id="email"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    class="form-control @error('email') is-invalid @enderror"
                    placeholder="seu@email.com"
                    required
                    autofocus
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
            <div class="d-flex align-items-center justify-content-between">
                <label for="password">Senha</label>

                <a class="prisma-auth-recovery" href="{{ route('password.request') }}">
                    Esqueceu a senha?
                </a>
            </div>

            <div class="input-group prisma-auth-input">
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="Digite sua senha"
                    required
                    autocomplete="current-password"
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

        <div class="prisma-auth-options">
            <div class="icheck-primary">
                <input
                    type="checkbox"
                    name="remember"
                    id="remember"
                    @checked(old('remember'))
                >

                <label for="remember">
                    Manter-me conectado
                </label>
            </div>
        </div>

        <button type="submit" class="btn prisma-auth-button btn-block">
            Entrar
            <span class="fas fa-arrow-right" aria-hidden="true"></span>
        </button>
    </form>
@stop

@section('auth_footer')
    <p>
        Ainda não possui uma conta?
        <a href="{{ route('register') }}">Criar conta</a>
    </p>
@stop

@section('css')
    <x-auth.prisma-styles />
@stop
