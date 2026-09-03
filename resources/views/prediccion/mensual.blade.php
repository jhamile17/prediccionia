@extends('layouts.app')

@section('title', 'Demanda mensual | PrediccionIA')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/prediccion.css') }}">
@endpush

@section('content')

@php

    /*
    |--------------------------------------------------------------------------
    | DATOS
    |--------------------------------------------------------------------------
    */

    $predicciones = is_iterable($predicciones ?? null)
        ? collect($predicciones)
        : collect();


    /*
    |--------------------------------------------------------------------------
    | RESUMEN DEL MES
    |--------------------------------------------------------------------------
    */

    $totalProductos =
        $predicciones->count();

    $demandaTotal =
        (int) $predicciones->sum(
            fn ($producto) =>
                (int) ($producto['demanda_mensual'] ?? 0)
        );

    $productosConFaltante =
        $predicciones->filter(
            fn ($producto) =>
                (int) ($producto['faltante_estimado'] ?? 0) > 0
        )->count();

    $faltanteTotal =
        (int) $predicciones->sum(
            fn ($producto) =>
                (int) ($producto['faltante_estimado'] ?? 0)
        );


    /*
    |--------------------------------------------------------------------------
    | PRODUCTOS DE MAYOR DEMANDA
    |--------------------------------------------------------------------------
    */

    $productosMayorDemanda =
        $predicciones
            ->sortByDesc(
                fn ($producto) =>
                    (int) ($producto['demanda_mensual'] ?? 0)
            )
            ->take(5);


    /*
    |--------------------------------------------------------------------------
    | ESTADO GENERAL
    |--------------------------------------------------------------------------
    */

    $porcentajeCubierto = 0;

    $stockTotal =
        (int) $predicciones->sum(
            fn ($producto) =>
                (int) ($producto['stock_actual'] ?? 0)
        );

    if ($demandaTotal > 0) {

        $porcentajeCubierto =
            min(
                100,
                round(
                    ($stockTotal / $demandaTotal) * 100
                )
            );

    }

@endphp


