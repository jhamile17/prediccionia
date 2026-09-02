@extends('layouts.app')

@section('title', $titulo . ' | PrediccionIA')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/reportes.css') }}">
@endpush

@section('content')

<div class="reports-page">

    {{-- ENCABEZADO --}}

    <div class="page-header">

        <div>

            <div class="page-kicker">
                REPORTE GENERADO
            </div>

            <h1>
                {{ $titulo }}
            </h1>

            <p>
                {{ $descripcion }}
            </p>

        </div>

        <div>
            <a
                href="{{ route('reportes.index') }}"
                class="btn btn-secondary"
            >
                <i class="bi bi-arrow-left"></i>
                Volver a reportes
            </a>
        </div>

    </div>


    {{-- RESUMEN --}}

    <div class="reports-summary">

        <div class="report-summary-card">

            <div class="report-summary-icon blue">
                <i class="bi bi-file-earmark-text"></i>
            </div>

            <div>
                <span>Tipo de reporte</span>

                <strong>
                    {{ ucfirst($tipo) }}
                </strong>

                <small>
                    reporte generado
                </small>
            </div>

        </div>


        <div class="report-summary-card">

            <div class="report-summary-icon green">
                <i class="bi bi-calendar3"></i>
            </div>

            <div>
                <span>Período</span>

                <strong>
                    {{ $periodo }}
                </strong>

                <small>
                    días analizados
                </small>
            </div>

        </div>


        <div class="report-summary-card">

            <div class="report-summary-icon purple">
                <i class="bi bi-database"></i>
            </div>

            <div>
                <span>Registros</span>

                <strong>
                    {{ number_format($totalRegistros) }}
                </strong>

                <small>
                    registros encontrados
                </small>
            </div>

        </div>


        <div class="report-summary-card">

            <div class="report-summary-icon orange">
                <i class="bi bi-clock-history"></i>
            </div>

            <div>
                <span>Generado</span>

                <strong>
                    {{ now()->format('d/m/Y') }}
                </strong>

                <small>
                    {{ now()->format('H:i') }}
                </small>
            </div>

        </div>

    </div>


    {{-- RESULTADO --}}

    <div class="reports-section">

        <div class="section-header">

            <div>

                <h2>
                    Datos del reporte
                </h2>

                <p>
                    Información obtenida directamente del sistema.
                </p>

            </div>

            <div class="reports-badge">
                <i class="bi bi-check-circle"></i>
                Datos disponibles
            </div>

        </div>


        @if($totalRegistros > 0)

            <div class="report-table-wrapper">

                {{-- PRODUCTOS --}}

                @if($tipo === 'productos')

                    <table class="report-table">

                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Costo</th>
                                <th>Stock</th>
                                <th>Estado</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($datos as $dato)

                                <tr>

                                    <td>
                                        <strong>
                                            {{ $dato->nombre }}
                                        </strong>
                                    </td>

                                    <td>
                                        {{ $dato->categoria ?? 'Sin categoría' }}
                                    </td>

                                    <td>
                                        S/ {{ number_format($dato->precio, 2) }}
                                    </td>

                                    <td>
                                        S/ {{ number_format($dato->costo, 2) }}
                                    </td>

                                    <td>
                                        {{ $dato->stock }}
                                    </td>

                                    <td>

                                        @if($dato->activo)
                                            <span class="status-badge status-active">
                                                Activo
                                            </span>
                                        @else
                                            <span class="status-badge status-inactive">
                                                Inactivo
                                            </span>
                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>


                {{-- INVENTARIO --}}

                @elseif($tipo === 'inventario')

                    <table class="report-table">

                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Stock actual</th>
                                <th>Stock mínimo</th>
                                <th>Stock seguridad</th>
                                <th>Estado</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($datos as $dato)

                                <tr>

                                    <td>
                                        <strong>
                                            {{ $dato->nombre }}
                                        </strong>
                                    </td>

                                    <td>
                                        {{ $dato->categoria ?? 'Sin categoría' }}
                                    </td>

                                    <td>
                                        {{ $dato->stock }}
                                    </td>

                                    <td>
                                        {{ $dato->stock_minimo }}
                                    </td>

                                    <td>
                                        {{ $dato->stock_seguridad }}
                                    </td>

                                    <td>

                                        @if($dato->stock <= $dato->stock_minimo)

                                            <span class="status-badge status-danger">
                                                Stock bajo
                                            </span>

                                        @else

                                            <span class="status-badge status-active">
                                                Disponible
                                            </span>

                                        @endif

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>


                {{-- VENTAS --}}

                @elseif($tipo === 'ventas')

                    <table class="report-table">

                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad vendida</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($datos as $dato)

                                <tr>

                                    <td>
                                        <strong>
                                            {{ $dato->nombre }}
                                        </strong>
                                    </td>

                                    <td>
                                        {{ number_format($dato->cantidad) }}
                                        unidades
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>


                {{-- DEMANDA --}}

                @elseif($tipo === 'demanda')

                    <table class="report-table">

                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Demanda</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($datos as $dato)

                                <tr>

                                    <td>
                                        {{ Carbon\Carbon::parse($dato->fecha)->locale('es')->translatedFormat('d \d\e F \d\e Y') }}
                                    </td>

                                    <td>
                                        <strong>
                                            {{ number_format($dato->cantidad) }}
                                        </strong>
                                        unidades
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>


                {{-- PREDICCIONES --}}

                @elseif($tipo === 'predicciones')

                    <table class="report-table">

                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Stock actual</th>
                                <th>Stock mínimo</th>
                                <th>Stock seguridad</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($datos as $dato)

                                <tr>

                                    <td>
                                        <strong>
                                            {{ $dato->nombre }}
                                        </strong>
                                    </td>

                                    <td>
                                        {{ $dato->stock }}
                                    </td>

                                    <td>
                                        {{ $dato->stock_minimo }}
                                    </td>

                                    <td>
                                        {{ $dato->stock_seguridad }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                @endif

            </div>

        @else

            <div class="analysis-empty">

                <i class="bi bi-file-earmark-x"></i>

                <h3>
                    No hay datos disponibles
                </h3>

                <p>
                    No se encontraron registros para los criterios
                    seleccionados.
                </p>

            </div>

        @endif

    </div>


    {{-- PREDICCIONES --}}

    @if($tipo === 'predicciones')

        <div class="reports-info">

            <div class="reports-info-icon">
                <i class="bi bi-cpu"></i>
            </div>

            <div>

                <strong>
                    Predicción mensual
                </strong>

                <p>
                    Para consultar la demanda estimada y las
                    recomendaciones de reposición, utiliza el módulo
                    de predicción mensual.
                </p>

                <a
                    href="{{ route('prediccion.mensual') }}"
                    class="report-link"
                >
                    Ir a predicción mensual
                    <i class="bi bi-arrow-right"></i>
                </a>

            </div>

        </div>

    @endif

</div>

@endsection