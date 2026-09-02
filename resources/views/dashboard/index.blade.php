@extends('layouts.app')

@section('title', 'Dashboard | PrediccionIA')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')

<main class="dashboard">

    {{-- =====================================================
         ENCABEZADO
    ====================================================== --}}

    <header class="dashboard-header">

        <h1>Dashboard PROCÁFES</h1>

        <p>
            Información clave para apoyar las decisiones de inventario.
        </p>

    </header>


    {{-- =====================================================
         PREDICCIÓN DE REPOSICIÓN
    ====================================================== --}}

    <section class="prediction-section">

        <div class="section-header">

            <h2>🤖 Predicción de reposición</h2>

            <p>
                La demanda estimada se compara con el stock disponible
                para identificar productos que requieren atención.
            </p>

        </div>


        {{-- =================================================
             RESUMEN
        ================================================== --}}

        <div class="resumen">

            {{-- Reposición inmediata --}}

            <div class="card inmediata">

                <div class="card-content">

                    <div class="card-icon">
                        🔴
                    </div>

                    <div>

                        <h3>
                            {{ $resumenReposicion['reposicion_inmediata'] }}
                        </h3>

                        <p>
                            Reponer ahora
                        </p>

                    </div>

                </div>

            </div>


            {{-- Revisar pronto --}}

            <div class="card pronta">

                <div class="card-content">

                    <div class="card-icon">
                        🟠
                    </div>

                    <div>

                        <h3>
                            {{ $resumenReposicion['reposicion_pronta'] }}
                        </h3>

                        <p>
                            Revisar pronto
                        </p>

                    </div>

                </div>

            </div>


            {{-- Stock suficiente --}}

            <div class="card suficiente">

                <div class="card-content">

                    <div class="card-icon">
                        🟢
                    </div>

                    <div>

                        <h3>
                            {{ $resumenReposicion['stock_suficiente'] }}
                        </h3>

                        <p>
                            Stock suficiente
                        </p>

                    </div>

                </div>

            </div>

        </div>


        {{-- =================================================
             PRODUCTOS QUE REQUIEREN ATENCIÓN
        ================================================== --}}

        <section class="estado">

            <div class="estado-header">

                <div>

                    <h2>
                        Productos que requieren atención
                    </h2>

                    <p class="estado-subtitulo">
                        Recomendaciones basadas en la demanda estimada.
                    </p>

                </div>

                <span class="badge-ia">
                    🤖 Análisis automático
                </span>

            </div>


            {{-- =====================================================
                 REPOSICIÓN INMEDIATA
            ====================================================== --}}

            @if(count($resumenReposicion['productos_criticos']) > 0)

                <div class="grupo-atencion">

                    <h3 class="grupo-titulo inmediato-titulo">
                        🔴 Reponer ahora
                    </h3>

                    <p class="grupo-descripcion">
                        Estos productos podrían no cubrir la demanda estimada.
                    </p>


                    @foreach($resumenReposicion['productos_criticos'] as $producto)

                        <article class="producto">

                            <div class="producto-info">

                                <div class="producto-nombre">
                                    🔴
                                    {{ $producto['producto'] }}
                                </div>

                                <p class="producto-mensaje">
                                    {{ $producto['mensaje'] }}
                                </p>


                                @if($producto['faltante_estimado'] > 0)

                                    <p class="producto-recomendacion">

                                        💡 Se recomienda reponer

                                        <strong>
                                            {{ $producto['faltante_estimado'] }}
                                        </strong>

                                        unidades.

                                    </p>

                                @endif

                            </div>


                            <div class="producto-datos">

                                <div class="dato">

                                    <span>
                                        Stock actual
                                    </span>

                                    <strong>
                                        {{ $producto['stock_actual'] }}
                                    </strong>

                                </div>


                                <div class="dato">

                                    <span>
                                        Demanda estimada
                                    </span>

                                    <strong>
                                        {{ $producto['demanda_predicha'] }}
                                    </strong>

                                </div>


                                <div class="dato">

                                    <span>
                                        Faltante
                                    </span>

                                    <strong>
                                        {{ $producto['faltante_estimado'] }}
                                    </strong>

                                </div>

                            </div>


                            <div class="producto-accion accion-inmediata">
                                Reponer ahora
                            </div>

                        </article>

                    @endforeach

                </div>

            @endif


            {{-- =====================================================
                 REVISAR PRONTO
            ====================================================== --}}

            @if(count($resumenReposicion['productos_por_revisar']) > 0)

                <div class="grupo-atencion grupo-pronto">

                    <h3 class="grupo-titulo pronto-titulo">
                        🟠 Revisar pronto
                    </h3>

                    <p class="grupo-descripcion">
                        Estos productos aún tienen stock, pero están cerca
                        del nivel mínimo.
                    </p>


                    @foreach($resumenReposicion['productos_por_revisar'] as $producto)

                        <article class="producto">

                            <div class="producto-info">

                                <div class="producto-nombre">
                                    🟠
                                    {{ $producto['producto'] }}
                                </div>

                                <p class="producto-mensaje">
                                    {{ $producto['mensaje'] }}
                                </p>

                            </div>


                            <div class="producto-datos">

                                <div class="dato">

                                    <span>
                                        Stock actual
                                    </span>

                                    <strong>
                                        {{ $producto['stock_actual'] }}
                                    </strong>

                                </div>


                                <div class="dato">

                                    <span>
                                        Demanda estimada
                                    </span>

                                    <strong>
                                        {{ $producto['demanda_predicha'] }}
                                    </strong>

                                </div>


                                <div class="dato">

                                    <span>
                                        Stock mínimo
                                    </span>

                                    <strong>
                                        {{ $producto['stock_minimo'] }}
                                    </strong>

                                </div>

                            </div>


                            <div class="producto-accion accion-pronta">
                                Revisar pronto
                            </div>

                        </article>

                    @endforeach

                </div>

            @endif


            {{-- =====================================================
                 TODO CORRECTO
            ====================================================== --}}

            @if(
                count($resumenReposicion['productos_criticos']) === 0 &&
                count($resumenReposicion['productos_por_revisar']) === 0
            )

                <div class="sin-alertas">

                    <div class="sin-alertas-icon">
                        🟢
                    </div>

                    <h3>
                        Inventario bajo control
                    </h3>

                    <p>
                        No se detectan productos que necesiten
                        reposición según la demanda estimada.
                    </p>

                </div>

            @endif

        </section>

    </section>

</main>

@endsection