@extends('layouts.app')

@section('title', 'Análisis de demanda | PrediccionIA')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/analisis.css') }}">
@endpush

@section('content')

<div class="analysis-page">

    {{-- =====================================================
         HEADER
    ====================================================== --}}

    <div class="page-header">

        <div>

            <div class="page-kicker">
                INTELIGENCIA ARTIFICIAL
            </div>

            <h1>
                Análisis de demanda
            </h1>

            <p>
                Analiza el comportamiento histórico de la demanda
                y visualiza sus principales tendencias.
            </p>

        </div>

    </div>


    {{-- =====================================================
         FILTROS
    ====================================================== --}}

    <div class="analysis-filters">

        <form
            action="{{ route('analisis.index') }}"
            method="GET"
            class="analysis-filter-form"
        >

            <div class="filter-group">

                <label for="producto_id">
                    Producto
                </label>

                <select
                    name="producto_id"
                    id="producto_id"
                >

                    <option value="">
                        Todos los productos
                    </option>

                    @foreach($productos as $producto)

                        <option
                            value="{{ $producto->id }}"
                            {{ (string) $productoId === (string) $producto->id ? 'selected' : '' }}
                        >
                            {{ $producto->nombre }}
                        </option>

                    @endforeach

                </select>

            </div>


            <div class="filter-group">

                <label for="periodo">
                    Período
                </label>

                <select
                    name="periodo"
                    id="periodo"
                >

                    <option
                        value="7"
                        {{ $periodo === '7' ? 'selected' : '' }}
                    >
                        Últimos 7 días
                    </option>

                    <option
                        value="30"
                        {{ $periodo === '30' ? 'selected' : '' }}
                    >
                        Últimos 30 días
                    </option>

                    <option
                        value="90"
                        {{ $periodo === '90' ? 'selected' : '' }}
                    >
                        Últimos 90 días
                    </option>

                    <option
                        value="365"
                        {{ $periodo === '365' ? 'selected' : '' }}
                    >
                        Último año
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="btn btn-primary"
            >

                <i class="bi bi-search"></i>

                Analizar

            </button>

        </form>

    </div>


    {{-- =====================================================
         RESUMEN
    ====================================================== --}}

    <div class="analysis-summary">


        {{-- DEMANDA PROMEDIO --}}
        <div class="analysis-summary-card">

            <div class="analysis-summary-icon blue">

                <i class="bi bi-graph-up"></i>

            </div>

            <div>

                <span>
                    Demanda promedio
                </span>

                <strong>
                    {{ number_format($demandaPromedio, 2) }}
                </strong>

                <small>
                    unidades por día
                </small>

            </div>

        </div>


        {{-- DEMANDA TOTAL --}}
        <div class="analysis-summary-card">

            <div class="analysis-summary-icon purple">

                <i class="bi bi-bar-chart"></i>

            </div>

            <div>

                <span>
                    Demanda total
                </span>

                <strong>
                    {{ number_format($demandaTotal) }}
                </strong>

                <small>
                    unidades analizadas
                </small>

            </div>

        </div>


        {{-- MAYOR DEMANDA --}}
        <div class="analysis-summary-card">

            <div class="analysis-summary-icon orange">

                <i class="bi bi-arrow-up-right"></i>

            </div>

            <div>

                <span>
                    Día de mayor demanda
                </span>

                <strong>

                    @if($fechaMayorDemanda)
                        {{ $fechaMayorDemanda }}
                    @else
                        --
                    @endif

                </strong>

                <small>

                    @if($cantidadMayorDemanda > 0)
                        {{ $cantidadMayorDemanda }} unidades
                    @else
                        Sin ventas registradas
                    @endif

                </small>

            </div>

        </div>


        {{-- TENDENCIA --}}
        <div class="analysis-summary-card">

            <div class="analysis-summary-icon green">

                <i class="bi bi-activity"></i>

            </div>

            <div>

                <span>
                    Tendencia
                </span>

                <strong>
                    {{ $tendencia }}
                </strong>

                <small>

                    @if($tendenciaPorcentaje > 0)
                        +{{ number_format($tendenciaPorcentaje, 1) }}%
                    @elseif($tendenciaPorcentaje < 0)
                        {{ number_format($tendenciaPorcentaje, 1) }}%
                    @else
                        0%
                    @endif

                    respecto al inicio del período

                </small>

            </div>

        </div>

    </div>


    {{-- =====================================================
         GRÁFICO
    ====================================================== --}}

    <div class="analysis-section">

        <div class="section-header">

            <div>

                <h2>
                    Comportamiento de la demanda
                </h2>

                <p>

                    {{ $productoSeleccionado
                        ? 'Evolución de ' . $productoSeleccionado->nombre
                        : 'Evolución de todos los productos'
                    }}

                    durante {{ $nombrePeriodo }}.

                </p>

            </div>

            <div class="analysis-badge">

                <i class="bi bi-bar-chart-line"></i>

                Datos reales

            </div>

        </div>


        <div class="chart-container">

            <canvas id="demandaChart"></canvas>

        </div>

    </div>


    {{-- =====================================================
         TENDENCIAS
    ====================================================== --}}

    <div class="analysis-bottom-grid">


        {{-- PRINCIPALES DÍAS --}}
        <div class="analysis-section">

            <div class="section-header">

                <div>

                    <h2>
                        Principales días
                    </h2>

                    <p>
                        Días con mayor demanda durante el período.
                    </p>

                </div>

            </div>


            @if(count($principalesDias) > 0)

                <div class="trend-list">

                    @foreach($principalesDias as $indice => $dia)

                        <div class="trend-item">

                            <div class="trend-position">
                                {{ $indice + 1 }}
                            </div>

                            <div class="trend-info">

                                <strong>
                                    {{ $dia['fecha'] }}
                                </strong>

                                <span>
                                    Demanda registrada
                                </span>

                            </div>

                            <div class="trend-value">

                                {{ number_format($dia['cantidad']) }}

                                <small>
                                    unidades
                                </small>

                            </div>

                        </div>

                    @endforeach

                </div>

            @else

                <div class="analysis-empty">

                    <i class="bi bi-bar-chart"></i>

                    <h3>
                        No hay datos
                    </h3>

                    <p>
                        No se encontraron ventas completadas
                        durante el período seleccionado.
                    </p>

                </div>

            @endif

        </div>


        {{-- INTERPRETACIÓN --}}
        <div class="analysis-section">

            <div class="section-header">

                <div>

                    <h2>
                        Interpretación
                    </h2>

                    <p>
                        Resumen del comportamiento detectado.
                    </p>

                </div>

            </div>


            <div class="analysis-insights">

                <div class="insight-item">

                    <div class="insight-icon">

                        <i class="bi bi-speedometer2"></i>

                    </div>

                    <div>

                        <strong>
                            Demanda promedio
                        </strong>

                        <p>

                            Se registró un promedio de

                            <b>
                                {{ number_format($demandaPromedio, 2) }}
                            </b>

                            unidades vendidas por día.

                        </p>

                    </div>

                </div>


                <div class="insight-item">

                    <div class="insight-icon">

                        <i class="bi bi-activity"></i>

                    </div>

                    <div>

                        <strong>
                            Tendencia {{ strtolower($tendencia) }}
                        </strong>

                        <p>

                            @if($tendenciaPorcentaje > 5)

                                La demanda presenta un
                                <b>incremento del {{ number_format($tendenciaPorcentaje, 1) }}%</b>
                                entre la primera y segunda mitad del período analizado.

                            @elseif($tendenciaPorcentaje < -5)

                                La demanda presenta una
                                <b>disminución del {{ number_format(abs($tendenciaPorcentaje), 1) }}%</b>
                                entre la primera y segunda mitad del período analizado.

                            @else

                                La demanda se mantiene
                                <b>relativamente estable</b>
                                entre la primera y segunda mitad del período analizado.

                            @endif

                        </p>

                    </div>

                </div>


                <div class="insight-item">

                    <div class="insight-icon">

                        <i class="bi bi-calendar-event"></i>

                    </div>

                    <div>

                        <strong>
                            Mayor actividad
                        </strong>

                        <p>

                            @if($fechaMayorDemanda)

                                El mayor nivel de demanda se registró
                                el

                                <b>
                                    {{ $fechaMayorDemanda }}
                                </b>

                                con

                                <b>
                                    {{ $cantidadMayorDemanda }}
                                </b>

                                unidades.

                            @else

                                No existen ventas registradas
                                para este período.

                            @endif

                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection


{{-- =========================================================
     CHART.JS
========================================================= --}}

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

    const datosDemanda = @json($datosGrafico);

    const etiquetas = datosDemanda.map(item => {

        const fecha = new Date(
            item.fecha + 'T00:00:00'
        );

        return fecha.toLocaleDateString(
            'es-PE',
            {
                day: '2-digit',
                month: 'short'
            }
        );

    });


    const valores = datosDemanda.map(
        item => item.cantidad
    );


    const canvas =
        document.getElementById('demandaChart');


    if (canvas) {

        new Chart(canvas, {

            type: 'line',

            data: {

                labels: etiquetas,

                datasets: [

                    {

                        label: 'Demanda',

                        data: valores,

                        borderWidth: 2,

                        pointRadius: 3,

                        pointHoverRadius: 5,

                        tension: 0.35,

                        fill: true

                    }

                ]

            },

            options: {

                responsive: true,

                maintainAspectRatio: false,

                interaction: {

                    intersect: false,

                    mode: 'index'

                },

                plugins: {

                    legend: {

                        display: false

                    },

                    tooltip: {

                        callbacks: {

                            label: function(context) {

                                return (
                                    ' Demanda: ' +
                                    context.parsed.y +
                                    ' unidades'
                                );

                            }

                        }

                    }

                },

                scales: {

                    y: {

                        beginAtZero: true,

                        ticks: {

                            precision: 0

                        }

                    },

                    x: {

                        ticks: {

                            maxTicksLimit: 12

                        }

                    }

                }

            }

        });

    }

</script>

@endpush