@extends('layouts.app')

@section('title', 'Predicción de demanda | PrediccionIA')

@push('styles')
    <link
        rel="stylesheet"
        href="{{ asset('css/prediccion.css') }}"
    >
@endpush

@section('content')

@php

    /*
    |--------------------------------------------------------------------------
    | DATOS
    |--------------------------------------------------------------------------
    */

    $predicciones =
        is_iterable($predicciones ?? null)
            ? collect($predicciones)
            : collect();


    $nombreMes =
        $nombreMes ?? now()->translatedFormat('F');

    $anio =
        (int) ($anio ?? now()->year);

    $mes =
        (int) ($mes ?? now()->month);


    /*
    |--------------------------------------------------------------------------
    | INFORMACIÓN DEL PERÍODO
    |--------------------------------------------------------------------------
    */

    $inicioMes =
        \Carbon\Carbon::create(
            $anio,
            $mes,
            1
        );

    $diasMes =
        $inicioMes->daysInMonth;


    /*
    |--------------------------------------------------------------------------
    | RESUMEN
    |--------------------------------------------------------------------------
    */

    $totalProductos =
        $predicciones->count();


    $demandaTotal =
        (int) $predicciones->sum(
            fn ($producto) =>
                (int) (
                    $producto['demanda_mensual'] ?? 0
                )
        );


    $demandaPromedioDia =
        $diasMes > 0
            ? round(
                $demandaTotal / $diasMes,
                1
            )
            : 0;


    /*
    |--------------------------------------------------------------------------
    | PRODUCTO DE MAYOR DEMANDA
    |--------------------------------------------------------------------------
    */

    $productoMayorDemanda =
        $predicciones
            ->sortByDesc(
                fn ($producto) =>
                    (int) (
                        $producto['demanda_mensual'] ?? 0
                    )
            )
            ->first();


    $nombreMayorDemanda =
        $productoMayorDemanda['producto']
        ?? 'Sin datos';


    $cantidadMayorDemanda =
        (int) (
            $productoMayorDemanda['demanda_mensual']
            ?? 0
        );


    /*
    |--------------------------------------------------------------------------
    | TOP DE PRODUCTOS
    |--------------------------------------------------------------------------
    */

    $productosMayorDemanda =
        $predicciones
            ->sortByDesc(
                fn ($producto) =>
                    (int) (
                        $producto['demanda_mensual'] ?? 0
                    )
            )
            ->take(6)
            ->values();


    $maxDemanda =
        max(
            1,
            (int) $productosMayorDemanda->max(
                fn ($producto) =>
                    (int) (
                        $producto['demanda_mensual'] ?? 0
                    )
            )
        );


    /*
    |--------------------------------------------------------------------------
    | PARTICIPACIÓN DEL PRODUCTO MAYOR
    |--------------------------------------------------------------------------
    */

    $participacionMayor =
        $demandaTotal > 0
            ? round(
                (
                    $cantidadMayorDemanda /
                    $demandaTotal
                ) * 100
            )
            : 0;


    /*
    |--------------------------------------------------------------------------
    | PRIMER Y ÚLTIMO PRODUCTO
    |--------------------------------------------------------------------------
    */

    $productoMenorDemanda =
        $predicciones
            ->sortBy(
                fn ($producto) =>
                    (int) (
                        $producto['demanda_mensual'] ?? 0
                    )
            )
            ->first();


    $nombreMenorDemanda =
        $productoMenorDemanda['producto']
        ?? 'Sin datos';


    $cantidadMenorDemanda =
        (int) (
            $productoMenorDemanda['demanda_mensual']
            ?? 0
        );

@endphp


