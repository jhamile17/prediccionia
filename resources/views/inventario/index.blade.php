@extends('layouts.app')

@section('title', 'Inventario | PrediccionIA')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/inventario.css') }}">
@endpush

@section('content')

@php

    /*
    |--------------------------------------------------------------------------
    | DATOS DE RESUMEN
    |--------------------------------------------------------------------------
    */

    $productosColeccion = collect($productos ?? []);

    $productosSinStock = $productosColeccion
        ->filter(fn ($producto) => (int) $producto->stock <= 0)
        ->count();

    $totalMovimientos = count($movimientos ?? []);

@endphp


<main class="inventory-page">


    {{-- =========================================================
         MENSAJES
    ========================================================== --}}

    @if(session('success'))

        <div class="inventory-alert success">

            <div class="inventory-alert-icon">
                <i class="bi bi-check-lg"></i>
            </div>

            <div>

                <strong>
                    Operación realizada
                </strong>

                <span>
                    {{ session('success') }}
                </span>

            </div>

        </div>

    @endif


    @if($errors->any())

        <div class="inventory-alert danger">

            <div class="inventory-alert-icon">
                <i class="bi bi-exclamation-lg"></i>
            </div>

            <div>

                <strong>
                    No se pudo completar la operación
                </strong>

                @foreach($errors->all() as $error)

                    <span>
                        {{ $error }}
                    </span>

                @endforeach

            </div>

        </div>

    @endif


    {{-- =========================================================
         ENCABEZADO
    ========================================================== --}}

    <header class="inventory-hero">

        <div>

            <span class="inventory-eyebrow">
                INVENTARIO
            </span>

            <h1>
                ¿Qué tengo disponible?
            </h1>

            <p>
                Consulta rápidamente el stock de tus productos
                y actualiza las existencias cuando sea necesario.
            </p>

        </div>


        <button
            type="button"
            class="inventory-primary-button"
            onclick="abrirMovimiento()"
        >

            <i class="bi bi-plus-lg"></i>

            Registrar movimiento

        </button>

    </header>


    {{-- =========================================================
         RESUMEN
    ========================================================== --}}

    <section class="inventory-summary">


        {{-- PRODUCTOS --}}

        <article class="inventory-summary-card">

            <div class="summary-card-icon brown">

                <i class="bi bi-box-seam"></i>

            </div>

            <div class="summary-card-content">

                <span>
                    Productos
                </span>

                <strong>
                    {{ $productosColeccion->count() }}
                </strong>

                <small>
                    Registrados
                </small>

            </div>

        </article>


        {{-- UNIDADES --}}

        <article class="inventory-summary-card">

            <div class="summary-card-icon brown">

                <i class="bi bi-stack"></i>

            </div>

            <div class="summary-card-content">

                <span>
                    Unidades disponibles
                </span>

                <strong>
                    {{ number_format($stockTotal) }}
                </strong>

                <small>
                    En todo el inventario
                </small>

            </div>

        </article>


        {{-- STOCK BAJO --}}

        <article class="inventory-summary-card warning">

            <div class="summary-card-icon orange">

                <i class="bi bi-exclamation-triangle"></i>

            </div>

            <div class="summary-card-content">

                <span>
                    Stock bajo
                </span>

                <strong>
                    {{ $stockBajo }}
                </strong>

                <small>
                    Necesitan atención
                </small>

            </div>

        </article>


        {{-- SIN STOCK --}}

        <article class="inventory-summary-card danger">

            <div class="summary-card-icon red">

                <i class="bi bi-x-circle"></i>

            </div>

            <div class="summary-card-content">

                <span>
                    Sin stock
                </span>

                <strong>
                    {{ $productosSinStock }}
                </strong>

                <small>
                    Productos agotados
                </small>

            </div>

        </article>

    </section>


    {{-- =========================================================
         INVENTARIO ACTUAL
    ========================================================== --}}

    <section class="inventory-panel">


        <header class="inventory-panel-header">

            <div>

                <span class="inventory-section-label">
                    EXISTENCIAS
                </span>

                <h2>
                    Stock actual
                </h2>

                <p>
                    Revisa cuántas unidades tienes disponibles
                    de cada producto.
                </p>

            </div>


            {{-- BUSCADOR --}}

            <div class="inventory-search">

                <i class="bi bi-search"></i>

                <input
                    type="text"
                    id="inventorySearch"
                    placeholder="Buscar producto..."
                    autocomplete="off"
                >

            </div>

        </header>


        {{-- =====================================================
             FILTROS
        ====================================================== --}}

        <div class="inventory-filters">

            <button
                type="button"
                class="inventory-filter active"
                data-filter="all"
            >
                Todos
            </button>

            <button
                type="button"
                class="inventory-filter"
                data-filter="normal"
            >
                Disponible
            </button>

            <button
                type="button"
                class="inventory-filter"
                data-filter="low"
            >
                Stock bajo
            </button>

            <button
                type="button"
                class="inventory-filter"
                data-filter="empty"
            >
                Sin stock
            </button>

        </div>


        {{-- =====================================================
             TABLA
        ====================================================== --}}

        <div class="inventory-table-wrapper">

            <table class="inventory-table">

                <thead>

                    <tr>

                        <th>
                            Producto
                        </th>

                        <th>
                            Categoría
                        </th>

                        <th>
                            Disponible
                        </th>

                        <th>
                            Mínimo
                        </th>

                        <th>
                            Estado
                        </th>

                    </tr>

                </thead>


                <tbody id="inventoryTableBody">


                    @forelse($productos as $producto)

                        @php

                            $stock =
                                (int) $producto->stock;

                            $stockMinimo =
                                (int) $producto->stock_minimo;

                            if (!$producto->activo) {

                                $estadoFiltro = 'inactive';

                            } elseif ($stock <= 0) {

                                $estadoFiltro = 'empty';

                            } elseif ($stock <= $stockMinimo) {

                                $estadoFiltro = 'low';

                            } else {

                                $estadoFiltro = 'normal';

                            }

                        @endphp


                        <tr
                            data-status="{{ $estadoFiltro }}"
                        >


                            {{-- PRODUCTO --}}

                            <td>

                                <div class="inventory-product">

                                    <div class="inventory-product-avatar">

                                        <i class="bi bi-box-seam"></i>

                                    </div>

                                    <div>

                                        <strong>
                                            {{ $producto->nombre }}
                                        </strong>

                                        <span>
                                            {{ $producto->descripcion ?: 'Sin descripción' }}
                                        </span>

                                    </div>

                                </div>

                            </td>


                            {{-- CATEGORÍA --}}

                            <td>

                                <span class="category-chip">

                                    {{ $producto->categoria?->nombre ?? 'Sin categoría' }}

                                </span>

                            </td>


                            {{-- DISPONIBLE --}}

                            <td>

                                <div
                                    class="
                                        stock-value
                                        {{ $stock <= 0
                                            ? 'empty'
                                            : ($stock <= $stockMinimo
                                                ? 'low'
                                                : 'normal'
                                        ) }}
                                    "
                                >

                                    {{ $stock }}

                                    <small>
                                        unidades
                                    </small>

                                </div>

                            </td>


                            {{-- MÍNIMO --}}

                            <td>

                                <span class="table-number">

                                    {{ $stockMinimo }}

                                </span>

                            </td>


                            {{-- ESTADO --}}

                            <td>

                                @if(!$producto->activo)

                                    <span class="stock-status inactive">

                                        <i></i>

                                        Inactivo

                                    </span>


                                @elseif($stock <= 0)

                                    <span class="stock-status danger">

                                        <i></i>

                                        Sin stock

                                    </span>


                                @elseif($stock <= $stockMinimo)

                                    <span class="stock-status warning">

                                        <i></i>

                                        Stock bajo

                                    </span>


                                @else

                                    <span class="stock-status success">

                                        <i></i>

                                        Disponible

                                    </span>

                                @endif

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="5"
                                class="inventory-empty"
                            >

                                <div class="empty-state-icon">

                                    <i class="bi bi-box-seam"></i>

                                </div>

                                <strong>
                                    No hay productos registrados
                                </strong>

                                <span>
                                    Los productos aparecerán aquí
                                    cuando sean registrados.
                                </span>

                            </td>

                        </tr>

                    @endforelse


                </tbody>

            </table>

        </div>


        {{-- SIN RESULTADOS --}}

        <div
            id="inventoryNoResults"
            class="inventory-no-results"
            style="display: none;"
        >

            <div class="empty-state-icon">

                <i class="bi bi-search"></i>

            </div>

            <strong>
                No encontramos productos
            </strong>

            <span>
                Prueba con otro nombre o cambia el filtro.
            </span>

        </div>


    </section>


    {{-- =========================================================
         MOVIMIENTOS RECIENTES
    ========================================================== --}}

    <section class="inventory-panel movements-panel">


        <header class="inventory-panel-header movements-header">

            <div>

                <span class="inventory-section-label">
                    ACTIVIDAD RECIENTE
                </span>

                <h2>
                    Últimos movimientos
                </h2>

                <p>
                    Consulta las últimas actualizaciones del inventario.
                </p>

            </div>


            <div class="movement-info">

                <i class="bi bi-clock-history"></i>

                {{ $totalMovimientos }} registros

            </div>

        </header>


        <div class="inventory-table-wrapper">

            <table class="inventory-table movement-table">

                <thead>

                    <tr>

                        <th>
                            Producto
                        </th>

                        <th>
                            Movimiento
                        </th>

                        <th>
                            Cantidad
                        </th>

                        <th>
                            Nuevo stock
                        </th>

                        <th>
                            Fecha
                        </th>

                    </tr>

                </thead>


                <tbody>


                    @forelse($movimientos as $movimiento)


                        <tr>


                            {{-- PRODUCTO --}}

                            <td>

                                <div class="movement-product">

                                    <div class="movement-product-icon">

                                        <i class="bi bi-box-seam"></i>

                                    </div>

                                    <strong>

                                        {{ $movimiento->producto?->nombre
                                            ?? 'Producto eliminado'
                                        }}

                                    </strong>

                                </div>

                            </td>


                            {{-- MOVIMIENTO --}}

                            <td>

                                @switch($movimiento->tipo)


                                    @case('entrada')

                                        <span class="movement-type entry">

                                            <i class="bi bi-arrow-down-left"></i>

                                            Entrada

                                        </span>

                                        @break


                                    @case('salida')

                                        <span class="movement-type exit">

                                            <i class="bi bi-arrow-up-right"></i>

                                            Salida

                                        </span>

                                        @break


                                    @case('reposicion')

                                        <span class="movement-type restock">

                                            <i class="bi bi-box-arrow-in-down"></i>

                                            Reposición

                                        </span>

                                        @break


                                    @case('ajuste')

                                        <span class="movement-type adjustment">

                                            <i class="bi bi-sliders"></i>

                                            Ajuste

                                        </span>

                                        @break


                                    @default

                                        <span class="movement-type adjustment">

                                            <i class="bi bi-question-circle"></i>

                                            {{ ucfirst($movimiento->tipo) }}

                                        </span>

                                @endswitch

                            </td>


                            {{-- CANTIDAD --}}

                            <td>

                                <strong class="movement-quantity">

                                    {{ $movimiento->cantidad }}

                                </strong>

                            </td>


                            {{-- NUEVO STOCK --}}

                            <td>

                                <strong class="movement-stock current">

                                    {{ $movimiento->stock_nuevo }}

                                </strong>

                                <span class="movement-unit-label">
                                    unidades
                                </span>

                            </td>


                            {{-- FECHA --}}

                            <td>

                                <span class="movement-date">

                                    {{ $movimiento->fecha?->format('d/m/Y H:i')
                                        ?? 'Sin fecha'
                                    }}

                                </span>

                            </td>


                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="5"
                                class="inventory-empty"
                            >

                                <div class="empty-state-icon">

                                    <i class="bi bi-clock-history"></i>

                                </div>

                                <strong>
                                    Todavía no hay movimientos
                                </strong>

                                <span>
                                    Los movimientos aparecerán aquí
                                    cuando registres una operación.
                                </span>

                            </td>

                        </tr>

                    @endforelse


                </tbody>

            </table>

        </div>


    </section>


