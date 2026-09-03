@extends('layouts.app')

@section('title', 'Análisis de ventas | PrediccionIA')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/analisis.css') }}">
@endpush

@section('content')

@php

    /*
    |--------------------------------------------------------------------------
    | DATOS
    |--------------------------------------------------------------------------
    */

    $demandaPromedio =
        (float) ($demandaPromedio ?? 0);

    $demandaTotal =
        (int) ($demandaTotal ?? 0);

    $fechaMayorDemanda =
        $fechaMayorDemanda ?? null;

    $cantidadMayorDemanda =
        (int) ($cantidadMayorDemanda ?? 0);

    $tendencia =
        $tendencia ?? 'Estable';

    $tendenciaPorcentaje =
        (float) ($tendenciaPorcentaje ?? 0);

    $datosGrafico =
        is_iterable($datosGrafico ?? null)
            ? collect($datosGrafico)->values()->all()
            : [];

    $principalesDias =
        is_iterable($principalesDias ?? null)
            ? collect($principalesDias)->values()->all()
            : [];

    /*
    |--------------------------------------------------------------------------
    | PRODUCTO
    |--------------------------------------------------------------------------
    */

    $nombreProducto =
        $productoSeleccionado?->nombre
        ?? 'Todos los productos';

@endphp


<main class="analysis-page">


    {{-- =========================================================
         ENCABEZADO
    ========================================================== --}}

    <header class="analysis-hero">

        <div class="analysis-hero-content">

            <span class="analysis-eyebrow">
                ANÁLISIS DE VENTAS
            </span>

            <h1>
                ¿Cómo se están comportando tus ventas?
            </h1>

            <p>
                Explora el comportamiento histórico de tus productos
                para identificar cambios y patrones de demanda.
            </p>

        </div>


        <div class="analysis-context">

            <span>
                PRODUCTO ANALIZADO
            </span>

            <strong>
                {{ $nombreProducto }}
            </strong>

            <small>
                {{ $nombrePeriodo ?? 'Período seleccionado' }}
            </small>

        </div>

    </header>



    {{-- =========================================================
         FILTROS
    ========================================================== --}}

    <section class="analysis-filter-card">

        <div class="analysis-filter-heading">

            <div>

                <span class="analysis-section-label">
                    CONSULTAR
                </span>

                <h2>
                    Selecciona qué quieres analizar
                </h2>

            </div>

        </div>


        <form
            action="{{ route('analisis.index') }}"
            method="GET"
            class="analysis-filter-form"
        >

            <div class="analysis-filter-group">

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
                            {{ (string) $productoId === (string) $producto->id
                                ? 'selected'
                                : ''
                            }}
                        >
                            {{ $producto->nombre }}
                        </option>

                    @endforeach

                </select>

            </div>


            <div class="analysis-filter-group">

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
                class="analysis-consult-button"
            >

                <i class="bi bi-bar-chart-line"></i>

                Analizar

            </button>

        </form>

    </section>



    {{-- =========================================================
         RESUMEN
    ========================================================== --}}

    <section class="analysis-kpis">


        {{-- PROMEDIO --}}

        <article class="analysis-kpi">

            <div class="analysis-kpi-icon blue">

                <i class="bi bi-graph-up"></i>

            </div>

            <div>

                <span>
                    Demanda promedio
                </span>

                <strong>
                    {{ number_format($demandaPromedio, 1) }}
                </strong>

                <small>
                    unidades por día
                </small>

            </div>

        </article>



        {{-- TOTAL --}}

        <article class="analysis-kpi">

            <div class="analysis-kpi-icon purple">

                <i class="bi bi-bar-chart"></i>

            </div>

            <div>

                <span>
                    Unidades vendidas
                </span>

                <strong>
                    {{ number_format($demandaTotal) }}
                </strong>

                <small>
                    durante el período
                </small>

            </div>

        </article>



        {{-- MAYOR DÍA --}}

        <article class="analysis-kpi">

            <div class="analysis-kpi-icon orange">

                <i class="bi bi-calendar2-week"></i>

            </div>

            <div>

                <span>
                    Día con mayor movimiento
                </span>

                <strong class="analysis-kpi-date">

                    {{ $fechaMayorDemanda ?: '—' }}

                </strong>

                <small>

                    @if($cantidadMayorDemanda > 0)

                        {{ number_format($cantidadMayorDemanda) }}
                        unidades vendidas

                    @else

                        Sin ventas registradas

                    @endif

                </small>

            </div>

        </article>



        {{-- TENDENCIA --}}

        <article class="analysis-kpi">

            <div class="analysis-kpi-icon green">

                <i class="bi bi-activity"></i>

            </div>

            <div>

                <span>
                    Tendencia
                </span>

                <strong
                    class="
                        {{ $tendenciaPorcentaje > 5
                            ? 'trend-up'
                            : ($tendenciaPorcentaje < -5
                                ? 'trend-down'
                                : 'trend-stable'
                            )
                        }}
                    "
                >
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

                    respecto al inicio

                </small>

            </div>

        </article>


    </section>



    {{-- =========================================================
         GRÁFICO PRINCIPAL
    ========================================================== --}}

    <section class="analysis-panel">

        <header class="analysis-panel-header">

            <div>

                <span class="analysis-section-label">
                    COMPORTAMIENTO
                </span>

                <h2>
                    Evolución de las ventas
                </h2>

                <p>

                    {{ $productoSeleccionado
                        ? 'Así ha variado la demanda de ' .
                          $productoSeleccionado->nombre .
                          ' durante el período seleccionado.'
                        : 'Así ha variado la demanda de todos los productos.'
                    }}

                </p>

            </div>


            <div class="analysis-data-badge">

                <i class="bi bi-database-check"></i>

                Datos registrados

            </div>

        </header>


        @if(count($datosGrafico) > 0)

            <div class="analysis-chart">

                <canvas id="demandaChart"></canvas>

            </div>

        @else

            <div class="analysis-empty">

                <div class="analysis-empty-icon">

                    <i class="bi bi-bar-chart"></i>

                </div>

                <h3>
                    Aún no hay ventas para mostrar
                </h3>

                <p>
                    Selecciona otro período o producto para consultar
                    el comportamiento de las ventas.
                </p>

            </div>

        @endif

    </section>



    {{-- =========================================================
         PARTE INFERIOR
    ========================================================== --}}

    <section class="analysis-grid">


        {{-- =====================================================
             DÍAS PRINCIPALES
        ====================================================== --}}

        <article class="analysis-panel">

            <header class="analysis-panel-header">

                <div>

                    <span class="analysis-section-label">
                        MAYOR MOVIMIENTO
                    </span>

                    <h2>
                        Días con más ventas
                    </h2>

                    <p>
                        Los días en los que se registró mayor movimiento.
                    </p>

                </div>

            </header>


            @if(count($principalesDias) > 0)

                <div class="analysis-days">

                    @foreach($principalesDias as $indice => $dia)

                        <div class="analysis-day">

                            <div class="analysis-day-number">

                                {{ str_pad(
                                    $indice + 1,
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                ) }}

                            </div>


                            <div class="analysis-day-info">

                                <strong>
                                    {{ $dia['fecha'] }}
                                </strong>

                                <span>
                                    Demanda registrada
                                </span>

                            </div>


                            <div class="analysis-day-value">

                                <strong>
                                    {{ number_format($dia['cantidad']) }}
                                </strong>

                                <span>
                                    unidades
                                </span>

                            </div>

                        </div>

                    @endforeach

                </div>

            @else

                <div class="analysis-empty compact">

                    <i class="bi bi-calendar-x"></i>

                    <h3>
                        Sin datos
                    </h3>

                    <p>
                        No hay ventas completadas durante este período.
                    </p>

                </div>

            @endif

        </article>



        {{-- =====================================================
             INTERPRETACIÓN
        ====================================================== --}}

        <article class="analysis-panel">

            <header class="analysis-panel-header">

                <div>

                    <span class="analysis-section-label">
                        RESUMEN
                    </span>

                    <h2>
                        ¿Qué está pasando?
                    </h2>

                    <p>
                        Una lectura sencilla de los datos registrados.
                    </p>

                </div>

            </header>


            <div class="analysis-insights">


                {{-- PROMEDIO --}}

                <div class="analysis-insight">

                    <div class="analysis-insight-icon">

                        <i class="bi bi-speedometer2"></i>

                    </div>

                    <div>

                        <strong>
                            Ritmo de ventas
                        </strong>

                        <p>

                            En promedio se venden

                            <b>
                                {{ number_format($demandaPromedio, 1) }}
                            </b>

                            unidades por día.

                        </p>

                    </div>

                </div>



                {{-- TENDENCIA --}}

                <div class="analysis-insight">

                    <div class="analysis-insight-icon">

                        <i class="bi bi-activity"></i>

                    </div>

                    <div>

                        <strong>
                            Evolución
                        </strong>

                        <p>

                            @if($tendenciaPorcentaje > 5)

                                Las ventas muestran un
                                <b>incremento</b>
                                durante la segunda mitad del período.

                            @elseif($tendenciaPorcentaje < -5)

                                Las ventas muestran una
                                <b>disminución</b>
                                durante la segunda mitad del período.

                            @else

                                Las ventas se mantienen
                                <b>relativamente estables</b>
                                durante el período analizado.

                            @endif

                        </p>

                    </div>

                </div>



                {{-- MAYOR ACTIVIDAD --}}

                <div class="analysis-insight">

                    <div class="analysis-insight-icon">

                        <i class="bi bi-calendar-event"></i>

                    </div>

                    <div>

                        <strong>
                            Mayor movimiento
                        </strong>

                        <p>

                            @if($fechaMayorDemanda)

                                El día

                                <b>
                                    {{ $fechaMayorDemanda }}
                                </b>

                                se registraron

                                <b>
                                    {{ number_format($cantidadMayorDemanda) }}
                                </b>

                                unidades vendidas.

                            @else

                                No hay suficiente información
                                para identificar un día de mayor movimiento.

                            @endif

                        </p>

                    </div>

                </div>


            </div>

        </article>


    </section>



    {{-- =========================================================
         NOTA
    ========================================================== --}}

    <div class="analysis-note">

        <i class="bi bi-lightbulb"></i>

        <p>

            Usa este análisis para conocer el comportamiento
            de tus ventas. La información mostrada corresponde
            a las ventas registradas en el período seleccionado.

        </p>

    </div>


</main>

@endsection


{{-- =========================================================
     CHART.JS
========================================================= --}}

@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const datosDemanda =
        @json($datosGrafico);

    const canvas =
        document.getElementById('demandaChart');


    if (!canvas || !datosDemanda.length) {
        return;
    }


    const etiquetas =
        datosDemanda.map(function (item) {

            const fecha =
                new Date(
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


    const valores =
        datosDemanda.map(function (item) {

            return Number(item.cantidad) || 0;

        });


    new Chart(canvas, {

        type: 'line',

        data: {

            labels: etiquetas,

            datasets: [

                {

                    label: 'Ventas',

                    data: valores,

                    borderWidth: 3,

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

                        label: function (context) {

                            return (
                                ' ' +
                                context.parsed.y +
                                ' unidades vendidas'
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

                    },

                    grid: {
                        drawBorder: false
                    }

                },

                x: {

                    ticks: {

                        maxTicksLimit: 12

                    },

                    grid: {
                        display: false
                    }

                }

            }

        }

    });

});

</script>

@endpush