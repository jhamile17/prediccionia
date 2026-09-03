@extends('layouts.app')

@section('title', 'Ventas | PrediccionIA')

@push('styles')
    <link
        rel="stylesheet"
        href="{{ asset('css/ventas.css') }}"
    >
@endpush

@section('content')

@php

    $ventasDelDia =
        (int) ($resumen['ventas_hoy'] ?? 0);

    $unidadesDelDia =
        (int) ($resumen['unidades_hoy'] ?? 0);

    $ingresosDelDia =
        (float) ($resumen['ingresos_hoy'] ?? 0);

    $productoMasVendido =
        $resumen['producto_mas_vendido']
        ?? null;

    $cantidadProductoMasVendido =
        (int) (
            $resumen['cantidad_producto_mas_vendido']
            ?? 0
        );

    $esHoy =
        (bool) ($resumen['es_hoy'] ?? true);

    $fechaTexto =
        $resumen['fecha_texto']
        ?? 'Hoy';

    $fechaSeleccionada =
        $resumen['fecha_seleccionada']
        ?? now()->toDateString();

@endphp


<main class="sales-page">


    {{-- =========================================================
         ENCABEZADO
    ========================================================== --}}

    <header class="sales-hero">

        <div>

            <span class="sales-eyebrow">
                VENTAS
            </span>

            <h1>
                ¿Qué se ha vendido?
            </h1>

            <p>
                Consulta las ventas registradas y revisa cómo
                se están moviendo tus productos.
            </p>

        </div>

        <div>
            <a
                href="{{ route('ventas.create') }}"
                class="sales-new-button"
            >
                <i class="bi bi-plus-lg"></i>
                Nueva venta
            </a>
        </div>

    </header>


    {{-- =========================================================
         CONSULTA POR FECHA
    ========================================================== --}}

    <section class="sales-date-panel">

        <div class="sales-date-info">

            <div class="sales-date-icon">

                <i class="bi bi-calendar3"></i>

            </div>

            <div>

                <span>
                    CONSULTAR VENTAS
                </span>

                <strong>
                    {{ $fechaTexto }}
                </strong>

            </div>

        </div>


        <form
            method="GET"
            action="{{ route('ventas.index') }}"
            class="sales-date-form"
        >

            <label for="fecha">
                Fecha
            </label>

            <input
                type="date"
                name="fecha"
                id="fecha"
                value="{{ $fechaSeleccionada }}"
                max="{{ now()->toDateString() }}"
            >

            <button
                type="submit"
            >

                <i class="bi bi-search"></i>

                Consultar

            </button>

        </form>

    </section>


    {{-- =========================================================
         RESUMEN
    ========================================================== --}}

    <section class="sales-summary">


        {{-- VENTAS --}}

        <article class="sales-summary-card">

            <div class="sales-summary-icon brown">

                <i class="bi bi-receipt"></i>

            </div>

            <div class="sales-summary-content">

                <span>
                    Ventas {{ $esHoy ? 'de hoy' : 'del día' }}
                </span>

                <strong>
                    {{ number_format($ventasDelDia) }}
                </strong>

                <small>
                    Operaciones registradas
                </small>

            </div>

        </article>


        {{-- UNIDADES --}}

        <article class="sales-summary-card">

            <div class="sales-summary-icon green">

                <i class="bi bi-box-seam"></i>

            </div>

            <div class="sales-summary-content">

                <span>
                    Unidades vendidas
                </span>

                <strong>
                    {{ number_format($unidadesDelDia) }}
                </strong>

                <small>
                    {{ $esHoy ? 'Durante hoy' : 'Durante el día seleccionado' }}
                </small>

            </div>

        </article>


        {{-- INGRESOS --}}

        <article class="sales-summary-card">

            <div class="sales-summary-icon purple">

                <i class="bi bi-cash-stack"></i>

            </div>

            <div class="sales-summary-content">

                <span>
                    Ingresos {{ $esHoy ? 'de hoy' : 'del día' }}
                </span>

                <strong>
                    S/ {{ number_format($ingresosDelDia, 2) }}
                </strong>

                <small>
                    Total registrado
                </small>

            </div>

        </article>


        {{-- PRODUCTO MÁS VENDIDO --}}

        <article class="sales-summary-card highlight">

            <div class="sales-summary-icon orange">

                <i class="bi bi-star-fill"></i>

            </div>

            <div class="sales-summary-content">

                <span>
                    Más vendido {{ $esHoy ? 'hoy' : 'ese día' }}
                </span>


                @if($productoMasVendido)

                    <strong class="product-highlight">

                        {{ $productoMasVendido }}

                    </strong>

                    <small>

                        {{ number_format(
                            $cantidadProductoMasVendido
                        ) }}

                        {{
                            $cantidadProductoMasVendido === 1
                                ? 'unidad'
                                : 'unidades'
                        }}

                    </small>

                @else

                    <strong class="product-highlight empty">

                        Sin ventas

                    </strong>

                    <small>
                        No hubo ventas registradas
                    </small>

                @endif

            </div>

        </article>


    </section>


    {{-- =========================================================
         VENTAS
    ========================================================== --}}

    <section class="sales-panel">


        <header class="sales-panel-header">

            <div>

                <span class="sales-section-label">
                    ACTIVIDAD {{ $esHoy ? 'DE HOY' : 'DEL DÍA' }}
                </span>

                <h2>
                    Ventas registradas
                </h2>

                <p>

                    @if($esHoy)

                        Estas son las ventas registradas durante hoy.

                    @else

                        Estas son las ventas registradas el
                        {{ $fechaTexto }}.

                    @endif

                </p>

            </div>


            {{-- BUSCADOR --}}

            <div class="sales-search">

                <i class="bi bi-search"></i>

                <input
                    type="text"
                    id="salesSearch"
                    placeholder="Buscar producto..."
                    autocomplete="off"
                >

            </div>

        </header>


        {{-- =====================================================
             TABLA
        ====================================================== --}}

        <div class="sales-table-wrapper">

            <table class="sales-table">

                <thead>

                    <tr>

                        <th>
                            Hora
                        </th>

                        <th>
                            Venta
                        </th>

                        <th>
                            Productos
                        </th>

                        <th>
                            Total
                        </th>

                        <th>
                            Estado
                        </th>

                    </tr>

                </thead>


                <tbody id="salesTableBody">


                    @forelse($ventas as $venta)


                        @php

                            $detalles =
                                $venta->detalles;

                            $productosTexto =
                                $detalles
                                    ->map(
                                        fn ($detalle) =>
                                            $detalle->producto?->nombre
                                            ?? 'Producto eliminado'
                                    )
                                    ->implode(', ');

                            $cantidadProductos =
                                (int) $detalles->sum(
                                    'cantidad'
                                );

                        @endphp


                        <tr
                            data-search="{{ strtolower(
                                $productosTexto .
                                ' ' .
                                $venta->id
                            ) }}"
                        >


                            {{-- HORA --}}

                            <td>

                                <div class="sale-date">

                                    <strong>

                                        {{ $venta->fecha?->format(
                                            'H:i'
                                        ) }}

                                    </strong>

                                    <span>
                                        {{ $venta->fecha?->format(
                                            'd/m/Y'
                                        ) }}
                                    </span>

                                </div>

                            </td>


                            {{-- VENTA --}}

                            <td>

                                <div class="sale-number">

                                    <div class="sale-number-icon">

                                        <i class="bi bi-receipt"></i>

                                    </div>

                                    <div>

                                        <strong>
                                            Venta #{{ $venta->id }}
                                        </strong>

                                        @if($venta->usuario)

                                            <span>
                                                {{ $venta->usuario->name ?? 'Administrador' }}
                                            </span>

                                        @endif

                                    </div>

                                </div>

                            </td>


                            {{-- PRODUCTOS --}}

                            <td>

                                <div class="sale-products">

                                    <strong>

                                        {{ $cantidadProductos }}

                                        {{
                                            $cantidadProductos === 1
                                                ? 'unidad'
                                                : 'unidades'
                                        }}

                                    </strong>

                                    <span>
                                        {{ $productosTexto }}
                                    </span>

                                </div>

                            </td>


                            {{-- TOTAL --}}

                            <td>

                                <strong class="sale-total">

                                    S/
                                    {{ number_format(
                                        (float) $venta->total,
                                        2
                                    ) }}

                                </strong>

                            </td>


                            {{-- ESTADO --}}

                            <td>

                                @if(
                                    in_array(
                                        strtolower(
                                            $venta->estado
                                        ),
                                        [
                                            'completada',
                                            'completado',
                                            'finalizada',
                                            'pagada'
                                        ]
                                    )
                                )

                                    <span class="sale-status success">

                                        <i></i>

                                        Completada

                                    </span>

                                @elseif(
                                    in_array(
                                        strtolower(
                                            $venta->estado
                                        ),
                                        [
                                            'cancelada',
                                            'anulada'
                                        ]
                                    )
                                )

                                    <span class="sale-status danger">

                                        <i></i>

                                        Cancelada

                                    </span>

                                @else

                                    <span class="sale-status neutral">

                                        <i></i>

                                        {{ ucfirst(
                                            $venta->estado
                                        ) }}

                                    </span>

                                @endif

                            </td>

                        </tr>


                    @empty


                        <tr>

                            <td
                                colspan="5"
                                class="sales-empty"
                            >

                                <div class="sales-empty-icon">

                                    <i class="bi bi-calendar-x"></i>

                                </div>

                                <strong>
                                    No hubo ventas este día
                                </strong>

                                <span>
                                    Prueba seleccionando otra fecha.
                                </span>

                            </td>

                        </tr>


                    @endforelse


                </tbody>

            </table>

        </div>


        {{-- SIN RESULTADOS DE BÚSQUEDA --}}

        <div
            id="salesNoResults"
            class="sales-no-results"
            style="display: none;"
        >

            <div class="sales-empty-icon">

                <i class="bi bi-search"></i>

            </div>

            <strong>
                No encontramos esa venta
            </strong>

            <span>
                Prueba buscando otro producto.
            </span>

        </div>


    </section>


    {{-- =========================================================
         PIE
    ========================================================== --}}

    <div class="sales-footer-note">

        <i class="bi bi-info-circle"></i>

        <span>
            Selecciona otra fecha para consultar el historial de ventas.
        </span>

    </div>


</main>


{{-- =========================================================
     JAVASCRIPT
========================================================= --}}

@push('scripts')

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const searchInput =
            document.getElementById(
                'salesSearch'
            );

        const tableBody =
            document.getElementById(
                'salesTableBody'
            );

        const noResults =
            document.getElementById(
                'salesNoResults'
            );


        if (
            !searchInput ||
            !tableBody
        ) {

            return;

        }


        searchInput.addEventListener(
            'input',
            function () {

                const search =
                    this.value
                        .toLowerCase()
                        .trim();


                const rows =
                    tableBody.querySelectorAll(
                        'tr[data-search]'
                    );


                let visibles = 0;


                rows.forEach(
                    function (row) {

                        const text =
                            row.dataset.search
                            || '';


                        const mostrar =
                            text.includes(
                                search
                            );


                        row.style.display =
                            mostrar
                                ? ''
                                : 'none';


                        if (mostrar) {

                            visibles++;

                        }

                    }
                );


                if (noResults) {

                    noResults.style.display =
                        rows.length > 0 &&
                        visibles === 0
                            ? 'flex'
                            : 'none';

                }

            }
        );

    }
);

</script>

@endpush

@endsection