<main class="prediction-page">


    {{-- =========================================================
         ENCABEZADO
    ========================================================== --}}

    <header class="prediction-hero">

        <div class="prediction-hero-content">

            <span class="prediction-eyebrow">
                PLANIFICACIÓN DE DEMANDA
            </span>

            <h1>
                Demanda mensual
            </h1>

            <p>
                Consulta cuánto se espera vender durante el mes
                y prepara tu inventario con anticipación.
            </p>

        </div>


        <div class="prediction-period">

            <span>
                PERÍODO
            </span>

            <strong>
                {{ ucfirst($nombreMes) }} {{ $anio }}
            </strong>

        </div>

    </header>



    {{-- =========================================================
         FILTROS
    ========================================================== --}}

    <section class="prediction-filter-card">

        <div class="filter-heading">

            <div>

                <span class="prediction-section-label">
                    CONSULTAR PERÍODO
                </span>

                <h2>
                    Selecciona el mes que quieres planificar
                </h2>

            </div>

        </div>


        <form
            action="{{ route('prediccion.mensual') }}"
            method="GET"
            class="prediction-filter-form"
        >

            <div class="prediction-filter-group">

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
                            {{ (int) $mes === $numero ? 'selected' : '' }}
                        >
                            {{ $nombre }}
                        </option>

                    @endforeach

                </select>

            </div>


            <div class="prediction-filter-group">

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
                            {{ (int) $anio === $year ? 'selected' : '' }}
                        >
                            {{ $year }}
                        </option>

                    @endfor

                </select>

            </div>


            <button
                type="submit"
                class="prediction-consult-button"
            >

                <i class="bi bi-arrow-clockwise"></i>

                Actualizar

            </button>

        </form>

    </section>



    {{-- =========================================================
         RESUMEN DEL MES
    ========================================================== --}}

    <section class="prediction-kpis">


        {{-- DEMANDA --}}

        <article class="prediction-kpi prediction-kpi-main">

            <div class="prediction-kpi-icon">
                <i class="bi bi-graph-up-arrow"></i>
            </div>

            <div>

                <span>
                    Demanda esperada
                </span>

                <strong>
                    {{ number_format($demandaTotal) }}
                </strong>

                <small>
                    unidades para el mes
                </small>

            </div>

        </article>


        {{-- PRODUCTOS --}}

        <article class="prediction-kpi">

            <div class="prediction-kpi-icon neutral">
                <i class="bi bi-box-seam"></i>
            </div>

            <div>

                <span>
                    Productos analizados
                </span>

                <strong>
                    {{ $totalProductos }}
                </strong>

                <small>
                    productos activos
                </small>

            </div>

        </article>


        {{-- FALTANTES --}}

        <article class="prediction-kpi prediction-kpi-warning">

            <div class="prediction-kpi-icon warning">
                <i class="bi bi-exclamation-circle"></i>
            </div>

            <div>

                <span>
                    Productos por preparar
                </span>

                <strong>
                    {{ $productosConFaltante }}
                </strong>

                <small>
                    presentan faltante
                </small>

            </div>

        </article>


        {{-- UNIDADES --}}

        <article class="prediction-kpi prediction-kpi-danger">

            <div class="prediction-kpi-icon danger">
                <i class="bi bi-box-arrow-in-down"></i>
            </div>

            <div>

                <span>
                    Unidades por cubrir
                </span>

                <strong>
                    {{ number_format($faltanteTotal) }}
                </strong>

                <small>
                    requieren reposición
                </small>

            </div>

        </article>


    </section>



    {{-- =========================================================
         RESUMEN VISUAL
    ========================================================== --}}

    <section class="prediction-overview">


        <div>

            <span class="prediction-section-label">
                RESUMEN DEL PERÍODO
            </span>

            <h2>
                ¿Cómo llego preparado a {{ ucfirst($nombreMes) }}?
            </h2>

            <p>
                Compara el stock disponible con la cantidad
                que se espera vender durante el mes.
            </p>

        </div>


        <div class="coverage-box">

            <div class="coverage-top">

                <span>
                    Cobertura estimada
                </span>

                <strong>
                    {{ $porcentajeCubierto }}%
                </strong>

            </div>


            <div class="coverage-track">

                <span
                    style="width: {{ $porcentajeCubierto }}%"
                ></span>

            </div>


            <div class="coverage-bottom">

                <span>
                    {{ number_format($stockTotal) }}
                    unidades disponibles
                </span>

                <span>
                    {{ number_format($demandaTotal) }}
                    esperadas
                </span>

            </div>

        </div>

    </section>



    {{-- =========================================================
         MAYOR DEMANDA
    ========================================================== --}}

    <section class="prediction-panel">

        <header class="prediction-panel-header">

            <div>

                <span class="prediction-section-label">
                    PRIORIDADES DEL MES
                </span>

                <h2>
                    Productos con mayor demanda esperada
                </h2>

                <p>
                    Te ayuda a identificar dónde tendrás mayor movimiento.
                </p>

            </div>

        </header>


        @if($productosMayorDemanda->count() > 0)


            @php

                $maxDemanda =
                    max(
                        1,
                        (int) $productosMayorDemanda->max(
                            fn ($producto) =>
                                (int) ($producto['demanda_mensual'] ?? 0)
                        )
                    );

            @endphp


            <div class="monthly-demand-list">

                @foreach($productosMayorDemanda as $indice => $producto)

                    @php

                        $demanda =
                            (int) ($producto['demanda_mensual'] ?? 0);

                        $stock =
                            (int) ($producto['stock_actual'] ?? 0);

                        $faltante =
                            (int) ($producto['faltante_estimado'] ?? 0);

                        $porcentaje =
                            min(
                                100,
                                round(
                                    ($demanda / $maxDemanda) * 100
                                )
                            );

                    @endphp


                    <div class="monthly-demand-row">


                        <div class="monthly-demand-rank">

                            {{ str_pad(
                                $indice + 1,
                                2,
                                '0',
                                STR_PAD_LEFT
                            ) }}

                        </div>


                        <div class="monthly-demand-info">

                            <div class="monthly-demand-top">

                                <strong>
                                    {{ $producto['producto'] ?? 'Producto' }}
                                </strong>

                                <span>
                                    {{ $demanda }}
                                    {{ $demanda === 1
                                        ? 'unidad'
                                        : 'unidades'
                                    }}
                                </span>

                            </div>


                            <div class="monthly-demand-track">

                                <span
                                    style="width: {{ $porcentaje }}%"
                                ></span>

                            </div>


                            <div class="monthly-demand-meta">

                                <span>
                                    Stock actual:
                                    <strong>{{ $stock }}</strong>
                                </span>

                                @if($faltante > 0)

                                    <span class="monthly-faltante">

                                        Faltan:
                                        <strong>{{ $faltante }}</strong>

                                    </span>

                                @else

                                    <span class="monthly-covered">

                                        Stock cubierto

                                    </span>

                                @endif

                            </div>

                        </div>


                    </div>

                @endforeach

            </div>


        @else


            <div class="prediction-empty">

                <div class="prediction-empty-icon">

                    <i class="bi bi-bar-chart"></i>

                </div>

                <h3>
                    No hay predicciones disponibles
                </h3>

                <p>
                    No se encontraron datos para el período seleccionado.
                </p>

            </div>


        @endif

    </section>



    {{-- =========================================================
         DETALLE
    ========================================================== --}}

    <section class="prediction-panel detail-panel">

        <header class="prediction-panel-header">

            <div>

                <span class="prediction-section-label">
                    DETALLE DE PLANIFICACIÓN
                </span>

                <h2>
                    Demanda y stock por producto
                </h2>

                <p>
                    Utiliza esta información para preparar las existencias
                    del período seleccionado.
                </p>

            </div>

        </header>


        @if($predicciones->count() > 0)


            <div class="prediction-table-wrapper">

                <table class="prediction-table">

                    <thead>

                        <tr>

                            <th>
                                Producto
                            </th>

                            <th>
                                Se esperan vender
                            </th>

                            <th>
                                Tienes
                            </th>

                            <th>
                                Te faltarían
                            </th>

                            <th>
                                Situación
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        @foreach($predicciones as $prediccion)

                            @php

                                $demanda =
                                    (int) (
                                        $prediccion['demanda_mensual']
                                        ?? 0
                                    );

                                $stock =
                                    (int) (
                                        $prediccion['stock_actual']
                                        ?? 0
                                    );

                                $faltante =
                                    (int) (
                                        $prediccion['faltante_estimado']
                                        ?? 0
                                    );

                            @endphp


                            <tr>


                                {{-- PRODUCTO --}}

                                <td>

                                    <div class="prediction-product">

                                        <div class="prediction-product-icon">

                                            <i class="bi bi-box-seam"></i>

                                        </div>

                                        <div>

                                            <strong>
                                                {{ $prediccion['producto'] }}
                                            </strong>

                                            <small>
                                                {{ $prediccion['mensaje'] ?? '' }}
                                            </small>

                                        </div>

                                    </div>

                                </td>



                                {{-- DEMANDA --}}

                                <td>

                                    <strong class="monthly-number">

                                        {{ $demanda }}

                                    </strong>

                                    <span class="monthly-unit">
                                        unidades
                                    </span>

                                </td>



                                {{-- STOCK --}}

                                <td>

                                    <strong class="monthly-stock">

                                        {{ $stock }}

                                    </strong>

                                    <span class="monthly-unit">
                                        disponibles
                                    </span>

                                </td>



                                {{-- FALTANTE --}}

                                <td>

                                    @if($faltante > 0)

                                        <strong class="monthly-shortage">
                                            {{ $faltante }}
                                        </strong>

                                        <span class="monthly-unit">
                                            unidades
                                        </span>

                                    @else

                                        <span class="monthly-no-shortage">

                                            <i class="bi bi-check-circle"></i>

                                            Cubierto

                                        </span>

                                    @endif

                                </td>



                                {{-- ESTADO --}}

                                <td>

                                    @if($faltante > 0)

                                        <span class="monthly-status danger">

                                            <i></i>

                                            Preparar reposición

                                        </span>

                                    @elseif($stock <=
                                        (int) ($prediccion['stock_minimo'] ?? 0)
                                    )

                                        <span class="monthly-status warning">

                                            <i></i>

                                            Vigilar

                                        </span>

                                    @else

                                        <span class="monthly-status success">

                                            <i></i>

                                            Cubierto

                                        </span>

                                    @endif

                                </td>


                            </tr>


                        @endforeach


                    </tbody>

                </table>

            </div>


        @else


            <div class="prediction-empty">

                <div class="prediction-empty-icon">

                    <i class="bi bi-calendar-x"></i>

                </div>

                <h3>
                    No hay productos para este período
                </h3>

                <p>
                    Selecciona otro mes o año para consultar la demanda.
                </p>

            </div>


        @endif

    </section>



    {{-- =========================================================
         NOTA
    ========================================================== --}}

    <div class="prediction-note">

        <i class="bi bi-info-circle"></i>

        <p>
            Las cantidades mostradas representan una
            <strong>estimación de la demanda</strong>.
            Úsalas como apoyo para planificar tus existencias
            y no como una cantidad obligatoria de compra.
        </p>

    </div>


</main>

@endsection