</main>



{{-- =========================================================
     MODAL PARA REGISTRAR MOVIMIENTO
========================================================= --}}

<div
    id="movementModal"
    class="modal-overlay"
    onclick="cerrarModalExterior(event)"
>


    <div
        class="movement-modal"
        onclick="event.stopPropagation()"
    >


        {{-- CABECERA --}}

        <header class="movement-modal-header">

            <div>

                <span class="inventory-section-label">
                    INVENTARIO
                </span>

                <h2>
                    Actualizar inventario
                </h2>

                <p>
                    Registra una entrada, salida o reposición.
                </p>

            </div>


            <button
                type="button"
                class="modal-close"
                onclick="cerrarMovimiento()"
                aria-label="Cerrar"
            >

                <i class="bi bi-x-lg"></i>

            </button>

        </header>


        {{-- FORMULARIO --}}

        <form
            action="{{ route('inventario.movimiento.store') }}"
            method="POST"
            class="movement-form"
        >

            @csrf


            {{-- PRODUCTO --}}

            <div class="form-group">

                <label for="producto_id">
                    Producto
                </label>

                <select
                    name="producto_id"
                    id="producto_id"
                    required
                >

                    <option value="">
                        Selecciona un producto
                    </option>

                    @foreach($productos as $producto)

                        <option
                            value="{{ $producto->id }}"
                            {{ old('producto_id') == $producto->id
                                ? 'selected'
                                : ''
                            }}
                        >

                            {{ $producto->nombre }}
                            — {{ $producto->stock }} disponibles

                        </option>

                    @endforeach

                </select>

            </div>


            {{-- TIPO --}}

            <div class="form-group">

                <label for="tipo">
                    ¿Qué deseas registrar?
                </label>

                <select
                    name="tipo"
                    id="tipo"
                    required
                >

                    <option value="">
                        Selecciona una opción
                    </option>

                    <option
                        value="entrada"
                        {{ old('tipo') === 'entrada'
                            ? 'selected'
                            : ''
                        }}
                    >
                        Entrada
                    </option>

                    <option
                        value="salida"
                        {{ old('tipo') === 'salida'
                            ? 'selected'
                            : ''
                        }}
                    >
                        Salida
                    </option>

                    <option
                        value="reposicion"
                        {{ old('tipo') === 'reposicion'
                            ? 'selected'
                            : ''
                        }}
                    >
                        Reposición
                    </option>

                    <option
                        value="ajuste"
                        {{ old('tipo') === 'ajuste'
                            ? 'selected'
                            : ''
                        }}
                    >
                        Ajuste de stock
                    </option>

                </select>

                <small id="movementHelp">
                    Selecciona qué deseas registrar.
                </small>

            </div>


            {{-- CANTIDAD --}}

            <div class="form-group">

                <label for="cantidad">
                    Cantidad
                </label>

                <input
                    type="number"
                    name="cantidad"
                    id="cantidad"
                    min="1"
                    value="{{ old('cantidad') }}"
                    placeholder="Ej. 10"
                    required
                >

            </div>


            {{-- MOTIVO --}}

            <div class="form-group">

                <label for="motivo">

                    Motivo

                    <span>
                        opcional
                    </span>

                </label>

                <textarea
                    name="motivo"
                    id="motivo"
                    rows="3"
                    maxlength="500"
                    placeholder="Escribe el motivo del movimiento..."
                >{{ old('motivo') }}</textarea>

            </div>


            {{-- ACCIONES --}}

            <div class="movement-form-actions">

                <button
                    type="button"
                    class="modal-button secondary"
                    onclick="cerrarMovimiento()"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    class="modal-button primary"
                >

                    <i class="bi bi-check-lg"></i>

                    Guardar movimiento

                </button>

            </div>


        </form>


    </div>

