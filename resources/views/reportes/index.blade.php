@extends('layouts.app')

@section('title', 'Reportes | PrediccionIA')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/reportes.css') }}">
@endpush

@section('content')

<div class="reports-page">

    {{-- =========================================================
         MENSAJE DE ÉXITO
    ========================================================== --}}

    @if(session('success'))

        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>

            <span>
                {{ session('success') }}
            </span>
        </div>

    @endif


    {{-- =========================================================
         ENCABEZADO
    ========================================================== --}}

    <div class="page-header">

        <div>

            <div class="page-kicker">
                INFORMACIÓN DEL SISTEMA
            </div>

            <h1>
                Reportes
            </h1>

            <p>
                Consulta y genera reportes relacionados con productos,
                inventario, ventas y predicciones.
            </p>

        </div>

    </div>


    {{-- =========================================================
         RESUMEN
    ========================================================== --}}

    <div class="reports-summary">


        {{-- =====================================================
             REPORTES DISPONIBLES
        ====================================================== --}}

        <div class="report-summary-card">

            <div class="report-summary-icon blue">

                <i class="bi bi-file-earmark-text"></i>

            </div>

            <div>

                <span>
                    Reportes disponibles
                </span>

                <strong>
                    {{ $totalReportes }}
                </strong>

                <small>
                    tipos de reportes
                </small>

            </div>

        </div>


        {{-- =====================================================
             INVENTARIO
        ====================================================== --}}

        <div class="report-summary-card">

            <div class="report-summary-icon green">

                <i class="bi bi-box-seam"></i>

            </div>

            <div>

                <span>
                    Inventario
                </span>

                <strong>
                    {{ $totalProductos }}
                </strong>

                <small>
                    productos registrados
                </small>

            </div>

        </div>


        {{-- =====================================================
             PREDICCIONES
        ====================================================== --}}

        <div class="report-summary-card">

            <div class="report-summary-icon purple">

                <i class="bi bi-graph-up"></i>

            </div>

            <div>

                <span>
                    Predicciones
                </span>

                <strong>
                    {{ $totalPredicciones }}
                </strong>

                <small>
                    productos analizables
                </small>

            </div>

        </div>


        {{-- =====================================================
             ÚLTIMO REPORTE
        ====================================================== --}}

        <div class="report-summary-card">

            <div class="report-summary-icon orange">

                <i class="bi bi-calendar-check"></i>

            </div>

            <div>

                <span>
                    Último reporte
                </span>

                <strong>
                    {{ $ultimoReporte ?? 'Sin generar' }}
                </strong>

                <small>
                    fecha de generación
                </small>

            </div>

        </div>


    </div>


    {{-- =========================================================
         GENERAR REPORTE
    ========================================================== --}}

    <div class="reports-section">

        <div class="reports-generator">


            {{-- CABECERA --}}

            <div class="generator-header">

                <div>

                    <h2>
                        Generar reporte
                    </h2>

                    <p>
                        Selecciona el tipo y período del reporte.
                    </p>

                </div>


                <div class="generator-icon">

                    <i class="bi bi-sliders"></i>

                </div>

            </div>


            {{-- FORMULARIO --}}

            <form
                action="{{ route('reportes.generar') }}"
                method="GET"
                class="report-form"
            >


                {{-- =================================================
                     TIPO DE REPORTE
                ================================================== --}}

                <div class="filter-group">

                    <label for="tipo">
                        Tipo de reporte
                    </label>

                    <select
                        name="tipo"
                        id="tipo"
                        required
                    >

                        <option value="">
                            Seleccionar reporte
                        </option>


                        <option
                            value="inventario"
                            {{ ($tipo ?? request('tipo')) === 'inventario' ? 'selected' : '' }}
                        >
                            Reporte de inventario
                        </option>


                        <option
                            value="productos"
                            {{ ($tipo ?? request('tipo')) === 'productos' ? 'selected' : '' }}
                        >
                            Reporte de productos
                        </option>


                        <option
                            value="predicciones"
                            {{ ($tipo ?? request('tipo')) === 'predicciones' ? 'selected' : '' }}
                        >
                            Reporte de predicciones
                        </option>


                        <option
                            value="ventas"
                            {{ ($tipo ?? request('tipo')) === 'ventas' ? 'selected' : '' }}
                        >
                            Reporte de ventas
                        </option>


                        <option
                            value="demanda"
                            {{ ($tipo ?? request('tipo')) === 'demanda' ? 'selected' : '' }}
                        >
                            Reporte de demanda
                        </option>


                        <option
                            value="alertas"
                            {{ ($tipo ?? request('tipo')) === 'alertas' ? 'selected' : '' }}
                        >
                            Reporte de alertas
                        </option>

                    </select>

                </div>


                {{-- =================================================
                     PERÍODO
                ================================================== --}}

                <div class="filter-group">

                    <label for="periodo">
                        Período
                    </label>

                    <select
                        name="periodo"
                        id="periodo"
                        required
                    >

                        <option value="">
                            Seleccionar período
                        </option>


                        <option
                            value="7"
                            {{ ($periodo ?? '30') === '7' ? 'selected' : '' }}
                        >
                            Últimos 7 días
                        </option>


                        <option
                            value="30"
                            {{ ($periodo ?? '30') === '30' ? 'selected' : '' }}
                        >
                            Últimos 30 días
                        </option>


                        <option
                            value="90"
                            {{ ($periodo ?? '30') === '90' ? 'selected' : '' }}
                        >
                            Últimos 90 días
                        </option>


                        <option
                            value="365"
                            {{ ($periodo ?? '30') === '365' ? 'selected' : '' }}
                        >
                            Último año
                        </option>

                    </select>

                </div>


                {{-- =================================================
                     BOTÓN
                ================================================== --}}

                <button
                    type="submit"
                    class="btn btn-primary"
                >

                    <i class="bi bi-file-earmark-arrow-down"></i>

                    Generar reporte

                </button>

            </form>

        </div>

    </div>


    {{-- =========================================================
         REPORTES DISPONIBLES
    ========================================================== --}}

    <div class="reports-section">


        {{-- CABECERA --}}

        <div class="section-header">

            <div>

                <h2>
                    Reportes disponibles
                </h2>

                <p>
                    Accede rápidamente a los diferentes informes
                    del sistema.
                </p>

            </div>


            <div class="reports-badge">

                <i class="bi bi-check-circle"></i>

                Sistema disponible

            </div>

        </div>


        {{-- =====================================================
             GRID
        ====================================================== --}}

        <div class="reports-grid">


            {{-- =================================================
                 INVENTARIO
            ================================================== --}}

            <div class="report-item">

                <div class="report-item-icon blue">

                    <i class="bi bi-clipboard-data"></i>

                </div>


                <div class="report-item-content">

                    <h3>
                        Reporte de inventario
                    </h3>

                    <p>
                        Consulta el estado actual del inventario,
                        stock disponible y productos con niveles bajos.
                    </p>

                    <a
                        href="{{ route('inventario.index') }}"
                        class="report-link"
                    >
                        Ver inventario

                        <i class="bi bi-arrow-right"></i>
                    </a>

                </div>

            </div>


            {{-- =================================================
                 PRODUCTOS
            ================================================== --}}

            <div class="report-item">

                <div class="report-item-icon green">

                    <i class="bi bi-box-seam"></i>

                </div>


                <div class="report-item-content">

                    <h3>
                        Reporte de productos
                    </h3>

                    <p>
                        Consulta información general de los productos
                        registrados en el sistema.
                    </p>

                    <a
                        href="{{ route('productos.index') }}"
                        class="report-link"
                    >
                        Ver productos

                        <i class="bi bi-arrow-right"></i>
                    </a>

                </div>

            </div>


            {{-- =================================================
                 PREDICCIONES
            ================================================== --}}

            <div class="report-item">

                <div class="report-item-icon purple">

                    <i class="bi bi-cpu"></i>

                </div>


                <div class="report-item-content">

                    <h3>
                        Reporte de predicciones
                    </h3>

                    <p>
                        Consulta las predicciones mensuales y
                        recomendaciones de reposición.
                    </p>

                    <a
                        href="{{ route('prediccion.mensual') }}"
                        class="report-link"
                    >
                        Ver predicciones

                        <i class="bi bi-arrow-right"></i>
                    </a>

                </div>

            </div>


            {{-- =================================================
                 VENTAS
            ================================================== --}}

            <div class="report-item">

                <div class="report-item-icon orange">

                    <i class="bi bi-cart-check"></i>

                </div>


                <div class="report-item-content">

                    <h3>
                        Reporte de ventas
                    </h3>

                    <p>
                        Consulta el comportamiento de las ventas
                        registradas durante un período.
                    </p>

                    <a
                        href="{{ route('reportes.generar', [
                            'tipo' => 'ventas',
                            'periodo' => 30
                        ]) }}"
                        class="report-link"
                    >
                        Generar reporte

                        <i class="bi bi-arrow-right"></i>
                    </a>

                </div>

            </div>


            {{-- =================================================
                 DEMANDA
            ================================================== --}}

            <div class="report-item">

                <div class="report-item-icon blue">

                    <i class="bi bi-bar-chart-line"></i>

                </div>


                <div class="report-item-content">

                    <h3>
                        Reporte de demanda
                    </h3>

                    <p>
                        Analiza el comportamiento histórico y las
                        principales tendencias de la demanda.
                    </p>

                    <a
                        href="{{ route('analisis.index') }}"
                        class="report-link"
                    >
                        Ver análisis

                        <i class="bi bi-arrow-right"></i>
                    </a>

                </div>

            </div>


            {{-- =================================================
                 ALERTAS
            ================================================== --}}

            <div class="report-item">

                <div class="report-item-icon red">

                    <i class="bi bi-exclamation-triangle"></i>

                </div>


                <div class="report-item-content">

                    <h3>
                        Reporte de alertas
                    </h3>

                    <p>
                        Consulta productos con stock crítico y
                        situaciones que requieren atención.
                    </p>

                    <a
                        href="{{ route('alertas.index') }}"
                        class="report-link"
                    >
                        Ver alertas

                        <i class="bi bi-arrow-right"></i>
                    </a>

                </div>

            </div>


        </div>

    </div>


    {{-- =========================================================
         INFORMACIÓN
    ========================================================== --}}

    <div class="reports-info">

        <div class="reports-info-icon">

            <i class="bi bi-info-circle"></i>

        </div>


        <div>

            <strong>
                Generación de reportes
            </strong>

            <p>
                Selecciona un tipo de reporte y un período para
                generar el informe correspondiente utilizando
                la información disponible en el sistema.
            </p>

        </div>

    </div>


</div>

@endsection