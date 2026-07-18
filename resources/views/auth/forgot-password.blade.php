@extends('adminlte::auth.auth-page', [
    'authType' => 'login',
])

@section('title', 'Recuperar senha | Análises Prisma')

@section('auth_header')
    <x-auth.prisma-brand />
@stop

@section('auth_body')
    <div class="prisma-auth-intro">
        <span class="prisma-auth-intro__icon fas fa-key" aria-hidden="true"></span>
        <h1>Recupere sua senha</h1>
        <p>Informe seu e-mail e enviaremos um link seguro para criar uma nova senha.</p>
    </div>

    @if (session('status'))
        <div class="prisma-auth-alert" role="alert">
            <span class="fas fa-check-circle" aria-hidden="true"></span>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form action="{{ route('password.email') }}" method="POST">
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

        <button type="submit" class="btn prisma-auth-button btn-block">
            Enviar link de recuperação
            <span class="fas fa-paper-plane" aria-hidden="true"></span>
        </button>
    </form>
@stop

@section('auth_footer')
    <p>
        Lembrou sua senha?
        <a href="{{ route('login') }}">Voltar para o login</a>
    </p>
@stop

@section('css')
    <x-auth.prisma-styles />
@stop
