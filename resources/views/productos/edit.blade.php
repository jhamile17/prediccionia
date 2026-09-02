@extends('layouts.app')

@section('title', 'Editar producto | PrediccionIA')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/productos.css') }}">
@endpush

@section('content')

<div class="products-page">

    {{-- =====================================================
         ENCABEZADO
    ====================================================== --}}

    <div class="page-header">

        <div>

            <span class="page-kicker">
                GESTIÓN DE PRODUCTOS
            </span>

            <h1>
                Editar producto
            </h1>

            <p>
                Actualiza la información del producto seleccionado.
            </p>

        </div>


        {{-- VOLVER --}}

        <a
            href="{{ route('productos.index') }}"
            class="btn-back"
        >
            <i class="bi bi-arrow-left"></i>
            Volver a productos
        </a>

    </div>


    {{-- =====================================================
         ERRORES
    ====================================================== --}}

    @if($errors->any())

        <div class="form-errors">

            <i class="bi bi-exclamation-triangle-fill"></i>

            <div>

                <strong>
                    Revisa los siguientes datos:
                </strong>

                <ul>

                    @foreach($errors->all() as $error)

                        <li>
                            {{ $error }}
                        </li>

                    @endforeach

                </ul>

            </div>

        </div>

    @endif


    {{-- =====================================================
         PANEL DEL FORMULARIO
    ====================================================== --}}

    <div class="product-form-panel">


        {{-- =================================================
             CABECERA DEL FORMULARIO
        ================================================== --}}

        <div class="product-form-header">

            <div class="form-header-icon">

                <i class="bi bi-pencil-square"></i>

            </div>

            <div>

                <h2>
                    Información del producto
                </h2>

                <p>
                    Modifica los datos necesarios y guarda los cambios.
                </p>

            </div>

        </div>


        {{-- =================================================
             FORMULARIO
        ================================================== --}}

        <form
            action="{{ route('productos.update', $producto) }}"
            method="POST"
            class="product-form"
        >

            @csrf
            @method('PUT')


            {{-- =================================================
                 INFORMACIÓN GENERAL
            ================================================== --}}

            <div class="form-section">

                <div class="form-section-title">

                    <i class="bi bi-info-circle"></i>

                    <div>

                        <h3>
                            Información general
                        </h3>

                        <p>
                            Datos principales del producto.
                        </p>

                    </div>

                </div>


                <div class="form-grid">


                    {{-- NOMBRE --}}

                    <div class="form-group">

                        <label for="nombre">
                            Nombre del producto
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            id="nombre"
                            name="nombre"
                            value="{{ old('nombre', $producto->nombre) }}"
                            placeholder="Ej. Café Americano"
                            maxlength="150"
                            required
                        >

                        @error('nombre')
                            <small class="field-error">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>


                    {{-- CATEGORÍA --}}

                    <div class="form-group">

                        <label for="categoria_id">
                            Categoría
                            <span class="required">*</span>
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
                                    {{ old('categoria_id', $producto->categoria_id) == $categoria->id ? 'selected' : '' }}
                                >
                                    {{ $categoria->nombre }}
                                </option>

                            @endforeach

                        </select>

                        @error('categoria_id')
                            <small class="field-error">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>


                    {{-- DESCRIPCIÓN --}}

                    <div class="form-group full-width">

                        <label for="descripcion">
                            Descripción
                        </label>

                        <textarea
                            id="descripcion"
                            name="descripcion"
                            rows="4"
                            maxlength="500"
                            placeholder="Describe brevemente el producto..."
                        >{{ old('descripcion', $producto->descripcion) }}</textarea>

                        @error('descripcion')
                            <small class="field-error">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>

                </div>

            </div>


            {{-- =================================================
                 INFORMACIÓN ECONÓMICA
            ================================================== --}}

            <div class="form-section">

                <div class="form-section-title">

                    <i class="bi bi-cash-stack"></i>

                    <div>

                        <h3>
                            Información económica
                        </h3>

                        <p>
                            Define el precio de venta y costo del producto.
                        </p>

                    </div>

                </div>


                <div class="form-grid">


                    {{-- PRECIO --}}

                    <div class="form-group">

                        <label for="precio">
                            Precio de venta
                            <span class="required">*</span>
                        </label>

                        <div class="input-prefix">

                            <span>
                                S/
                            </span>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                id="precio"
                                name="precio"
                                value="{{ old('precio', $producto->precio) }}"
                                placeholder="0.00"
                                required
                            >

                        </div>

                        @error('precio')
                            <small class="field-error">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>


                    {{-- COSTO --}}

                    <div class="form-group">

                        <label for="costo">
                            Costo del producto
                            <span class="required">*</span>
                        </label>

                        <div class="input-prefix">

                            <span>
                                S/
                            </span>

                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                id="costo"
                                name="costo"
                                value="{{ old('costo', $producto->costo) }}"
                                placeholder="0.00"
                                required
                            >

                        </div>

                        @error('costo')
                            <small class="field-error">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>

                </div>

            </div>


            {{-- =================================================
                 CONTROL DE INVENTARIO
            ================================================== --}}

            <div class="form-section">

                <div class="form-section-title">

                    <i class="bi bi-box-seam"></i>

                    <div>

                        <h3>
                            Control de inventario
                        </h3>

                        <p>
                            Configura el stock y los niveles de seguridad.
                        </p>

                    </div>

                </div>


                <div class="form-grid">


                    {{-- STOCK ACTUAL --}}

                    <div class="form-group">

                        <label for="stock">
                            Stock actual
                            <span class="required">*</span>
                        </label>

                        <input
                            type="number"
                            min="0"
                            id="stock"
                            name="stock"
                            value="{{ old('stock', $producto->stock) }}"
                            required
                        >

                        @error('stock')
                            <small class="field-error">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>


                    {{-- STOCK MÍNIMO --}}

                    <div class="form-group">

                        <label for="stock_minimo">
                            Stock mínimo
                            <span class="required">*</span>
                        </label>

                        <input
                            type="number"
                            min="0"
                            id="stock_minimo"
                            name="stock_minimo"
                            value="{{ old('stock_minimo', $producto->stock_minimo) }}"
                            required
                        >

                        @error('stock_minimo')
                            <small class="field-error">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>


                    {{-- STOCK SEGURIDAD --}}

                    <div class="form-group">

                        <label for="stock_seguridad">
                            Stock de seguridad
                            <span class="required">*</span>
                        </label>

                        <input
                            type="number"
                            min="0"
                            id="stock_seguridad"
                            name="stock_seguridad"
                            value="{{ old('stock_seguridad', $producto->stock_seguridad) }}"
                            required
                        >

                        @error('stock_seguridad')
                            <small class="field-error">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>


                    {{-- ESTADO --}}

                    <div class="form-group">

                        <label for="activo">
                            Estado
                        </label>

                        <select
                            id="activo"
                            name="activo"
                        >

                            <option
                                value="1"
                                {{ (string) old('activo', $producto->activo ? '1' : '0') === '1' ? 'selected' : '' }}
                            >
                                Activo
                            </option>

                            <option
                                value="0"
                                {{ (string) old('activo', $producto->activo ? '1' : '0') === '0' ? 'selected' : '' }}
                            >
                                Inactivo
                            </option>

                        </select>

                        @error('activo')
                            <small class="field-error">
                                {{ $message }}
                            </small>
                        @enderror

                    </div>

                </div>

            </div>


            {{-- =================================================
                 INFORMACIÓN
            ================================================== --}}

            <div class="form-info">

                <i class="bi bi-info-circle"></i>

                <span>
                    Los cambios realizados actualizarán la información
                    del producto en el sistema.
                </span>

            </div>


            {{-- =================================================
                 BOTONES
            ================================================== --}}

            <div class="form-actions">

                <a
                    href="{{ route('productos.index') }}"
                    class="btn-secondary"
                >

                    <i class="bi bi-arrow-left"></i>

                    Cancelar

                </a>


                <button
                    type="submit"
                    class="btn-primary"
                >

                    <i class="bi bi-check-lg"></i>

                    Guardar cambios

                </button>

            </div>

        </form>

    </div>

</div>

@endsection