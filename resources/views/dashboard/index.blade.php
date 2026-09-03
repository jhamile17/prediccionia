@extends('layouts.app')

@section('title', 'Dashboard | PrediccionIA')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')

@php

    /*
    |--------------------------------------------------------------------------
    | DATOS
    |--------------------------------------------------------------------------
    */

    $resumenReposicion =
        $dashboard['resumenReposicion'] ?? [];

    /*
    |--------------------------------------------------------------------------
    | INDICADORES
    |--------------------------------------------------------------------------
    */

    $reposicionInmediata =
        (int) ($resumenReposicion['reposicion_inmediata'] ?? 0);

    $reposicionPronta =
        (int) ($resumenReposicion['reposicion_pronta'] ?? 0);

    $stockSuficiente =
        (int) ($resumenReposicion['stock_suficiente'] ?? 0);

    $totalProductos =
        (int) ($resumenReposicion['total_productos'] ?? 0);

    /*
    |--------------------------------------------------------------------------
    | PRODUCTOS CRÍTICOS
    |--------------------------------------------------------------------------
    */

    $productosCriticos =
        $resumenReposicion['productos_criticos'] ?? [];

    if (!is_iterable($productosCriticos)) {
        $productosCriticos = [];
    }

    $productosCriticos =
        is_array($productosCriticos)
            ? $productosCriticos
            : iterator_to_array($productosCriticos);

    /*
    |--------------------------------------------------------------------------
    | PRODUCTOS PARA REVISAR
    |--------------------------------------------------------------------------
    */

    $productosPorRevisar =
        $resumenReposicion['productos_por_revisar'] ?? [];

    if (!is_iterable($productosPorRevisar)) {
        $productosPorRevisar = [];
    }

    $productosPorRevisar =
        is_array($productosPorRevisar)
            ? $productosPorRevisar
            : iterator_to_array($productosPorRevisar);

    /*
    |--------------------------------------------------------------------------
    | PRODUCTOS QUE NECESITAN ATENCIÓN
    |--------------------------------------------------------------------------
    */

    $productosAtencion = collect(
        array_merge(
            $productosCriticos,
            $productosPorRevisar
        )
    )->take(5);

    /*
    |--------------------------------------------------------------------------
    | TOTAL DE ATENCIÓN
    |--------------------------------------------------------------------------
    */

    $totalAtencion =
        $reposicionInmediata +
        $reposicionPronta;

    /*
    |--------------------------------------------------------------------------
    | SALUD DEL INVENTARIO
    |--------------------------------------------------------------------------
    */

    $porcentajeControl =
        $totalProductos > 0
            ? round(
                ($stockSuficiente / $totalProductos) * 100
            )
            : 0;

    $porcentajeControl =
        min(100, max(0, $porcentajeControl));

    /*
    |--------------------------------------------------------------------------
    | FECHA
    |--------------------------------------------------------------------------
    */

    $fechaActual =
        now()->translatedFormat('d \d\e F \d\e Y');

@endphp


