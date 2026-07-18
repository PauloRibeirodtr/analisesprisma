@extends('adminlte::auth.auth-page', [
    'authType' => 'login',
])

@section('title', 'Verificar e-mail | Análises Prisma')

@section('auth_header')
    <x-auth.prisma-brand />
@stop

@section('auth_body')
    <div class="prisma-auth-intro">
        <span class="prisma-auth-intro__icon far fa-envelope" aria-hidden="true"></span>
        <h1>Verifique seu e-mail</h1>
        <p>Enviamos um link de confirmação. Acesse sua caixa de entrada para ativar sua conta.</p>
    </div>

    @if (session('status') === 'verification-link-sent')
        <div class="prisma-auth-alert" role="alert">
            <span class="fas fa-check-circle" aria-hidden="true"></span>
            <span>Um novo link de verificação foi enviado para seu e-mail.</span>
        </div>
    @endif

    <form action="{{ route('verification.send') }}" method="POST">
        @csrf

        <button type="submit" class="btn prisma-auth-button btn-block">
            Reenviar e-mail de verificação
            <span class="fas fa-paper-plane" aria-hidden="true"></span>
        </button>
    </form>
@stop

@section('auth_footer')
    <form action="{{ route('logout') }}" method="POST">
        @csrf

        <button type="submit" class="btn btn-link prisma-auth-link-button p-0">
            Sair e usar outra conta
        </button>
    </form>
@stop

@section('css')
    <x-auth.prisma-styles />
@stop
