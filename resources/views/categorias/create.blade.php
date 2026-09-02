@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/categorias.css') }}">
@endpush

@section('title', 'Nueva categoría | PrediccionIA')

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
                Nueva categoría
            </h1>

            <p>
                Registra una nueva categoría para organizar
                los productos del sistema.
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

                @foreach($errors->all() as $error)

                    <span>
                        {{ $error }}
                    </span>

                @endforeach

            </div>

        </div>

    @endif


    {{-- =====================================================
         FORMULARIO
    ====================================================== --}}

    <div class="category-form-card">

        <div class="form-card-header">

            <div class="form-header-icon">
                <i class="bi bi-tag"></i>
            </div>

            <div>

                <h2>
                    Información de la categoría
                </h2>

                <p>
                    Completa los datos necesarios para registrar
                    la nueva categoría.
                </p>

            </div>

        </div>


        <form
            action="{{ route('categorias.store') }}"
            method="POST"
            class="category-form"
        >

            @csrf


            {{-- =============================================
                 NOMBRE
            ============================================== --}}

            <div class="form-group">

                <label for="nombre">
                    Nombre de la categoría
                    <span>*</span>
                </label>

                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    value="{{ old('nombre') }}"
                    placeholder="Ej. Bebidas calientes"
                    maxlength="100"
                    required
                    autofocus
                >

                @error('nombre')
                    <small class="field-error">
                        {{ $message }}
                    </small>
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
                >{{ old('descripcion') }}</textarea>

                @error('descripcion')
                    <small class="field-error">
                        {{ $message }}
                    </small>
                @enderror

            </div>


            {{-- =============================================
                 INFORMACIÓN
            ============================================== --}}

            <div class="form-info">

                <i class="bi bi-info-circle"></i>

                <div>

                    <strong>
                        Estado inicial
                    </strong>

                    <p>
                        La nueva categoría se registrará como
                        <strong>Activa</strong>.
                    </p>

                </div>

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
                    Registrar categoría
                </button>

            </div>

        </form>

    </div>

</div>

@endsection