<main class="dashboard">


    {{-- =========================================================
         ENCABEZADO
    ========================================================== --}}

    <section class="dashboard-hero">

        <div class="hero-text">

            <span class="hero-eyebrow">
                PROCÁFES · PANEL DE CONTROL
            </span>

            <h1>
                Buenos días, Administrador
                <span>👋</span>
            </h1>

            <p>
                Esto es lo más importante que debes revisar hoy.
            </p>

        </div>


        <div class="hero-info">

            <span>
                Actualizado
            </span>

            <strong>
                {{ $fechaActual }}
            </strong>

        </div>

    </section>



    {{-- =========================================================
         ESTADO GENERAL
    ========================================================== --}}

    <section class="summary-section">

        <div class="summary-heading">

            <div>

                <span class="section-eyebrow">
                    ESTADO GENERAL
                </span>

                <h2>
                    Tu inventario en un vistazo
                </h2>

            </div>


            @if($totalAtencion > 0)

                <div class="summary-status warning">

                    <i class="bi bi-exclamation-circle-fill"></i>

                    <span>
                        {{ $totalAtencion }}
                        {{ $totalAtencion === 1
                            ? 'producto necesita atención'
                            : 'productos necesitan atención'
                        }}
                    </span>

                </div>

            @else

                <div class="summary-status success">

                    <i class="bi bi-check-circle-fill"></i>

                    <span>
                        Inventario estable
                    </span>

                </div>

            @endif

        </div>



        <div class="summary-grid">


            {{-- REPOSICIÓN --}}

            <article class="summary-card danger">

                <div class="summary-card-top">

                    <div class="summary-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>

                    <span class="summary-label">
                        ACCIÓN
                    </span>

                </div>

                <strong class="summary-number">
                    {{ $reposicionInmediata }}
                </strong>

                <h3>
                    Necesitan reposición
                </h3>

                <p>
                    El stock no cubre la demanda esperada.
                </p>

            </article>



            {{-- REVISAR --}}

            <article class="summary-card warning">

                <div class="summary-card-top">

                    <div class="summary-icon">
                        <i class="bi bi-clock"></i>
                    </div>

                    <span class="summary-label">
                        VIGILAR
                    </span>

                </div>

                <strong class="summary-number">
                    {{ $reposicionPronta }}
                </strong>

                <h3>
                    Revisar pronto
                </h3>

                <p>
                    Están cerca del nivel mínimo.
                </p>

            </article>



            {{-- BAJO CONTROL --}}

            <article class="summary-card success">

                <div class="summary-card-top">

                    <div class="summary-icon">
                        <i class="bi bi-check2"></i>
                    </div>

                    <span class="summary-label">
                        ESTABLE
                    </span>

                </div>

                <strong class="summary-number">
                    {{ $stockSuficiente }}
                </strong>

                <h3>
                    Bajo control
                </h3>

                <p>
                    Tienen stock suficiente.
                </p>

            </article>


        </div>

    </section>



    {{-- =========================================================
         CENTRO DE DECISIONES
    ========================================================== --}}

    <section class="decision-layout">


        {{-- =====================================================
             QUÉ HACER HOY
        ====================================================== --}}

        <article class="decision-card">

            <header class="decision-header">

                <div>

                    <span class="section-eyebrow">
                        ⚡ QUÉ HACER HOY
                    </span>

                    <h2>
                        Productos que necesitan atención
                    </h2>

                    <p>
                        Empieza por estos productos para reducir
                        el riesgo de quedarte sin stock.
                    </p>

                </div>


                @if($totalAtencion > 0)

                    <span class="decision-total">

                        {{ $totalAtencion }}

                        {{ $totalAtencion === 1
                            ? 'pendiente'
                            : 'pendientes'
                        }}

                    </span>

                @else

                    <span class="decision-ok">

                        <i class="bi bi-check-circle-fill"></i>

                        Todo bien

                    </span>

                @endif

            </header>



            @if($productosAtencion->count() > 0)


                <div class="decision-list">

                    @foreach($productosAtencion as $indice => $producto)

                        @php

                            $nivel =
                                $producto['nivel'] ?? 'pronto';

                            $esCritico =
                                $nivel === 'inmediata';

                            $stock =
                                (int) (
                                    $producto['stock_actual'] ?? 0
                                );

                            $demanda =
                                (int) (
                                    $producto['demanda_predicha'] ?? 0
                                );

                            $faltante =
                                (int) (
                                    $producto['faltante_estimado'] ?? 0
                                );

                            $minimo =
                                (int) (
                                    $producto['stock_minimo'] ?? 0
                                );

                        @endphp


                        <div class="decision-item">


                            <div class="decision-number
                                {{ $esCritico ? 'danger' : 'warning' }}">

                                {{ str_pad(
                                    $indice + 1,
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                ) }}

                            </div>



                            <div class="decision-product">

                                <div class="decision-product-title">

                                    <strong>
                                        {{ $producto['producto'] ?? 'Producto' }}
                                    </strong>

                                    @if($esCritico)

                                        <span class="pill-danger">

                                            <i></i>

                                            Reponer ahora

                                        </span>

                                    @else

                                        <span class="pill-warning">

                                            <i></i>

                                            Revisar pronto

                                        </span>

                                    @endif

                                </div>


                                <p>

                                    @if($esCritico)

                                        Tu stock no alcanza para cubrir
                                        la demanda esperada.

                                    @else

                                        Tu stock está cerca del nivel mínimo.

                                    @endif

                                </p>

                            </div>



                            <div class="decision-data">


                                <div>

                                    <span>
                                        Tienes
                                    </span>

                                    <strong>
                                        {{ $stock }}
                                    </strong>

                                    <small>
                                        unidades
                                    </small>

                                </div>


                                <div>

                                    <span>
                                        Se esperan vender
                                    </span>

                                    <strong>
                                        {{ $demanda }}
                                    </strong>

                                    <small>
                                        unidades
                                    </small>

                                </div>


                                @if($esCritico)

                                    <div class="decision-shortage">

                                        <span>
                                            Te faltarían
                                        </span>

                                        <strong>
                                            {{ $faltante }}
                                        </strong>

                                        <small>
                                            unidades
                                        </small>

                                    </div>

                                @else

                                    <div>

                                        <span>
                                            Nivel mínimo
                                        </span>

                                        <strong>
                                            {{ $minimo }}
                                        </strong>

                                        <small>
                                            unidades
                                        </small>

                                    </div>

                                @endif


                            </div>



                            <a
                                href="{{ route('inventario.index') }}"
                                class="decision-button
                                    {{ $esCritico
                                        ? 'button-danger'
                                        : 'button-warning'
                                    }}"
                            >

                                {{ $esCritico
                                    ? 'Reponer'
                                    : 'Revisar'
                                }}

                                <i class="bi bi-arrow-right"></i>

                            </a>


                        </div>

                    @endforeach

                </div>


            @else


                <div class="empty-decision">

                    <div class="empty-decision-icon">

                        <i class="bi bi-check-lg"></i>

                    </div>

                    <h3>
                        Todo está bajo control
                    </h3>

                    <p>
                        No necesitas reponer productos por ahora.
                    </p>

                </div>


            @endif


        </article>



        {{-- =====================================================
             RECOMENDACIÓN
        ====================================================== --}}

        <aside class="recommendation-card">

            <div class="recommendation-orb">

                <i class="bi bi-lightbulb"></i>

            </div>


            <span class="recommendation-label">
                RECOMENDACIÓN
            </span>


            @if($reposicionInmediata > 0)


                <h2>
                    Prioriza los productos en rojo.
                </h2>

                <p>
                    Hay
                    <strong>
                        {{ $reposicionInmediata }}
                    </strong>

                    {{ $reposicionInmediata === 1
                        ? 'producto'
                        : 'productos'
                    }}

                    cuyo stock no cubre la demanda esperada.
                </p>


                <div class="recommendation-box danger">

                    <i class="bi bi-arrow-up-right"></i>

                    <div>

                        <strong>
                            Qué hacer
                        </strong>

                        <span>
                            Revisa primero estos productos
                            y registra la reposición.
                        </span>

                    </div>

                </div>


                <a
                    href="{{ route('inventario.index') }}"
                    class="recommendation-button"
                >

                    Ir a inventario

                    <i class="bi bi-arrow-right"></i>

                </a>


            @elseif($reposicionPronta > 0)


                <h2>
                    Mantén estos productos vigilados.
                </h2>

                <p>
                    Hay
                    <strong>
                        {{ $reposicionPronta }}
                    </strong>

                    {{ $reposicionPronta === 1
                        ? 'producto'
                        : 'productos'
                    }}

                    cerca de su nivel mínimo.
                </p>


                <div class="recommendation-box warning">

                    <i class="bi bi-clock"></i>

                    <div>

                        <strong>
                            Qué hacer
                        </strong>

                        <span>
                            Revisa su stock antes de que
                            necesiten reposición.
                        </span>

                    </div>

                </div>


                <a
                    href="{{ route('inventario.index') }}"
                    class="recommendation-button"
                >

                    Revisar inventario

                    <i class="bi bi-arrow-right"></i>

                </a>


            @else


                <h2>
                    Puedes continuar con normalidad.
                </h2>

                <p>
                    No se identifican productos que
                    necesiten una acción inmediata.
                </p>


                <div class="recommendation-box success">

                    <i class="bi bi-check-circle"></i>

                    <div>

                        <strong>
                            Inventario estable
                        </strong>

                        <span>
                            El stock disponible está bajo control.
                        </span>

                    </div>

                </div>


            @endif


        </aside>


    </section>



    {{-- =========================================================
         SALUD DEL INVENTARIO
    ========================================================== --}}

    <section class="health-card">


        <div class="health-content">

            <span class="section-eyebrow">
                SALUD DEL INVENTARIO
            </span>

            <h2>
                {{ $porcentajeControl }}% bajo control
            </h2>

            <p>
                {{ $stockSuficiente }}
                de
                {{ $totalProductos }}
                productos tienen stock suficiente.
            </p>

        </div>


        <div class="health-progress">

            <div class="health-top">

                <span>
                    Estado actual
                </span>

                <strong>
                    {{ $porcentajeControl }}%
                </strong>

            </div>


            <div class="health-track">

                <span
                    style="width: {{ $porcentajeControl }}%"
                ></span>

            </div>


            <div class="health-legend">

                <span>
                    <i class="green"></i>
                    {{ $stockSuficiente }}
                    bajo control
                </span>

                <span>
                    <i class="red"></i>
                    {{ $reposicionInmediata }}
                    necesitan reposición
                </span>

                <span>
                    <i class="orange"></i>
                    {{ $reposicionPronta }}
                    por revisar
                </span>

            </div>

        </div>


    </section>


</main>

@endsection