@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/productos.css') }}">
@endpush

@section('title', 'Nuevo producto | PrediccionIA')

@section('content')

<div class="products-page">

    {{-- ENCABEZADO --}}

    <div class="page-header">

        <div>

            <span class="page-kicker">
                GESTIÓN DE PRODUCTOS
            </span>

            <h1>
                Nuevo producto
            </h1>

            <p>
                Registra un nuevo producto en el sistema.
            </p>

        </div>

        <a
            href="{{ route('productos.index') }}"
            class="btn-secondary"
        >
            <i class="bi bi-arrow-left"></i>
            Volver a productos
        </a>

    </div>


    {{-- ERRORES --}}

    @if($errors->any())

        <div class="alert-errors">

            <div class="alert-errors-title">

                <i class="bi bi-exclamation-circle-fill"></i>

                <strong>
                    Hay algunos errores en el formulario
                </strong>

            </div>

            <ul>

                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- FORMULARIO --}}

    <div class="form-panel">

        <div class="form-panel-header">

            <div class="form-header-icon">
                <i class="bi bi-box-seam"></i>
            </div>

            <div>

                <h2>
                    Información del producto
                </h2>

                <p>
                    Completa los datos necesarios para registrar el producto.
                </p>

            </div>

        </div>


        <form
            method="POST"
            action="{{ route('productos.store') }}"
        >

            @csrf


            {{-- =================================================
                 INFORMACIÓN GENERAL
            ================================================== --}}

            <div class="form-section">

                <h3>
                    Información general
                </h3>

                <div class="form-grid">


                    {{-- NOMBRE --}}

                    <div class="form-group">

                        <label for="nombre">
                            Nombre del producto
                            <span>*</span>
                        </label>

                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            value="{{ old('nombre') }}"
                            placeholder="Ej. Café Americano"
                            required
                        >

                    </div>


                    {{-- CATEGORÍA --}}

                    <div class="form-group">

                        <label for="categoria_id">
                            Categoría
                            <span>*</span>
                        </label>

                        <select
                            id="categoria_id"
                            name="categoria_id"
                            required
                        >

                            <option value="">
                                Seleccionar categoría
                            </option>

                            @foreach($categorias as $categoria)

                                <option
                                    value="{{ $categoria->id }}"
                                    {{ old('categoria_id') == $categoria->id ? 'selected' : '' }}
                                >
                                    {{ $categoria->nombre }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- DESCRIPCIÓN --}}

                    <div class="form-group full">

                        <label for="descripcion">
                            Descripción
                        </label>

                        <textarea
                            id="descripcion"
                            name="descripcion"
                            rows="4"
                            placeholder="Describe brevemente el producto..."
                        >{{ old('descripcion') }}</textarea>

                    </div>

                </div>

            </div>


            {{-- =================================================
                 INFORMACIÓN ECONÓMICA
            ================================================== --}}

            <div class="form-section">

                <h3>
                    Información económica
                </h3>

                <div class="form-grid">


                    {{-- PRECIO --}}

                    <div class="form-group">

                        <label for="precio">
                            Precio de venta
                            <span>*</span>
                        </label>

                        <div class="input-prefix">

                            <span>S/</span>

                            <input
                                type="number"
                                id="precio"
                                name="precio"
                                value="{{ old('precio') }}"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                required
                            >

                        </div>

                    </div>


                    {{-- COSTO --}}

                    <div class="form-group">

                        <label for="costo">
                            Costo del producto
                            <span>*</span>
                        </label>

                        <div class="input-prefix">

                            <span>S/</span>

                            <input
                                type="number"
                                id="costo"
                                name="costo"
                                value="{{ old('costo') }}"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                required
                            >

                        </div>

                    </div>

                </div>

            </div>


            {{-- =================================================
                 INVENTARIO
            ================================================== --}}

            <div class="form-section">

                <h3>
                    Control de inventario
                </h3>

                <div class="form-grid">


                    {{-- STOCK --}}

                    <div class="form-group">

                        <label for="stock">
                            Stock actual
                            <span>*</span>
                        </label>

                        <input
                            type="number"
                            id="stock"
                            name="stock"
                            value="{{ old('stock', 0) }}"
                            min="0"
                            required
                        >

                    </div>


                    {{-- STOCK MÍNIMO --}}

                    <div class="form-group">

                        <label for="stock_minimo">
                            Stock mínimo
                            <span>*</span>
                        </label>

                        <input
                            type="number"
                            id="stock_minimo"
                            name="stock_minimo"
                            value="{{ old('stock_minimo', 10) }}"
                            min="0"
                            required
                        >

                    </div>


                    {{-- STOCK SEGURIDAD --}}

                    <div class="form-group">

                        <label for="stock_seguridad">
                            Stock de seguridad
                            <span>*</span>
                        </label>

                        <input
                            type="number"
                            id="stock_seguridad"
                            name="stock_seguridad"
                            value="{{ old('stock_seguridad', 5) }}"
                            min="0"
                            required
                        >

                    </div>


                    {{-- ESTADO --}}

                    <div class="form-group">

                        <label>
                            Estado
                        </label>

                        <label class="switch-field">

                            <input
                                type="checkbox"
                                name="activo"
                                value="1"
                                {{ old('activo', true) ? 'checked' : '' }}
                            >

                            <span>
                                Producto activo
                            </span>

                        </label>

                    </div>

                </div>

            </div>


            {{-- ACCIONES --}}

            <div class="form-actions">

                <a
                    href="{{ route('productos.index') }}"
                    class="btn-secondary"
                >
                    Cancelar
                </a>

                <button
                    type="submit"
                    class="btn-primary"
                >
                    <i class="bi bi-check-lg"></i>
                    Registrar producto
                </button>

            </div>

        </form>

    </div>

</div>

@endsection