@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/productos.css') }}">
@endpush

@section('title', 'Productos | PrediccionIA')

@section('content')

<div class="products-page">

    {{-- =====================================================
         ENCABEZADO
    ====================================================== --}}

    <div class="page-header">

        <div>
            <span class="page-kicker">
                GESTIÓN
            </span>

            <h1>
                Productos
            </h1>

            <p>
                Administra los productos disponibles para la predicción de demanda.
            </p>
        </div>

        <a
            href="{{ route('productos.create') }}"
            class="btn-primary"
        >
            <i class="bi bi-plus-lg"></i>
            Nuevo producto
        </a>

    </div>


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
         RESUMEN
    ====================================================== --}}

    <div class="product-summary">

        <div class="summary-card">

            <div class="summary-icon">
                <i class="bi bi-box-seam"></i>
            </div>

            <div>
                <span>Total productos</span>
                <strong>{{ $productos->count() }}</strong>
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-icon warning">
                <i class="bi bi-exclamation-triangle"></i>
            </div>

            <div>
                <span>Stock bajo</span>

                <strong>
                    {{ $productos->filter(fn($p) => $p->stock <= $p->stock_minimo)->count() }}
                </strong>
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-icon success">
                <i class="bi bi-check-circle"></i>
            </div>

            <div>
                <span>Productos activos</span>

                <strong>
                    {{ $productos->where('activo', true)->count() }}
                </strong>
            </div>

        </div>


        <div class="summary-card">

            <div class="summary-icon purple">
                <i class="bi bi-layers"></i>
            </div>

            <div>
                <span>Categorías</span>

                <strong>
                    {{ $categorias->count() }}
                </strong>
            </div>

        </div>

    </div>


    {{-- =====================================================
         TABLA
    ====================================================== --}}

    <div class="products-panel">

        {{-- FILTROS --}}

        <div class="products-toolbar">

            <div>

                <h2>
                    Lista de productos
                </h2>

                <p>
                    Consulta los productos registrados en el sistema.
                </p>

            </div>


            <form
                method="GET"
                action="{{ route('productos.index') }}"
                class="products-filters"
            >

                {{-- Buscar --}}

                <div class="search-box">

                    <i class="bi bi-search"></i>

                    <input
                        type="text"
                        name="buscar"
                        value="{{ request('buscar') }}"
                        placeholder="Buscar producto..."
                    >

                </div>


                {{-- Categoría --}}

                <select
                    name="categoria"
                    class="category-filter"
                    onchange="this.form.submit()"
                >

                    <option value="">
                        Todas las categorías
                    </option>

                    @foreach($categorias as $categoria)

                        <option
                            value="{{ $categoria->id }}"
                            {{ request('categoria') == $categoria->id ? 'selected' : '' }}
                        >
                            {{ $categoria->nombre }}
                        </option>

                    @endforeach

                </select>


                <button
                    type="submit"
                    class="btn-filter"
                >
                    Filtrar
                </button>

            </form>

        </div>


        {{-- TABLA --}}

        <div class="table-wrapper">

            <table class="products-table">

                <thead>

                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Mínimo</th>
                        <th>Estado</th>
                    </tr>

                </thead>


                <tbody>

                    @forelse($productos as $producto)

                        <tr>

                            {{-- PRODUCTO --}}

                            <td>

                                <div class="product-name">

                                    <div class="product-icon">
                                        <i class="bi bi-box"></i>
                                    </div>

                                    <div>

                                        <strong>
                                            {{ $producto->nombre }}
                                        </strong>

                                        @if($producto->descripcion)

                                            <small>
                                                {{ $producto->descripcion }}
                                            </small>

                                        @endif

                                    </div>

                                </div>

                            </td>


                            {{-- CATEGORÍA --}}

                            <td>

                                <span class="category-badge">
                                    {{ $producto->categoria?->nombre ?? 'Sin categoría' }}
                                </span>

                            </td>


                            {{-- PRECIO --}}

                            <td>

                                <strong class="price">
                                    S/ {{ number_format($producto->precio, 2) }}
                                </strong>

                            </td>


                            {{-- STOCK --}}

                            <td>

                                <span
                                    class="
                                        stock-value
                                        {{ $producto->stock <= $producto->stock_minimo ? 'low' : '' }}
                                    "
                                >
                                    {{ $producto->stock }}
                                </span>

                            </td>


                            {{-- STOCK MÍNIMO --}}

                            <td>
                                {{ $producto->stock_minimo }}
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
                                        Activo
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
                                    No se encontraron productos
                                </strong>

                                <span>
                                    Prueba con otro término de búsqueda o registra un nuevo producto.
                                </span>

                                <a
                                    href="{{ route('productos.create') }}"
                                    class="btn-primary"
                                >
                                    <i class="bi bi-plus-lg"></i>
                                    Registrar producto
                                </a>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection