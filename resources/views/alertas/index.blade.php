@extends('layouts.app')

@section('title', 'Alertas | PrediccionIA')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/alertas.css') }}">
@endpush

@section('content')

<div class="alerts-page">

    {{-- =========================================================
         ENCABEZADO
    ========================================================== --}}

    <div class="page-header">

        <div>

            <div class="page-kicker">
                MONITOREO DEL SISTEMA
            </div>

            <h1>Alertas</h1>

            <p>
                Consulta las alertas relacionadas con el stock,
                inventario y reposición de productos.
            </p>

        </div>

    </div>


    {{-- =========================================================
         RESUMEN
    ========================================================== --}}

    <div class="alerts-summary">

        <div class="alert-summary-card">

            <div class="alert-summary-icon alert-icon-danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>

            <div>
                <span>Alertas críticas</span>

                <strong>
                    {{ $alertasCriticas }}
                </strong>

                <small>
                    requieren atención inmediata
                </small>
            </div>

        </div>


        <div class="alert-summary-card">

            <div class="alert-summary-icon alert-icon-warning">
                <i class="bi bi-exclamation-circle-fill"></i>
            </div>

            <div>
                <span>Alertas pendientes</span>

                <strong>
                    {{ $alertasPendientes }}
                </strong>

                <small>
                    requieren revisión
                </small>
            </div>

        </div>


        <div class="alert-summary-card">

            <div class="alert-summary-icon alert-icon-info">
                <i class="bi bi-info-circle-fill"></i>
            </div>

            <div>
                <span>Informativas</span>

                <strong>
                    {{ $alertasInformativas }}
                </strong>

                <small>
                    información del sistema
                </small>
            </div>

        </div>


        <div class="alert-summary-card">

            <div class="alert-summary-icon alert-icon-success">
                <i class="bi bi-check-circle-fill"></i>
            </div>

            <div>
                <span>Atendidas</span>

                <strong>
                    {{ $alertasAtendidas }}
                </strong>

                <small>
                    alertas solucionadas
                </small>
            </div>

        </div>

    </div>


    {{-- =========================================================
         FILTROS
    ========================================================== --}}

    <div class="alerts-filters">

        <form
            action="{{ route('alertas.index') }}"
            method="GET"
            class="alerts-filter-form"
        >

            <div class="filter-group">

                <label for="tipo">
                    Tipo de alerta
                </label>

                <select name="tipo" id="tipo">

                    <option value="">
                        Todas las alertas
                    </option>

                    <option
                        value="critica"
                        {{ $tipo === 'critica' ? 'selected' : '' }}
                    >
                        Críticas
                    </option>

                    <option
                        value="advertencia"
                        {{ $tipo === 'advertencia' ? 'selected' : '' }}
                    >
                        Advertencias
                    </option>

                    <option
                        value="informativa"
                        {{ $tipo === 'informativa' ? 'selected' : '' }}
                    >
                        Informativas
                    </option>

                </select>

            </div>


            <div class="filter-group">

                <label for="estado">
                    Estado
                </label>

                <select name="estado" id="estado">

                    <option value="">
                        Todos
                    </option>

                    <option
                        value="pendiente"
                        {{ $estado === 'pendiente' ? 'selected' : '' }}
                    >
                        Pendientes
                    </option>

                    <option
                        value="atendida"
                        {{ $estado === 'atendida' ? 'selected' : '' }}
                    >
                        Atendidas
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="btn btn-primary"
            >
                <i class="bi bi-search"></i>
                Filtrar
            </button>

        </form>

    </div>


    {{-- =========================================================
         LISTA DE ALERTAS
    ========================================================== --}}

    <div class="alerts-section">

        <div class="section-header">

            <div>

                <h2>
                    Alertas recientes
                </h2>

                <p>
                    Situaciones detectadas automáticamente por el sistema.
                </p>

            </div>

            <div class="alerts-badge">
                <i class="bi bi-bell"></i>
                Monitoreo activo
            </div>

        </div>


        {{-- =====================================================
             ALERTAS DINÁMICAS
        ====================================================== --}}

        @forelse($alertasFiltradas as $alerta)

            @php

                $claseAlerta = match($alerta['tipo']) {
                    'critica' => 'alert-item-danger',
                    'advertencia' => 'alert-item-warning',
                    'informativa' => 'alert-item-info',
                    default => 'alert-item-info',
                };

            @endphp


            <div class="alert-item {{ $claseAlerta }}">

                <div class="alert-item-icon">

                    <i class="bi {{ $alerta['icono'] }}"></i>

                </div>


                <div class="alert-item-content">

                    <div class="alert-item-header">

                        <div>

                            <h3>
                                {{ $alerta['titulo'] }}
                            </h3>

                            <span class="alert-type">

                                @switch($alerta['tipo'])

                                    @case('critica')
                                        Alerta crítica
                                        @break

                                    @case('advertencia')
                                        Advertencia
                                        @break

                                    @case('informativa')
                                        Informativa
                                        @break

                                @endswitch

                            </span>

                        </div>


                        <span class="alert-time">

                            @if($alerta['estado'] === 'pendiente')
                                Pendiente
                            @else
                                Atendida
                            @endif

                        </span>

                    </div>


                    <p>
                        {{ $alerta['descripcion'] }}
                    </p>


                    {{-- Información de stock --}}

                    @if($alerta['producto'])

                        <div class="alert-stock-info">

                            <span>
                                <i class="bi bi-box-seam"></i>
                                {{ $alerta['producto'] }}
                            </span>

                            <span>
                                Stock:
                                <strong>
                                    {{ $alerta['stock'] }}
                                </strong>
                            </span>

                            <span>
                                Mínimo:
                                <strong>
                                    {{ $alerta['stock_minimo'] }}
                                </strong>
                            </span>

                        </div>

                    @endif


                    <div class="alert-item-footer">

                        <span>
                            <i class="bi bi-box-seam"></i>
                            {{ $alerta['origen'] }}
                        </span>

                        <span>

                            @if($alerta['estado'] === 'pendiente')
                                <i class="bi bi-clock"></i>
                            @else
                                <i class="bi bi-check-circle"></i>
                            @endif

                            {{ $alerta['accion'] }}

                        </span>

                    </div>

                </div>

            </div>

        @empty

            <div class="alerts-empty">

                <div class="alerts-empty-icon">
                    <i class="bi bi-check-circle"></i>
                </div>

                <h3>
                    No hay alertas
                </h3>

                <p>
                    No se encontraron alertas que coincidan
                    con los filtros seleccionados.
                </p>

            </div>

        @endforelse

    </div>

</div>

@endsection