@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/inventario.css') }}">
@endpush

@section('title', 'Inventario | PrediccionIA')

@section('content')

<div class="inventory-page">

    {{-- =====================================================
         MENSAJE DE ÉXITO
    ====================================================== --}}

    @if(session('success'))
        <div class="alert-success">
            <i class="bi bi-check-circle-fill"></i>

            <span>
                {{ session('success') }}
            </span>
        </div>
    @endif


    {{-- =====================================================
         ERRORES DE VALIDACIÓN
    ====================================================== --}}

    @if($errors->any())
        <div class="alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i>

            <div>
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif


    {{-- =====================================================
         ENCABEZADO
    ====================================================== --}}

    <div class="page-header">

        <div>

            <span class="page-kicker">
                GESTIÓN DE INVENTARIO
            </span>

            <h1>
                Inventario
            </h1>

            <p>
                Controla el stock disponible y los movimientos de inventario.
            </p>

        </div>

        <button
            type="button"
            class="btn-primary"
            onclick="abrirMovimiento()"
        >
            <i class="bi bi-plus-lg"></i>
            Registrar movimiento
        </button>

    </div>


    {{-- =====================================================
         RESUMEN
    ====================================================== --}}

    <div class="inventory-summary">

        {{-- PRODUCTOS --}}

        <div class="summary-card">

            <div class="summary-icon">
                <i class="bi bi-box-seam"></i>
            </div>

            <div>
                <span>Productos</span>

                <strong>
                    {{ $totalProductos }}
                </strong>
            </div>

        </div>


        {{-- STOCK TOTAL --}}

        <div class="summary-card">

            <div class="summary-icon blue">
                <i class="bi bi-stack"></i>
            </div>

            <div>
                <span>Unidades en stock</span>

                <strong>
                    {{ number_format($stockTotal) }}
                </strong>
            </div>

        </div>


        {{-- STOCK BAJO --}}

        <div class="summary-card">

            <div class="summary-icon warning">
                <i class="bi bi-exclamation-triangle"></i>
            </div>

            <div>
                <span>Stock bajo</span>

                <strong>
                    {{ $stockBajo }}
                </strong>
            </div>

        </div>


        {{-- ACTIVOS --}}

        <div class="summary-card">

            <div class="summary-icon success">
                <i class="bi bi-check-circle"></i>
            </div>

            <div>
                <span>Productos activos</span>

                <strong>
                    {{ $productosActivos }}
                </strong>
            </div>

        </div>

    </div>


    {{-- =====================================================
         INVENTARIO ACTUAL
    ====================================================== --}}

    <div class="inventory-panel">

        <div class="inventory-panel-header">

            <div>

                <h2>
                    Stock actual
                </h2>

                <p>
                    Estado actual del inventario por producto.
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

        </div>


        <div class="table-wrapper">

            <table class="inventory-table">

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


                <tbody id="inventoryTableBody">

                    @forelse($productos as $producto)

                        <tr>

                            {{-- PRODUCTO --}}

                            <td>

                                <div class="inventory-product">

                                    <div class="inventory-product-icon">
                                        <i class="bi bi-box-seam"></i>
                                    </div>

                                    <div>

                                        <strong>
                                            {{ $producto->nombre }}
                                        </strong>

                                        <small>
                                            {{ $producto->descripcion ?: 'Sin descripción' }}
                                        </small>

                                    </div>

                                </div>

                            </td>


                            {{-- CATEGORÍA --}}

                            <td>

                                <span class="category-badge">
                                    {{ $producto->categoria?->nombre ?? 'Sin categoría' }}
                                </span>

                            </td>


                            {{-- STOCK ACTUAL --}}

                            <td>

                                <span
                                    class="inventory-stock
                                    {{ $producto->stock <= $producto->stock_minimo ? 'low' : '' }}"
                                >
                                    {{ $producto->stock }}
                                </span>

                            </td>


                            {{-- STOCK MÍNIMO --}}

                            <td>

                                <span class="inventory-number">
                                    {{ $producto->stock_minimo }}
                                </span>

                            </td>


                            {{-- STOCK SEGURIDAD --}}

                            <td>

                                <span class="inventory-number">
                                    {{ $producto->stock_seguridad }}
                                </span>

                            </td>


                            {{-- ESTADO --}}

                            <td>

                                @if(!$producto->activo)

                                    <span class="status-badge inactive">
                                        <span></span>
                                        Inactivo
                                    </span>

                                @elseif($producto->stock <= $producto->stock_minimo)

                                    <span class="status-badge warning">
                                        <span></span>
                                        Stock bajo
                                    </span>

                                @else

                                    <span class="status-badge active">
                                        <span></span>
                                        Stock normal
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="empty-products"
                            >

                                <i class="bi bi-box-seam"></i>

                                <strong>
                                    No hay productos registrados
                                </strong>

                                <span>
                                    Registra productos para comenzar a controlar el inventario.
                                </span>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    {{-- =====================================================
         MOVIMIENTOS RECIENTES
    ====================================================== --}}

    <div class="inventory-panel movements-panel">

        <div class="inventory-panel-header">

            <div>

                <h2>
                    Movimientos recientes
                </h2>

                <p>
                    Últimos movimientos registrados en el inventario.
                </p>

            </div>

        </div>


        <div class="table-wrapper">

            <table class="inventory-table movements-table">

                <thead>

                    <tr>

                        <th>Producto</th>

                        <th>Tipo</th>

                        <th>Cantidad</th>

                        <th>Stock anterior</th>

                        <th>Stock nuevo</th>

                        <th>Fecha</th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($movimientos as $movimiento)

                        <tr>

                            {{-- PRODUCTO --}}

                            <td>

                                <strong>
                                    {{ $movimiento->producto?->nombre ?? 'Producto eliminado' }}
                                </strong>

                            </td>


                            {{-- TIPO --}}

                            <td>

                                @switch($movimiento->tipo)

                                    @case('entrada')

                                        <span class="movement-badge entry">
                                            <i class="bi bi-arrow-down-left"></i>
                                            Entrada
                                        </span>

                                        @break


                                    @case('salida')

                                        <span class="movement-badge exit">
                                            <i class="bi bi-arrow-up-right"></i>
                                            Salida
                                        </span>

                                        @break


                                    @case('reposicion')

                                        <span class="movement-badge restock">
                                            <i class="bi bi-box-arrow-in-down"></i>
                                            Reposición
                                        </span>

                                        @break


                                    @case('ajuste')

                                        <span class="movement-badge adjustment">
                                            <i class="bi bi-sliders"></i>
                                            Ajuste
                                        </span>

                                        @break


                                    @default

                                        <span class="movement-badge adjustment">
                                            <i class="bi bi-question-circle"></i>
                                            {{ ucfirst($movimiento->tipo) }}
                                        </span>

                                @endswitch

                            </td>


                            {{-- CANTIDAD --}}

                            <td>

                                <strong>
                                    {{ $movimiento->cantidad }}
                                </strong>

                            </td>


                            {{-- STOCK ANTERIOR --}}

                            <td>
                                {{ $movimiento->stock_anterior }}
                            </td>


                            {{-- STOCK NUEVO --}}

                            <td>

                                <strong>
                                    {{ $movimiento->stock_nuevo }}
                                </strong>

                            </td>


                            {{-- FECHA --}}

                            <td>

                                <span class="movement-date">

                                    {{ $movimiento->fecha?->format('d/m/Y H:i') ?? 'Sin fecha' }}

                                </span>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="empty-products"
                            >

                                <i class="bi bi-clock-history"></i>

                                <strong>
                                    No hay movimientos registrados
                                </strong>

                                <span>
                                    Los movimientos de inventario aparecerán aquí.
                                </span>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>


