@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/categorias.css') }}">
@endpush

@section('title', 'Categorías | PrediccionIA')

@section('content')

<div class="categories-page">

    {{-- =====================================================
         ENCABEZADO
    ====================================================== --}}

    <div class="page-header">

        <div>
            <span class="page-kicker">
                GESTIÓN
            </span>

            <h1>
                Categorías
            </h1>

            <p>
                Administra las categorías utilizadas para organizar
                los productos del sistema.
            </p>
        </div>

        {{-- NUEVA CATEGORÍA --}}

        <a
            href="{{ route('categorias.create') }}"
            class="btn-primary"
        >
            <i class="bi bi-plus-lg"></i>
            Nueva categoría
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
         ERRORES
    ====================================================== --}}

    @if($errors->any())

        <div class="alert-error">

            <i class="bi bi-exclamation-circle-fill"></i>

            <div>

                @foreach($errors->all() as $error)

                    <span>
                        {{ $error }}
                    </span>

                @endforeach

            </div>

        </div>

    @endif


    {{-- =====================================================
         RESUMEN
    ====================================================== --}}

    <div class="category-summary">

        {{-- TOTAL --}}

        <div class="summary-card">

            <div class="summary-icon">
                <i class="bi bi-tags"></i>
            </div>

            <div>
                <span>Total categorías</span>

                <strong>
                    {{ $totalCategorias }}
                </strong>
            </div>

        </div>


        {{-- ACTIVAS --}}

        <div class="summary-card">

            <div class="summary-icon success">
                <i class="bi bi-check-circle"></i>
            </div>

            <div>
                <span>Categorías activas</span>

                <strong>
                    {{ $categoriasActivas }}
                </strong>
            </div>

        </div>


        {{-- INACTIVAS --}}

        <div class="summary-card">

            <div class="summary-icon warning">
                <i class="bi bi-dash-circle"></i>
            </div>

            <div>
                <span>Categorías inactivas</span>

                <strong>
                    {{ $categoriasInactivas }}
                </strong>
            </div>

        </div>


        {{-- PRODUCTOS --}}

        <div class="summary-card">

            <div class="summary-icon purple">
                <i class="bi bi-box-seam"></i>
            </div>

            <div>
                <span>Productos asociados</span>

                <strong>
                    {{ $productosAsociados }}
                </strong>
            </div>

        </div>

    </div>


    {{-- =====================================================
         PANEL
    ====================================================== --}}

    <div class="categories-panel">


        {{-- =================================================
             TOOLBAR
        ================================================== --}}

        <div class="categories-toolbar">

            <div>

                <h2>
                    Lista de categorías
                </h2>

                <p>
                    Consulta y administra las categorías registradas.
                </p>

            </div>


            {{-- FILTROS --}}

            <form
                method="GET"
                action="{{ route('categorias.index') }}"
                class="categories-filters"
            >

                {{-- BUSCAR --}}

                <div class="search-box">

                    <i class="bi bi-search"></i>

                    <input
                        type="text"
                        name="buscar"
                        value="{{ request('buscar') }}"
                        placeholder="Buscar categoría..."
                    >

                </div>


                {{-- ESTADO --}}

                <select
                    name="estado"
                    class="status-filter"
                >

                    <option value="">
                        Todos los estados
                    </option>

                    <option
                        value="activo"
                        {{ request('estado') === 'activo' ? 'selected' : '' }}
                    >
                        Activas
                    </option>

                    <option
                        value="inactivo"
                        {{ request('estado') === 'inactivo' ? 'selected' : '' }}
                    >
                        Inactivas
                    </option>

                </select>


                {{-- FILTRAR --}}

                <button
                    type="submit"
                    class="btn-filter"
                >
                    <i class="bi bi-funnel"></i>
                    Filtrar
                </button>

            </form>

        </div>


        {{-- =================================================
             TABLA
        ================================================== --}}

        <div class="table-wrapper">

            <table class="categories-table">

                <thead>

                    <tr>
                        <th>Categoría</th>
                        <th>Descripción</th>
                        <th>Productos</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>

                </thead>


                <tbody>

                    @forelse($categorias as $categoria)

                        <tr>

                            {{-- =================================
                                 CATEGORÍA
                            ================================== --}}

                            <td>

                                <div class="category-name">

                                    <div class="category-icon">
                                        <i class="bi bi-tag"></i>
                                    </div>

                                    <div>

                                        <strong>
                                            {{ $categoria->nombre }}
                                        </strong>

                                    </div>

                                </div>

                            </td>


                            {{-- =================================
                                 DESCRIPCIÓN
                            ================================== --}}

                            <td>

                                <span class="category-description">

                                    {{ $categoria->descripcion ?: 'Sin descripción' }}

                                </span>

                            </td>


                            {{-- =================================
                                 PRODUCTOS
                            ================================== --}}

                            <td>

                                @php
                                    $cantidadProductos = $categoria->productos_count ?? 0;
                                @endphp

                                <span class="products-count">

                                    {{ $cantidadProductos }}

                                    {{ $cantidadProductos == 1 ? 'producto' : 'productos' }}

                                </span>

                            </td>


                            {{-- =================================
                                 ESTADO
                            ================================== --}}

                            <td>

                                @if($categoria->activo)

                                    <span class="status-badge active">
                                        <span></span>
                                        Activa
                                    </span>

                                @else

                                    <span class="status-badge inactive">
                                        <span></span>
                                        Inactiva
                                    </span>

                                @endif

                            </td>


                            {{-- =================================
                                 ACCIONES
                            ================================== --}}

                            <td>

                                <div class="actions">


                                    {{-- EDITAR --}}

                                    <a
                                        href="{{ route('categorias.edit', $categoria) }}"
                                        class="action-btn edit"
                                        title="Editar categoría"
                                    >

                                        <i class="bi bi-pencil"></i>

                                    </a>


                                    {{-- ACTIVAR / DESACTIVAR --}}

                                    <form
                                        action="{{ route('categorias.toggle', $categoria) }}"
                                        method="POST"
                                        class="action-form"
                                    >

                                        @csrf

                                        @method('PATCH')

                                        <button
                                            type="submit"
                                            class="action-btn {{ $categoria->activo ? 'deactivate' : 'activate' }}"
                                            title="{{ $categoria->activo ? 'Desactivar categoría' : 'Activar categoría' }}"
                                        >

                                            @if($categoria->activo)

                                                <i class="bi bi-toggle-on"></i>

                                            @else

                                                <i class="bi bi-toggle-off"></i>

                                            @endif

                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>


                    @empty

                        {{-- =====================================
                             SIN RESULTADOS
                        ====================================== --}}

                        <tr>

                            <td
                                colspan="5"
                                class="empty-categories"
                            >

                                <i class="bi bi-tags"></i>

                                <strong>
                                    No se encontraron categorías
                                </strong>

                                <span>
                                    Prueba con otro término de búsqueda
                                    o registra una nueva categoría.
                                </span>

                                <a
                                    href="{{ route('categorias.create') }}"
                                    class="btn-primary"
                                >

                                    <i class="bi bi-plus-lg"></i>

                                    Nueva categoría

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