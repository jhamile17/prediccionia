@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/categorias.css') }}">
@endpush

@section('title', 'Editar categoría | PrediccionIA')

@section('content')

<div class="category-form-page">

    {{-- =====================================================
         ENCABEZADO
    ====================================================== --}}

    <div class="page-header">

        <div>

            <span class="page-kicker">
                GESTIÓN DE CATEGORÍAS
            </span>

            <h1>
                Editar categoría
            </h1>

            <p>
                Actualiza la información de la categoría seleccionada.
            </p>

        </div>

    </div>


    {{-- =====================================================
         ERRORES
    ====================================================== --}}

    @if($errors->any())

        <div class="alert-error">

            <i class="bi bi-exclamation-circle-fill"></i>

            <div>

                <strong>
                    Revisa los siguientes errores:
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
         TARJETA DEL FORMULARIO
    ====================================================== --}}

    <div class="category-form-card">


        {{-- =================================================
             CABECERA
        ================================================== --}}

        <div class="form-card-header">

            <div class="form-header-icon">
                <i class="bi bi-tag"></i>
            </div>

            <div>

                <h2>
                    Información de la categoría
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
            action="{{ route('categorias.update', $categoria) }}"
            method="POST"
            class="category-form"
        >

            @csrf

            @method('PUT')


            {{-- =============================================
                 NOMBRE
            ============================================== --}}

            <div class="form-group">

                <label for="nombre">

                    Nombre de la categoría

                    <span class="required">
                        *
                    </span>

                </label>

                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    value="{{ old('nombre', $categoria->nombre) }}"
                    placeholder="Ej. Café"
                    maxlength="150"
                    required
                >

                @error('nombre')

                    <span class="field-error">
                        {{ $message }}
                    </span>

                @enderror

            </div>


            {{-- =============================================
                 DESCRIPCIÓN
            ============================================== --}}

            <div class="form-group">

                <label for="descripcion">
                    Descripción
                </label>

                <textarea
                    id="descripcion"
                    name="descripcion"
                    rows="5"
                    maxlength="500"
                    placeholder="Describe brevemente esta categoría..."
                >{{ old('descripcion', $categoria->descripcion) }}</textarea>

                @error('descripcion')

                    <span class="field-error">
                        {{ $message }}
                    </span>

                @enderror

            </div>


            {{-- =============================================
                 ESTADO
            ============================================== --}}

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
                        {{ old('activo', $categoria->activo) ? 'selected' : '' }}
                    >
                        Activa
                    </option>

                    <option
                        value="0"
                        {{ !old('activo', $categoria->activo) ? 'selected' : '' }}
                    >
                        Inactiva
                    </option>

                </select>

            </div>


            {{-- =============================================
                 INFORMACIÓN
            ============================================== --}}

            <div class="form-info">

                <i class="bi bi-info-circle"></i>

                <span>
                    Los productos asociados a esta categoría
                    no serán eliminados al cambiar su estado.
                </span>

            </div>


            {{-- =============================================
                 BOTONES
            ============================================== --}}

            <div class="form-actions">

                <a
                    href="{{ route('categorias.index') }}"
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