{{-- =====================================================
     MODAL REGISTRAR MOVIMIENTO
====================================================== --}}

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

        <div class="movement-modal-header">

            <div>

                <span class="page-kicker">
                    INVENTARIO
                </span>

                <h2>
                    Registrar movimiento
                </h2>

                <p>
                    Actualiza el stock de un producto.
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

        </div>


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
                            {{ old('producto_id') == $producto->id ? 'selected' : '' }}
                        >
                            {{ $producto->nombre }}
                            — Stock actual: {{ $producto->stock }}
                        </option>

                    @endforeach

                </select>

            </div>


            {{-- TIPO --}}

            <div class="form-group">

                <label for="tipo">
                    Tipo de movimiento
                </label>

                <select
                    name="tipo"
                    id="tipo"
                    required
                >

                    <option value="">
                        Selecciona un tipo
                    </option>

                    <option
                        value="entrada"
                        {{ old('tipo') === 'entrada' ? 'selected' : '' }}
                    >
                        Entrada
                    </option>

                    <option
                        value="salida"
                        {{ old('tipo') === 'salida' ? 'selected' : '' }}
                    >
                        Salida
                    </option>

                    <option
                        value="reposicion"
                        {{ old('tipo') === 'reposicion' ? 'selected' : '' }}
                    >
                        Reposición
                    </option>

                    <option
                        value="ajuste"
                        {{ old('tipo') === 'ajuste' ? 'selected' : '' }}
                    >
                        Ajuste
                    </option>

                </select>

                <small id="movementHelp">
                    Selecciona el tipo de movimiento.
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
                    <span>(opcional)</span>
                </label>

                <textarea
                    name="motivo"
                    id="motivo"
                    rows="3"
                    maxlength="500"
                    placeholder="Describe el motivo del movimiento..."
                >{{ old('motivo') }}</textarea>

            </div>


            {{-- BOTONES --}}

            <div class="movement-form-actions">

                <button
                    type="button"
                    class="btn-secondary"
                    onclick="cerrarMovimiento()"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="btn-primary"
                >
                    <i class="bi bi-check-lg"></i>
                    Registrar movimiento
                </button>

            </div>

        </form>

    </div>