<main class="prediction-page">


    {{-- =========================================================
         ENCABEZADO
    ========================================================== --}}

    <header class="prediction-header">

        <div class="prediction-header-content">

            <div class="prediction-tag">
                <span class="prediction-tag-dot"></span>

                PREDICCIÓN DE DEMANDA
            </div>


            <h1>
                ¿Cuánto se espera vender?
            </h1>


            <p>
                Consulta la demanda estimada para el período
                seleccionado y conoce qué productos tendrán
                mayor movimiento.
            </p>

        </div>


        {{-- PERÍODO ACTUAL --}}

        <div class="prediction-period-card">

            <span>
                PERÍODO
            </span>

            <strong>
                {{ ucfirst($nombreMes) }}
            </strong>

            <small>
                {{ $anio }}
            </small>

        </div>

    </header>


    {{-- =========================================================
         SELECTOR DEL PERÍODO
    ========================================================== --}}

    <section class="prediction-period-selector">

        <div class="period-selector-heading">

            <div class="period-selector-icon">

                <i class="bi bi-calendar3"></i>

            </div>


            <div>

                <span>
                    CONSULTAR PERÍODO
                </span>

                <strong>
                    Elige el mes que quieres consultar
                </strong>

            </div>

        </div>


        <form
            action="{{ route('prediccion.mensual') }}"
            method="GET"
            class="prediction-period-form"
        >

            <div class="prediction-select-group">

                <label for="mes">
                    Mes
                </label>

                <select
                    name="mes"
                    id="mes"
                >

                    @foreach([
                        1 => 'Enero',
                        2 => 'Febrero',
                        3 => 'Marzo',
                        4 => 'Abril',
                        5 => 'Mayo',
                        6 => 'Junio',
                        7 => 'Julio',
                        8 => 'Agosto',
                        9 => 'Septiembre',
                        10 => 'Octubre',
                        11 => 'Noviembre',
                        12 => 'Diciembre'
                    ] as $numero => $nombre)

                        <option
                            value="{{ $numero }}"
                            {{ $mes === $numero
                                ? 'selected'
                                : ''
                            }}
                        >
                            {{ $nombre }}
                        </option>

                    @endforeach

                </select>

            </div>


            <div class="prediction-select-group">

                <label for="anio">
                    Año
                </label>

                <select
                    name="anio"
                    id="anio"
                >

                    @for(
                        $year = now()->year - 2;
                        $year <= now()->year + 2;
                        $year++
                    )

                        <option
                            value="{{ $year }}"
                            {{ $anio === $year
                                ? 'selected'
                                : ''
                            }}
                        >
                            {{ $year }}
                        </option>

                    @endfor

                </select>

            </div>


            <button
                type="submit"
                class="prediction-update-button"
            >

                <i class="bi bi-arrow-repeat"></i>

                Consultar

            </button>

        </form>

    </section>


    @if($predicciones->count() > 0)


        {{-- =====================================================
             RESUMEN
        ====================================================== --}}

        <section class="prediction-summary">


            {{-- DEMANDA TOTAL --}}

            <article class="prediction-summary-card primary">

                <div class="summary-icon">

                    <i class="bi bi-graph-up"></i>

                </div>

                <div class="summary-content">

                    <span>
                        Demanda estimada
                    </span>

                    <strong>
                        {{ number_format($demandaTotal) }}
                    </strong>

                    <small>
                        unidades en {{ ucfirst($nombreMes) }}
                    </small>

                </div>

            </article>


            {{-- PROMEDIO --}}

            <article class="prediction-summary-card">

                <div class="summary-icon blue">

                    <i class="bi bi-bar-chart-line"></i>

                </div>

                <div class="summary-content">

                    <span>
                        Promedio diario
                    </span>

                    <strong>
                        {{ number_format(
                            $demandaPromedioDia,
                            1
                        ) }}
                    </strong>

                    <small>
                        unidades por día
                    </small>

                </div>

            </article>


            {{-- MAYOR DEMANDA --}}

            <article class="prediction-summary-card">

                <div class="summary-icon cyan">

                    <i class="bi bi-arrow-up-right"></i>

                </div>

                <div class="summary-content">

                    <span>
                        Mayor demanda
                    </span>

                    <strong class="summary-product">
                        {{ $nombreMayorDemanda }}
                    </strong>

                    <small>
                        {{ number_format(
                            $cantidadMayorDemanda
                        ) }}
                        unidades estimadas
                    </small>

                </div>

            </article>


            {{-- PRODUCTOS --}}

            <article class="prediction-summary-card">

                <div class="summary-icon violet">

                    <i class="bi bi-grid"></i>

                </div>

                <div class="summary-content">

                    <span>
                        Productos incluidos
                    </span>

                    <strong>
                        {{ $totalProductos }}
                    </strong>

                    <small>
                        productos en la previsión
                    </small>

                </div>

            </article>

        </section>


        {{-- =====================================================
             BLOQUE PRINCIPAL
        ====================================================== --}}

        <section class="prediction-content-grid">


            {{-- =================================================
                 MAYOR MOVIMIENTO
            ================================================== --}}

            <article class="prediction-card demand-ranking">

                <header class="prediction-card-header">

                    <div>

                        <span>
                            MAYOR MOVIMIENTO
                        </span>

                        <h2>
                            Productos con mayor demanda
                        </h2>

                    </div>

                    <div class="prediction-card-icon">

                        <i class="bi bi-graph-up-arrow"></i>

                    </div>

                </header>


                <p class="prediction-card-description">
                    Estos productos concentran la mayor cantidad
                    de unidades que se espera vender durante
                    {{ strtolower($nombreMes) }}.
                </p>


                <div class="demand-ranking-list">

                    @foreach(
                        $productosMayorDemanda
                        as $indice => $producto
                    )

                        @php

                            $demandaProducto =
                                (int) (
                                    $producto['demanda_mensual']
                                    ?? 0
                                );

                            $barra =
                                min(
                                    100,
                                    round(
                                        (
                                            $demandaProducto /
                                            $maxDemanda
                                        ) * 100
                                    )
                                );

                            $participacion =
                                $demandaTotal > 0
                                    ? round(
                                        (
                                            $demandaProducto /
                                            $demandaTotal
                                        ) * 100
                                    )
                                    : 0;

                        @endphp


                        <div class="demand-ranking-item">


                            <div class="ranking-number">

                                {{ str_pad(
                                    $indice + 1,
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                ) }}

                            </div>


                            <div class="ranking-body">

                                <div class="ranking-top">

                                    <div>

                                        <strong>
                                            {{ $producto['producto']
                                                ?? 'Producto'
                                            }}
                                        </strong>

                                        <span>
                                            {{ $participacion }}%
                                            de la demanda total
                                        </span>

                                    </div>


                                    <strong class="ranking-value">

                                        {{ number_format(
                                            $demandaProducto
                                        ) }}

                                        <small>
                                            unidades
                                        </small>

                                    </strong>

                                </div>


                                <div class="ranking-track">

                                    <span
                                        style="
                                            width: {{ $barra }}%;
                                        "
                                    ></span>

                                </div>

                            </div>

                        </div>

                    @endforeach

                </div>

            </article>


            {{-- =================================================
                 LECTURA RÁPIDA
            ================================================== --}}

            <article class="prediction-card forecast-insight">

                <header class="prediction-card-header">

                    <div>

                        <span>
                            LECTURA RÁPIDA
                        </span>

                        <h2>
                            Lo más importante
                        </h2>

                    </div>

                    <div class="prediction-card-icon soft">

                        <i class="bi bi-lightning-charge"></i>

                    </div>

                </header>


                <div class="insight-main">

                    <div class="insight-badge">
                        <i class="bi bi-arrow-up"></i>
                        Mayor demanda
                    </div>

                    <strong>
                        {{ $nombreMayorDemanda }}
                    </strong>

                    <p>
                        Se esperan aproximadamente
                        <strong>
                            {{ number_format(
                                $cantidadMayorDemanda
                            ) }}
                        </strong>
                        unidades durante el mes.
                    </p>

                </div>


                <div class="insight-divider"></div>


                <div class="insight-stat">

                    <div class="insight-stat-icon">

                        <i class="bi bi-calendar2-week"></i>

                    </div>

                    <div>

                        <span>
                            Promedio diario
                        </span>

                        <strong>
                            {{ number_format(
                                $demandaPromedioDia,
                                1
                            ) }}
                            unidades
                        </strong>

                    </div>

                </div>


                <div class="insight-stat">

                    <div class="insight-stat-icon">

                        <i class="bi bi-boxes"></i>

                    </div>

                    <div>

                        <span>
                            Menor demanda
                        </span>

                        <strong>
                            {{ $nombreMenorDemanda }}
                        </strong>

                        <small>
                            {{ number_format(
                                $cantidadMenorDemanda
                            ) }}
                            unidades
                        </small>

                    </div>

                </div>


                <div class="insight-highlight">

                    <i class="bi bi-info-circle"></i>

                    <p>
                        {{ $nombreMayorDemanda }}
                        representa aproximadamente
                        <strong>
                            {{ $participacionMayor }}%
                        </strong>
                        de toda la demanda estimada del mes.
                    </p>

                </div>

            </article>


        </section>


        {{-- =====================================================
             DETALLE
        ====================================================== --}}

        <section class="prediction-card prediction-detail">

            <header class="prediction-card-header detail-header">

                <div>

                    <span>
                        DETALLE DE LA PREVISIÓN
                    </span>

                    <h2>
                        Demanda esperada por producto
                    </h2>

                    <p>
                        Consulta cuántas unidades se estima vender
                        de cada producto durante el período.
                    </p>

                </div>


                <div class="detail-period">

                    <i class="bi bi-calendar3"></i>

                    {{ ucfirst($nombreMes) }} {{ $anio }}

                </div>

            </header>


            <div class="prediction-table-wrapper">

                <table class="prediction-table">

                    <thead>

                        <tr>

                            <th>
                                Producto
                            </th>

                            <th>
                                Demanda estimada
                            </th>

                            <th>
                                Promedio diario
                            </th>

                            <th>
                                Participación
                            </th>

                            <th>
                                Nivel de demanda
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach(
                            $predicciones
                                ->sortByDesc(
                                    fn ($producto) =>
                                        (int) (
                                            $producto['demanda_mensual']
                                            ?? 0
                                        )
                                )
                            as $producto
                        )

                            @php

                                $demandaProducto =
                                    (int) (
                                        $producto['demanda_mensual']
                                        ?? 0
                                    );

                                $promedioProducto =
                                    $diasMes > 0
                                        ? round(
                                            $demandaProducto /
                                            $diasMes,
                                            1
                                        )
                                        : 0;

                                $participacionProducto =
                                    $demandaTotal > 0
                                        ? round(
                                            (
                                                $demandaProducto /
                                                $demandaTotal
                                            ) * 100
                                        )
                                        : 0;

                                /*
                                | Nivel simple y entendible.
                                */

                                $nivel =
                                    $participacionProducto >= 10
                                        ? 'Alta'
                                        : (
                                            $participacionProducto >= 7
                                                ? 'Media'
                                                : 'Baja'
                                        );

                                $nivelClase =
                                    match ($nivel) {
                                        'Alta' => 'high',
                                        'Media' => 'medium',
                                        default => 'low',
                                    };

                            @endphp


                            <tr>


                                {{-- PRODUCTO --}}

                                <td>

                                    <div class="prediction-product">

                                        <div class="prediction-product-icon">

                                            <i class="bi bi-cup-hot"></i>

                                        </div>

                                        <div>

                                            <strong>
                                                {{ $producto['producto']
                                                    ?? 'Producto'
                                                }}
                                            </strong>

                                            <span>
                                                Demanda estimada
                                            </span>

                                        </div>

                                    </div>

                                </td>


                                {{-- DEMANDA --}}

                                <td>

                                    <strong class="prediction-number">

                                        {{ number_format(
                                            $demandaProducto
                                        ) }}

                                    </strong>

                                    <span class="prediction-unit">
                                        unidades
                                    </span>

                                </td>


                                {{-- PROMEDIO --}}

                                <td>

                                    <strong class="prediction-average">

                                        {{ number_format(
                                            $promedioProducto,
                                            1
                                        ) }}

                                    </strong>

                                    <span class="prediction-unit">
                                        por día
                                    </span>

                                </td>


                                {{-- PARTICIPACIÓN --}}

                                <td>

                                    <div class="prediction-share">

                                        <div class="share-track">

                                            <span
                                                style="
                                                    width:
                                                    {{ min(
                                                        100,
                                                        $participacionProducto
                                                    ) }}%;
                                                "
                                            ></span>

                                        </div>

                                        <strong>
                                            {{ $participacionProducto }}%
                                        </strong>

                                    </div>

                                </td>


                                {{-- NIVEL --}}

                                <td>

                                    <span
                                        class="
                                            demand-level
                                            {{ $nivelClase }}
                                        "
                                    >

                                        <i></i>

                                        {{ $nivel }}

                                    </span>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </section>


        {{-- =====================================================
             NOTA
        ====================================================== --}}

        <div class="prediction-note">

            <div class="prediction-note-icon">

                <i class="bi bi-info-lg"></i>

            </div>

            <div>

                <strong>
                    Ten en cuenta
                </strong>

                <p>
                    Las cantidades mostradas son una estimación
                    de la demanda futura. Sirven como apoyo para
                    planificar, pero no representan una cantidad
                    obligatoria de venta.
                </p>

            </div>

        </div>


    @else


        {{-- =====================================================
             SIN DATOS
        ====================================================== --}}

        <section class="prediction-empty">

            <div class="prediction-empty-icon">

                <i class="bi bi-bar-chart"></i>

            </div>

            <h2>
                No hay información para este período
            </h2>

            <p>
                No encontramos productos con una previsión
                disponible para {{ ucfirst($nombreMes) }}
                {{ $anio }}.
            </p>

            <a
                href="{{ route('prediccion.mensual') }}"
            >
                <i class="bi bi-arrow-left"></i>
                Volver a consultar
            </a>

        </section>

    @endif


</main>

@endsection