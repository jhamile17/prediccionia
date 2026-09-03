@extends('layouts.app')

@section('title', 'Análisis de demanda | PrediccionIA')

@push('styles')
    <link
        rel="stylesheet"
        href="{{ asset('css/analisis.css') }}"
    >
@endpush

@section('content')

@php

    $demandaTotal =
        (int) ($demandaTotal ?? 0);

    $demandaPromedio =
        (float) ($demandaPromedio ?? 0);

    $cantidadMayorDemanda =
        (int) ($cantidadMayorDemanda ?? 0);

    $tendenciaPorcentaje =
        (float) ($tendenciaPorcentaje ?? 0);

    $datosGrafico =
        collect($datosGrafico ?? []);

    $principalesDias =
        collect($principalesDias ?? []);

    $comportamientoSemanal =
        collect($comportamientoSemanal ?? []);

@endphp


<main class="analysis-page">


    {{-- =========================================================
         ENCABEZADO
    ========================================================== --}}

    <header class="analysis-header">

        <div class="analysis-header-content">

            <div class="analysis-tag">

                <span></span>

                ANÁLISIS DE DEMANDA

            </div>


            <h1>
                ¿Cómo se está comportando la demanda?
            </h1>


            <p>
                Observa la evolución de las ventas y descubre
                los patrones de mayor movimiento.
            </p>

        </div>


        <div class="analysis-period">

            <span>
                PERÍODO
            </span>

            <strong>
                {{ $nombrePeriodo }}
            </strong>

        </div>

    </header>


    {{-- =========================================================
         FILTROS
    ========================================================== --}}

    <section class="analysis-filters">

        <form
            method="GET"
            action="{{ route('analisis.index') }}"
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
                            {{ (int) $productoId === (int) $producto->id
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
                        {{ $periodo === '7'
                            ? 'selected'
                            : ''
                        }}
                    >
                        Últimos 7 días
                    </option>

                    <option
                        value="30"
                        {{ $periodo === '30'
                            ? 'selected'
                            : ''
                        }}
                    >
                        Últimos 30 días
                    </option>

                    <option
                        value="90"
                        {{ $periodo === '90'
                            ? 'selected'
                            : ''
                        }}
                    >
                        Últimos 90 días
                    </option>

                    <option
                        value="365"
                        {{ $periodo === '365'
                            ? 'selected'
                            : ''
                        }}
                    >
                        Último año
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="analysis-filter-button"
            >

                <i class="bi bi-sliders"></i>

                Aplicar

            </button>

        </form>


        @if($productoSeleccionado)

            <div class="analysis-selected-product">

                <i class="bi bi-cup-hot"></i>

                <span>
                    Analizando:
                </span>

                <strong>
                    {{ $productoSeleccionado->nombre }}
                </strong>

            </div>

        @endif

    </section>


    {{-- =========================================================
         INDICADORES
    ========================================================== --}}

    <section class="analysis-metrics">


        <article class="analysis-metric">

            <div class="analysis-metric-icon indigo">

                <i class="bi bi-activity"></i>

            </div>

            <div>

                <span>
                    Demanda total
                </span>

                <strong>
                    {{ number_format($demandaTotal) }}
                </strong>

                <small>
                    unidades
                </small>

            </div>

        </article>


        <article class="analysis-metric">

            <div class="analysis-metric-icon blue">

                <i class="bi bi-bar-chart"></i>

            </div>

            <div>

                <span>
                    Promedio diario
                </span>

                <strong>
                    {{ number_format($demandaPromedio, 1) }}
                </strong>

                <small>
                    unidades por día
                </small>

            </div>

        </article>


        <article class="analysis-metric">

            <div class="analysis-metric-icon cyan">

                <i class="bi bi-calendar2-check"></i>

            </div>

            <div>

                <span>
                    Mayor movimiento
                </span>

                <strong>
                    {{ $cantidadMayorDemanda > 0
                        ? number_format($cantidadMayorDemanda)
                        : '—'
                    }}
                </strong>

                <small>
                    {{ $fechaMayorDemanda ?? 'Sin datos' }}
                </small>

            </div>

        </article>


        <article class="analysis-metric trend">

            <div class="analysis-metric-icon violet">

                @if($tendencia === 'Creciente')

                    <i class="bi bi-arrow-up-right"></i>

                @elseif($tendencia === 'Decreciente')

                    <i class="bi bi-arrow-down-right"></i>

                @else

                    <i class="bi bi-arrow-right"></i>

                @endif

            </div>

            <div>

                <span>
                    Comportamiento
                </span>

                <strong>
                    {{ $tendencia }}
                </strong>

                <small>

                    {{ $tendenciaPorcentaje >= 0 ? '+' : '' }}
                    {{ number_format(
                        $tendenciaPorcentaje,
                        1
                    ) }}%

                </small>

            </div>

        </article>


    </section>


    {{-- =========================================================
         GRÁFICO PRINCIPAL
    ========================================================== --}}

    <section class="analysis-card evolution-card">

        <header class="analysis-card-header">

            <div>

                <span>
                    EVOLUCIÓN
                </span>

                <h2>
                    Movimiento de la demanda
                </h2>

                <p>
                    Así se ha comportado la cantidad de unidades
                    vendidas durante el período seleccionado.
                </p>

            </div>


            <div class="chart-legend">

                <span class="legend-line"></span>

                Demanda

            </div>

        </header>


        <div class="analysis-chart">

            <div class="chart-y-axis">

                @php

                    $maxGrafico =
                        max(
                            1,
                            (int) $datosGrafico->max(
                                fn ($dato) =>
                                    (int) $dato['cantidad']
                            )
                        );

                    $paso =
                        max(
                            1,
                            (int) ceil(
                                $maxGrafico / 4
                            )
                        );

                @endphp


                @for(
                    $valor = $paso * 4;
                    $valor >= 0;
                    $valor -= $paso
                )

                    <span>
                        {{ $valor }}
                    </span>

                @endfor

            </div>


            <div class="chart-area">

                <div class="chart-grid">

                    @for($i = 0; $i < 5; $i++)

                        <span></span>

                    @endfor

                </div>


                <div
                    class="chart-bars"
                    style="
                        --bar-count:
                        {{ max(
                            1,
                            $datosGrafico->count()
                        ) }};
                    "
                >

                    @foreach($datosGrafico as $dato)

                        @php

                            $cantidad =
                                (int) $dato['cantidad'];

                            $altura =
                                $maxGrafico > 0
                                    ? max(
                                        2,
                                        round(
                                            (
                                                $cantidad /
                                                $maxGrafico
                                            ) * 100
                                        )
                                    )
                                    : 2;

                            $fecha =
                                \Carbon\Carbon::parse(
                                    $dato['fecha']
                                );

                        @endphp


                        <div
                            class="chart-bar-wrapper"
                            title="{{ $fecha->format('d/m/Y') }}: {{ $cantidad }} unidades"
                        >

                            <span
                                class="chart-bar"
                                style="
                                    height:
                                    {{ $altura }}%;
                                "
                            ></span>

                        </div>

                    @endforeach

                </div>


                <div class="chart-x-axis">

                    @php

                        $cantidadDatos =
                            $datosGrafico->count();

                        $indices =
                            [
                                0,
                                (int) floor(
                                    ($cantidadDatos - 1) / 3
                                ),
                                (int) floor(
                                    (($cantidadDatos - 1) / 3) * 2
                                ),
                                max(
                                    0,
                                    $cantidadDatos - 1
                                ),
                            ];

                        $indices =
                            array_values(
                                array_unique($indices)
                            );

                    @endphp


                    @foreach($indices as $index)

                        @if(isset($datosGrafico[$index]))

                            <span>

                                {{
                                    \Carbon\Carbon::parse(
                                        $datosGrafico[$index]['fecha']
                                    )->format('d M')
                                }}

                            </span>

                        @endif

                    @endforeach

                </div>

            </div>

        </div>

    </section>


    {{-- =========================================================
         DOS COLUMNAS
    ========================================================== --}}

    <section class="analysis-two-columns">


        {{-- =====================================================
             DÍAS DESTACADOS
        ====================================================== --}}

        <article class="analysis-card">

            <header class="analysis-card-header">

                <div>

                    <span>
                        MOMENTOS DESTACADOS
                    </span>

                    <h2>
                        Días con mayor movimiento
                    </h2>

                </div>

                <div class="small-card-icon">

                    <i class="bi bi-calendar-event"></i>

                </div>

            </header>


            <div class="highlight-days">

                @forelse(
                    $principalesDias
                    as $indice => $dia
                )

                    <div class="highlight-day">

                        <div class="highlight-rank">

                            {{ str_pad(
                                $indice + 1,
                                2,
                                '0',
                                STR_PAD_LEFT
                            ) }}

                        </div>


                        <div class="highlight-day-content">

                            <strong>
                                {{ $dia['fecha'] }}
                            </strong>

                            <span>
                                Día de mayor movimiento
                            </span>

                        </div>


                        <div class="highlight-value">

                            <strong>
                                {{ number_format(
                                    $dia['cantidad']
                                ) }}
                            </strong>

                            <span>
                                unidades
                            </span>

                        </div>

                    </div>

                @empty

                    <div class="analysis-empty-small">

                        <i class="bi bi-calendar-x"></i>

                        No hay días con ventas
                        en este período.

                    </div>

                @endforelse

            </div>

        </article>


        {{-- =====================================================
             DÍA DE LA SEMANA
        ====================================================== --}}

        <article class="analysis-card">

            <header class="analysis-card-header">

                <div>

                    <span>
                        PATRÓN SEMANAL
                    </span>

                    <h2>
                        ¿Qué día tiene mayor movimiento?
                    </h2>

                </div>

                <div class="small-card-icon blue">

                    <i class="bi bi-calendar-week"></i>

                </div>

            </header>


            @php

                $maxPromedio =
                    max(
                        1,
                        (float) $comportamientoSemanal->max(
                            'promedio'
                        )
                    );

            @endphp


            <div class="weekday-list">

                @foreach(
                    $comportamientoSemanal
                    as $dia
                )

                    @php

                        $promedio =
                            (float) $dia['promedio'];

                        $width =
                            min(
                                100,
                                round(
                                    (
                                        $promedio /
                                        $maxPromedio
                                    ) * 100
                                )
                            );

                        $esMayor =
                            $diaMayorPromedio &&
                            $dia['dia'] ===
                            $diaMayorPromedio['dia'];

                    @endphp


                    <div class="weekday-row">

                        <div class="weekday-name">

                            <span>
                                {{ $dia['dia'] }}
                            </span>

                            @if($esMayor)

                                <i
                                    class="bi bi-star-fill"
                                    title="Mayor promedio"
                                ></i>

                            @endif

                        </div>


                        <div class="weekday-track">

                            <span
                                style="
                                    width:
                                    {{ $width }}%;
                                "
                            ></span>

                        </div>


                        <strong>
                            {{ number_format(
                                $promedio,
                                1
                            ) }}
                        </strong>

                    </div>

                @endforeach

            </div>

        </article>


    </section>


    {{-- =========================================================
         INTERPRETACIÓN
    ========================================================== --}}

    <section class="analysis-insight">

        <div class="analysis-insight-icon">

            @if($tendencia === 'Creciente')

                <i class="bi bi-arrow-up-right"></i>

            @elseif($tendencia === 'Decreciente')

                <i class="bi bi-arrow-down-right"></i>

            @else

                <i class="bi bi-lightbulb"></i>

            @endif

        </div>


        <div class="analysis-insight-content">

            <span>
                LECTURA DEL ANÁLISIS
            </span>


            <h2>

                @if($tendencia === 'Creciente')

                    La demanda muestra una tendencia creciente.

                @elseif($tendencia === 'Decreciente')

                    La demanda muestra una tendencia decreciente.

                @else

                    La demanda se mantiene relativamente estable.

                @endif

            </h2>


            <p>

                Durante {{ strtolower($nombrePeriodo) }},
                la demanda promedio fue de
                <strong>
                    {{ number_format(
                        $demandaPromedio,
                        1
                    ) }}
                    unidades por día
                </strong>.

                @if($fechaMayorDemanda)

                    El mayor movimiento se registró el
                    <strong>
                        {{ $fechaMayorDemanda }}
                    </strong>
                    con
                    <strong>
                        {{ number_format(
                            $cantidadMayorDemanda
                        ) }}
                        unidades
                    </strong>.

                @endif

                @if($diaMayorPromedio)

                    El día con mayor promedio de movimiento
                    fue el
                    <strong>
                        {{ $diaMayorPromedio['dia'] }}
                    </strong>.

                @endif

            </p>

        </div>

    </section>


    {{-- =========================================================
         NOTA
    ========================================================== --}}

    <div class="analysis-note">

        <i class="bi bi-info-circle"></i>

        <span>
            Este análisis describe el comportamiento de las ventas
            registradas durante el período seleccionado.
        </span>

    </div>


</main>

@endsection