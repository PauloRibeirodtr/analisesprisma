@extends('adminlte::page')

@section('title', 'Chillbet Wheel')

@section('content_header')
    <div>
        <h1 class="m-0">Chillbet Wheel</h1>

        <small class="text-muted">
            Histórico visual dos resultados da Wheel.
        </small>
    </div>
@stop

@section('content')
    <div class="container-fluid">
        <div
            id="chillbet-wheel-app"
            data-history-limit="50"
            data-analysis-limit="250"
        ></div>
    </div>
@stop

@push('js')
    @vite('resources/js/app.js')
@endpush