</div>



{{-- =========================================================
     JAVASCRIPT
========================================================= --}}

@push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {


    /*
    |--------------------------------------------------------------------------
    | ELEMENTOS
    |--------------------------------------------------------------------------
    */

    const searchInput =
        document.getElementById('inventorySearch');

    const tableBody =
        document.getElementById('inventoryTableBody');

    const noResults =
        document.getElementById('inventoryNoResults');

    const filterButtons =
        document.querySelectorAll('.inventory-filter');


    let filtroActual = 'all';


    /*
    |--------------------------------------------------------------------------
    | FILTRAR TABLA
    |--------------------------------------------------------------------------
    */

    function actualizarTabla() {

        if (!tableBody) {
            return;
        }


        const rows =
            tableBody.querySelectorAll(
                'tr[data-status]'
            );


        const search =
            searchInput
                ? searchInput.value
                    .toLowerCase()
                    .trim()
                : '';


        let visibles = 0;


        rows.forEach(function (row) {

            const texto =
                row.textContent
                    .toLowerCase();


            const estado =
                row.dataset.status || '';


            const coincideTexto =
                !search ||
                texto.includes(search);


            const coincideFiltro =
                filtroActual === 'all' ||
                estado === filtroActual;


            const mostrar =
                coincideTexto &&
                coincideFiltro;


            row.style.display =
                mostrar
                    ? ''
                    : 'none';


            if (mostrar) {

                visibles++;

            }

        });


        /*
         * Mostrar mensaje cuando
         * no existen coincidencias.
         */

        if (noResults) {

            noResults.style.display =
                visibles === 0 &&
                rows.length > 0
                    ? 'flex'
                    : 'none';

        }

    }


    /*
    |--------------------------------------------------------------------------
    | BUSCADOR
    |--------------------------------------------------------------------------
    */

    if (searchInput) {

        searchInput.addEventListener(
            'input',
            actualizarTabla
        );

    }


    /*
    |--------------------------------------------------------------------------
    | FILTROS
    |--------------------------------------------------------------------------
    */

    filterButtons.forEach(function (button) {

        button.addEventListener(
            'click',
            function () {

                filtroActual =
                    this.dataset.filter || 'all';


                filterButtons.forEach(
                    function (item) {

                        item.classList.remove(
                            'active'
                        );

                    }
                );


                this.classList.add(
                    'active'
                );


                actualizarTabla();

            }
        );

    });


    /*
    |--------------------------------------------------------------------------
    | AYUDA DEL TIPO DE MOVIMIENTO
    |--------------------------------------------------------------------------
    */

    const tipo =
        document.getElementById('tipo');

    const movementHelp =
        document.getElementById('movementHelp');


    if (tipo && movementHelp) {

        tipo.addEventListener(
            'change',
            function () {

                switch (this.value) {

                    case 'entrada':

                        movementHelp.textContent =
                            'Las unidades se sumarán al stock actual.';

                        break;


                    case 'salida':

                        movementHelp.textContent =
                            'Las unidades se descontarán del stock actual.';

                        break;


                    case 'reposicion':

                        movementHelp.textContent =
                            'Las unidades se agregarán como reposición.';

                        break;


                    case 'ajuste':

                        movementHelp.textContent =
                            'Utiliza esta opción para corregir el stock.';

                        break;


                    default:

                        movementHelp.textContent =
                            'Selecciona qué deseas registrar.';

                }

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | CERRAR CON ESC
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {

                cerrarMovimiento();

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | ABRIR AUTOMÁTICAMENTE SI EXISTEN ERRORES
    |--------------------------------------------------------------------------
    */

    @if($errors->any())

        abrirMovimiento();

    @endif


    /*
    |--------------------------------------------------------------------------
    | APLICAR FILTRADO INICIAL
    |--------------------------------------------------------------------------
    */

    actualizarTabla();

});


/*
|--------------------------------------------------------------------------
| ABRIR MODAL
|--------------------------------------------------------------------------
*/

function abrirMovimiento() {

    const modal =
        document.getElementById('movementModal');


    if (!modal) {

        return;

    }


    modal.classList.add(
        'show'
    );


    document.body.classList.add(
        'modal-open'
    );

}


/*
|--------------------------------------------------------------------------
| CERRAR MODAL
|--------------------------------------------------------------------------
*/

function cerrarMovimiento() {

    const modal =
        document.getElementById('movementModal');


    if (!modal) {

        return;

    }


    modal.classList.remove(
        'show'
    );


    document.body.classList.remove(
        'modal-open'
    );

}


/*
|--------------------------------------------------------------------------
| CERRAR AL HACER CLICK FUERA
|--------------------------------------------------------------------------
*/

function cerrarModalExterior(event) {

    if (
        event.target.id ===
        'movementModal'
    ) {

        cerrarMovimiento();

    }

}

</script>

@endpush

@endsection