@extends('layouts.app')

@section('title', $nombreReporte . ' | PrediccionIA')

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
                {{ $nombreReporte }}
            </h1>

            <p>
                {{ $nombrePeriodo }}
            </p>

        </div>

        <div style="display:flex; gap:10px;">

            <a
                href="{{ route('reportes.index') }}"
                class="btn btn-secondary"
            >
                <i class="bi bi-arrow-left"></i>
                Volver
            </a>

            <button
                type="button"
                class="btn btn-primary"
                onclick="window.print()"
            >
                <i class="bi bi-printer"></i>
                Imprimir
            </button>

        </div>

    </div>


    {{-- INFORMACIÓN DEL PERÍODO --}}

    <div class="reports-info">

        <div class="reports-info-icon">
            <i class="bi bi-calendar3"></i>
        </div>

        <div>

            <strong>
                Período del reporte
            </strong>

            <p>
                Desde
                {{ $fechaInicio->format('d/m/Y') }}
                hasta
                {{ $fechaFin->format('d/m/Y') }}
            </p>

        </div>

    </div>


    {{-- RESUMEN --}}

    @if(count($resumen) > 0)

        <div class="reports-summary">

            @foreach($resumen as $titulo => $valor)

                <div class="report-summary-card">

                    <div class="report-summary-icon blue">

                        <i class="bi bi-bar-chart"></i>

                    </div>

                    <div>

                        <span>
                            {{ $titulo }}
                        </span>

                        <strong>
                            {{ is_numeric($valor)
                                ? number_format($valor)
                                : $valor
                            }}
                        </strong>

                        <small>
                            Datos del reporte
                        </small>

                    </div>

                </div>

            @endforeach

        </div>

    @endif


    {{-- DATOS --}}

    <div class="reports-section">

        <div class="section-header">

            <div>

                <h2>
                    Información del reporte
                </h2>

                <p>
                    Datos obtenidos del sistema.
                </p>

            </div>

            <div class="reports-badge">

                <i class="bi bi-check-circle"></i>

                Reporte generado

            </div>

        </div>


        @if(count($datos) > 0)

            <div style="overflow-x:auto;">

                <table class="reports-table">

                    <thead>

                        <tr>

                            @foreach(array_keys($datos[0]) as $columna)

                                <th>
                                    {{ $columna }}
                                </th>

                            @endforeach

                        </tr>

                    </thead>

                    <tbody>

                        @foreach($datos as $fila)

                            <tr>

                                @foreach($fila as $valor)

                                    <td>
                                        {{ $valor }}
                                    </td>

                                @endforeach

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @else

            <div class="alerts-empty">

                <div class="alerts-empty-icon">

                    <i class="bi bi-file-earmark-x"></i>

                </div>

                <h3>
                    No hay datos disponibles
                </h3>

                <p>
                    No se encontraron registros para
                    el período seleccionado.
                </p>

            </div>

        @endif

    </div>

</div>

@endsection