</div>


{{-- =====================================================
     JAVASCRIPT
====================================================== --}}

@push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | BÚSQUEDA DE PRODUCTOS
    |--------------------------------------------------------------------------
    */

    const searchInput =
        document.getElementById('inventorySearch');

    const tableBody =
        document.getElementById('inventoryTableBody');


    if (searchInput && tableBody) {

        searchInput.addEventListener('input', function () {

            const search =
                this.value.toLowerCase().trim();

            const rows =
                tableBody.querySelectorAll('tr');

            rows.forEach(function (row) {

                const text =
                    row.textContent.toLowerCase();

                row.style.display =
                    text.includes(search)
                        ? ''
                        : 'none';

            });

        });

    }


    /*
    |--------------------------------------------------------------------------
    | TIPO DE MOVIMIENTO
    |--------------------------------------------------------------------------
    */

    const tipo =
        document.getElementById('tipo');

    const movementHelp =
        document.getElementById('movementHelp');


    if (tipo && movementHelp) {

        tipo.addEventListener('change', function () {

            switch (this.value) {

                case 'entrada':

                    movementHelp.textContent =
                        'La cantidad se sumará al stock actual.';

                    break;


                case 'salida':

                    movementHelp.textContent =
                        'La cantidad se descontará del stock actual.';

                    break;


                case 'reposicion':

                    movementHelp.textContent =
                        'La cantidad se sumará al stock como reposición.';

                    break;


                case 'ajuste':

                    movementHelp.textContent =
                        'La cantidad indicada reemplazará el stock actual.';

                    break;


                default:

                    movementHelp.textContent =
                        'Selecciona el tipo de movimiento.';

            }

        });

    }


    /*
    |--------------------------------------------------------------------------
    | ESC PARA CERRAR MODAL
    |--------------------------------------------------------------------------
    */

    document.addEventListener('keydown', function (event) {

        if (event.key === 'Escape') {

            cerrarMovimiento();

        }

    });


    /*
    |--------------------------------------------------------------------------
    | ABRIR AUTOMÁTICAMENTE SI HUBO ERRORES
    |--------------------------------------------------------------------------
    */

    @if($errors->any())

        abrirMovimiento();

    @endif

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

    modal.classList.add('show');

    document.body.classList.add('modal-open');

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

    modal.classList.remove('show');

    document.body.classList.remove('modal-open');

}


/*
|--------------------------------------------------------------------------
| CERRAR AL HACER CLICK FUERA
|--------------------------------------------------------------------------
*/

function cerrarModalExterior(event) {

    if (event.target.id === 'movementModal') {

        cerrarMovimiento();

    }

}

</script>

@endpush

@endsection