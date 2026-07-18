@extends('adminlte::auth.auth-page', [
    'authType' => 'login',
])

@section('title', 'Confirmar senha | Análises Prisma')

@section('auth_header')
    <x-auth.prisma-brand />
@stop

@section('auth_body')
    <div class="prisma-auth-intro">
        <span class="prisma-auth-intro__icon fas fa-user-shield" aria-hidden="true"></span>
        <h1>Confirme sua senha</h1>
        <p>Esta é uma área protegida. Confirme sua senha antes de continuar.</p>
    </div>

    <form action="{{ route('password.confirm.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="password">Senha</label>

            <div class="input-group prisma-auth-input">
                <input
                    id="password"
                    type="password"
                    name="password"
                    class="form-control @error('password') is-invalid @enderror"
                    placeholder="Digite sua senha"
                    required
                    autofocus
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

        <button type="submit" class="btn prisma-auth-button btn-block">
            Confirmar e continuar
            <span class="fas fa-arrow-right" aria-hidden="true"></span>
        </button>
    </form>
@stop

@section('css')
    <x-auth.prisma-styles />
@stop
