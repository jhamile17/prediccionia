@extends('layouts.app')

@section('title', 'Predicción mensual | PrediccionIA')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/prediccion.css') }}">
@endpush

@section('content')

<div class="prediction-page">

    {{-- =========================================================
         ENCABEZADO
    ========================================================== --}}

    <div class="page-header">

        <div>
            <div class="page-kicker">
                INTELIGENCIA ARTIFICIAL
            </div>

            <h1>Predicción mensual</h1>

            <p>
                Consulta la demanda estimada y planifica la reposición
                de inventario.
            </p>
        </div>

    </div>


    {{-- =========================================================
         FILTROS
    ========================================================== --}}

    <div class="prediction-filters">

        <form
            action="{{ route('prediccion.mensual') }}"
            method="GET"
            class="prediction-filter-form"
        >

            <div class="filter-group">

                <label for="mes">
                    Mes
                </label>

                <select name="mes" id="mes">

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
                            {{ $mes == $numero ? 'selected' : '' }}
                        >
                            {{ $nombre }}
                        </option>

                    @endforeach

                </select>

            </div>


            <div class="filter-group">

                <label for="anio">
                    Año
                </label>

                <select name="anio" id="anio">

                    @for($year = now()->year - 2; $year <= now()->year + 2; $year++)

                        <option
                            value="{{ $year }}"
                            {{ $anio == $year ? 'selected' : '' }}
                        >
                            {{ $year }}
                        </option>

                    @endfor

                </select>

            </div>


            <button
                type="submit"
                class="btn btn-primary"
            >
                <i class="bi bi-search"></i>
                Consultar
            </button>

        </form>

    </div>


    {{-- =========================================================
         RESUMEN
    ========================================================== --}}

    @php

        $totalProductos = count($predicciones);

        $inmediatas = collect($predicciones)
            ->where('nivel', 'inmediata')
            ->count();

        $prontas = collect($predicciones)
            ->where('nivel', 'pronto')
            ->count();

        $suficientes = collect($predicciones)
            ->where('nivel', 'suficiente')
            ->count();

        $demandaTotal = collect($predicciones)
            ->sum('demanda_mensual');

        $faltanteTotal = collect($predicciones)
            ->sum('faltante_estimado');

    @endphp


    <div class="prediction-summary">

        <div class="prediction-card">

            <div class="prediction-card-icon">
                <i class="bi bi-box-seam"></i>
            </div>

            <div>
                <span>Productos analizados</span>
                <strong>{{ $totalProductos }}</strong>
            </div>

        </div>


        <div class="prediction-card prediction-card-danger">

            <div class="prediction-card-icon">
                <i class="bi bi-exclamation-circle"></i>
            </div>

            <div>
                <span>Reponer ahora</span>
                <strong>{{ $inmediatas }}</strong>
            </div>

        </div>


        <div class="prediction-card prediction-card-warning">

            <div class="prediction-card-icon">
                <i class="bi bi-clock"></i>
            </div>

            <div>
                <span>Revisar pronto</span>
                <strong>{{ $prontas }}</strong>
            </div>

        </div>


        <div class="prediction-card prediction-card-success">

            <div class="prediction-card-icon">
                <i class="bi bi-check-circle"></i>
            </div>

            <div>
                <span>Stock suficiente</span>
                <strong>{{ $suficientes }}</strong>
            </div>

        </div>

    </div>


    {{-- =========================================================
         INFORMACIÓN DEL MES
    ========================================================== --}}

    <div class="prediction-section">

        <div class="section-header">

            <div>
                <h2>
                    Predicción de {{ ucfirst($nombreMes) }} {{ $anio }}
                </h2>

                <p>
                    Demanda estimada por producto para el período seleccionado.
                </p>
            </div>

            <div class="prediction-badge">
                <i class="bi bi-robot"></i>
                Análisis automático
            </div>

        </div>


        @if(count($predicciones) > 0)

            <div class="prediction-table-wrapper">

                <table class="prediction-table">

                    <thead>

                        <tr>
                            <th>Producto</th>
                            <th>Stock actual</th>
                            <th>Stock mínimo</th>
                            <th>Demanda mensual</th>
                            <th>Faltante estimado</th>
                            <th>Estado</th>
                        </tr>

                    </thead>

                    <tbody>

                        @foreach($predicciones as $prediccion)

                            <tr>

                                <td>

                                    <div class="product-name">

                                        <div class="product-icon">
                                            <i class="bi bi-box"></i>
                                        </div>

                                        <div>
                                            <strong>
                                                {{ $prediccion['producto'] }}
                                            </strong>

                                            <small>
                                                {{ $prediccion['mensaje'] }}
                                            </small>
                                        </div>

                                    </div>

                                </td>


                                <td>
                                    <strong>
                                        {{ $prediccion['stock_actual'] }}
                                    </strong>
                                </td>


                                <td>
                                    {{ $prediccion['stock_minimo'] }}
                                </td>


                                <td>

                                    <span class="demand-value">
                                        {{ $prediccion['demanda_mensual'] }}
                                    </span>

                                </td>


                                <td>

                                    @if($prediccion['faltante_estimado'] > 0)

                                        <span class="shortage-value">
                                            {{ $prediccion['faltante_estimado'] }}
                                        </span>

                                    @else

                                        <span class="shortage-ok">
                                            0
                                        </span>

                                    @endif

                                </td>


                                <td>

                                    @if($prediccion['nivel'] === 'inmediata')

                                        <span class="status-badge status-danger">
                                            <i class="bi bi-circle-fill"></i>
                                            Reponer ahora
                                        </span>

                                    @elseif($prediccion['nivel'] === 'pronto')

                                        <span class="status-badge status-warning">
                                            <i class="bi bi-circle-fill"></i>
                                            Revisar pronto
                                        </span>

                                    @else

                                        <span class="status-badge status-success">
                                            <i class="bi bi-circle-fill"></i>
                                            Stock suficiente
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

                <div class="empty-icon">
                    <i class="bi bi-robot"></i>
                </div>

                <h3>
                    No hay predicciones disponibles
                </h3>

                <p>
                    No se encontraron productos activos para el período seleccionado.
                </p>

            </div>

        @endif

    </div>


    {{-- =========================================================
         RESUMEN DE DEMANDA
    ========================================================== --}}

    @if(count($predicciones) > 0)

        <div class="prediction-bottom-grid">

            <div class="prediction-info-card">

                <span>
                    Demanda total estimada
                </span>

                <strong>
                    {{ $demandaTotal }}
                </strong>

                <small>
                    unidades para {{ ucfirst($nombreMes) }}
                </small>

            </div>


            <div class="prediction-info-card">

                <span>
                    Faltante total estimado
                </span>

                <strong>
                    {{ $faltanteTotal }}
                </strong>

                <small>
                    unidades que podrían requerir reposición
                </small>

            </div>

        </div>

    @endif

</div>

@endsection