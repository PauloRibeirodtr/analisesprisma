@extends('adminlte::auth.auth-page', [
    'authType' => 'register',
])

@section('title', 'Criar conta | Análises Prisma')

@section('auth_header')
    <x-auth.prisma-brand />
@stop

@section('auth_body')
    <div class="prisma-auth-intro">
        <h1>Crie sua conta</h1>
        <p>Comece a transformar resultados em análises mais claras.</p>
    </div>

    <form action="{{ route('register') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="name">Nome</label>

            <div class="input-group prisma-auth-input">
                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    class="form-control @error('name') is-invalid @enderror"
                    placeholder="Seu nome"
                    required
                    autofocus
                    autocomplete="name"
                    @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                >

                <div class="input-group-append">
                    <div class="input-group-text" aria-hidden="true">
                        <span class="far fa-user"></span>
                    </div>
                </div>

                @error('name')
                    <span id="name-error" class="invalid-feedback">
                        {{ $message }}
                    </span>
                @enderror
            </div>
        </div>

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
            <label for="password">Senha</label>

            <div class="input-group prisma-auth-input">
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="Crie uma senha"
                    required
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
            <label for="password_confirmation">Confirme sua senha</label>

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
            Criar conta
            <span class="fas fa-arrow-right" aria-hidden="true"></span>
        </button>
    </form>
@stop

@section('auth_footer')
    <p>
        Já possui uma conta?
        <a href="{{ route('login') }}">Entrar</a>
    </p>
@stop

@section('css')
    <x-auth.prisma-styles />
@stop
