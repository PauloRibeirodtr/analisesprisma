@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <div>
        <h1 class="m-0">Dashboard</h1>

        <small class="text-muted">
            PrismaBet — Análise por todos os ângulos.
        </small>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    Bem-vindo ao PrismaBet
                </h3>
            </div>

            <div class="card-body">
                Olá, <strong>{{ auth()->user()->name }}</strong>.
            </div>
        </div>

        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>Wheel</h3>
                        <p>Histórico Chillbet</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-circle-notch"></i>
                    </div>
                    <a href="{{ route('chillbet.wheel') }}" class="small-box-footer">
                        Acessar <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>Planos</h3>
                        <p>Gerenciamento de planos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <span class="small-box-footer">
                        Disponível em breve <i class="fas fa-clock"></i>
                    </span>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>Vídeos</h3>
                        <p>Conteúdos e treinamentos</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-video"></i>
                    </div>
                    <span class="small-box-footer">
                        Disponível em breve <i class="fas fa-clock"></i>
                    </span>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>Outros jogos</h3>
                        <p>Novas análises</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-gamepad"></i>
                    </div>
                    <span class="small-box-footer">
                        Disponível em breve <i class="fas fa-clock"></i>
                    </span>
                </div>
            </div>
        </div>

    </div>
@stop
