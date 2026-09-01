<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | PROCÁFES</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f6f4f2;
            color: #2f2926;
        }

        .dashboard {
            width: min(1200px, calc(100% - 40px));
            margin: 0 auto;
            padding: 40px 0;
        }

        /* =====================================================
           ENCABEZADO
        ===================================================== */

        .dashboard-header {
            margin-bottom: 30px;
        }

        .dashboard-header h1 {
            margin: 0;
            color: #3e2723;
            font-size: 32px;
        }

        .dashboard-header p {
            margin: 8px 0 0;
            color: #756d69;
            font-size: 15px;
        }

        /* =====================================================
           PREDICCIÓN
        ===================================================== */
                /* =====================================================
        GRUPOS DE ATENCIÓN
        ===================================================== */

        .grupo-atencion {
            margin-bottom: 28px;
        }

        .grupo-atencion:last-child {
            margin-bottom: 0;
        }

        .grupo-titulo {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
        }

        .inmediato-titulo {
            color: #c62828;
        }

        .pronto-titulo {
            color: #ef6c00;
        }

        .grupo-descripcion {
            margin: 5px 0 15px;
            color: #756d69;
            font-size: 13px;
        }

        .grupo-pronto {
            padding-top: 20px;
            border-top: 1px solid #eee9e6;
        }


        /* =====================================================
        SUBTÍTULO DEL ESTADO
        ===================================================== */

        .estado-subtitulo {
            margin: 5px 0 0;
            color: #8a817c;
            font-size: 13px;
        }


        /* =====================================================
        RECOMENDACIÓN DE REPOSICIÓN
        ===================================================== */

        .producto-recomendacion {
            margin: 8px 0 0;
            color: #5d4037;
            font-size: 13px;
        }

        .producto-recomendacion strong {
            color: #c62828;
            font-size: 14px;
        }
        .prediction-section {
            margin-top: 25px;
        }

        .section-header {
            margin-bottom: 18px;
        }

        .section-header h2 {
            margin: 0;
            color: #3e2723;
            font-size: 22px;
        }

        .section-header p {
            margin: 6px 0 0;
            color: #756d69;
            font-size: 14px;
        }

        /* =====================================================
           TARJETAS
        ===================================================== */

        .resumen {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
        }

        .card {
            position: relative;
            background: #ffffff;
            padding: 22px;
            border-radius: 14px;
            border: 1px solid #ebe6e2;
            box-shadow: 0 3px 12px rgba(62, 39, 35, .06);
        }

        .card-content {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 22px;
        }

        .card h3 {
            margin: 0;
            font-size: 30px;
            line-height: 1;
        }

        .card p {
            margin: 7px 0 0;
            color: #756d69;
            font-size: 14px;
        }

        .inmediata {
            border-left: 4px solid #c62828;
        }

        .inmediata .card-icon {
            background: #ffebee;
        }

        .inmediata h3 {
            color: #c62828;
        }

        .pronta {
            border-left: 4px solid #ef6c00;
        }

        .pronta .card-icon {
            background: #fff3e0;
        }

        .pronta h3 {
            color: #ef6c00;
        }

        .suficiente {
            border-left: 4px solid #2e7d32;
        }

        .suficiente .card-icon {
            background: #e8f5e9;
        }

        .suficiente h3 {
            color: #2e7d32;
        }

        /* =====================================================
           ESTADO GENERAL
        ===================================================== */

        .estado {
            margin-top: 24px;
            background: #ffffff;
            border: 1px solid #ebe6e2;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 3px 12px rgba(62, 39, 35, .06);
        }

        .estado-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .estado-header h2 {
            margin: 0;
            color: #3e2723;
            font-size: 19px;
        }

        .badge-ia {
            padding: 6px 10px;
            border-radius: 20px;
            background: #f3eee9;
            color: #6d4c41;
            font-size: 12px;
            font-weight: bold;
        }

        /* =====================================================
           PRODUCTOS
        ===================================================== */

        .producto {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 18px 0;
            border-bottom: 1px solid #eee9e6;
        }

        .producto:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .producto:first-child {
            padding-top: 0;
        }

        .producto-info {
            flex: 1;
        }

        .producto-nombre {
            display: flex;
            align-items: center;
            gap: 9px;
            font-size: 16px;
            font-weight: bold;
            color: #3e2723;
        }

        .producto-mensaje {
            margin: 7px 0 0;
            color: #756d69;
            font-size: 13px;
        }

        .producto-datos {
            display: flex;
            gap: 25px;
            text-align: right;
        }

        .dato span {
            display: block;
            color: #8a817c;
            font-size: 11px;
            margin-bottom: 4px;
        }

        .dato strong {
            color: #3e2723;
            font-size: 15px;
        }

        .producto-accion {
            min-width: 135px;
            padding: 9px 14px;
            border-radius: 8px;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
        }

        .accion-inmediata {
            background: #ffebee;
            color: #c62828;
        }

        .accion-pronta {
            background: #fff3e0;
            color: #ef6c00;
        }

        /* =====================================================
           ESTADO SIN ALERTAS
        ===================================================== */

        .sin-alertas {
            text-align: center;
            padding: 35px 20px;
        }

        .sin-alertas-icon {
            width: 58px;
            height: 58px;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #e8f5e9;
            font-size: 27px;
        }

        .sin-alertas h3 {
            margin: 0;
            color: #2e7d32;
            font-size: 18px;
        }

        .sin-alertas p {
            max-width: 500px;
            margin: 8px auto 0;
            color: #756d69;
            font-size: 14px;
            line-height: 1.5;
        }
        .producto-recomendacion {
            margin: 8px 0 0;
            color: #5d4037;
            font-size: 13px;
        }

        .producto-recomendacion strong {
            color: #c62828;
        }
        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 800px) {

            .resumen {
                grid-template-columns: 1fr;
            }

            .producto {
                flex-direction: column;
                align-items: flex-start;
            }

            .producto-datos {
                width: 100%;
                text-align: left;
            }

            .producto-accion {
                width: 100%;
            }
        }

        @media (max-width: 500px) {

            .dashboard {
                width: min(100% - 24px, 1200px);
                padding: 25px 0;
            }

            .dashboard-header h1 {
                font-size: 26px;
            }

            .estado {
                padding: 18px;
            }

            .producto-datos {
                gap: 18px;
                flex-wrap: wrap;
            }
        }
    </style>
</head>

<body>

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
                    <h2>Productos que requieren atención</h2>

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

                                    <span>Stock actual</span>

                                    <strong>
                                        {{ $producto['stock_actual'] }}
                                    </strong>

                                </div>


                                <div class="dato">

                                    <span>Demanda estimada</span>

                                    <strong>
                                        {{ $producto['demanda_predicha'] }}
                                    </strong>

                                </div>


                                <div class="dato">

                                    <span>Faltante</span>

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

                                    <span>Stock actual</span>

                                    <strong>
                                        {{ $producto['stock_actual'] }}
                                    </strong>

                                </div>


                                <div class="dato">

                                    <span>Demanda estimada</span>

                                    <strong>
                                        {{ $producto['demanda_predicha'] }}
                                    </strong>

                                </div>


                                <div class="dato">

                                    <span>Stock mínimo</span>

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

</body>
</html>