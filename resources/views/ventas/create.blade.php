@extends('layouts.app')

@section('title', 'Nueva venta | PrediccionIA')

@push('styles')
    <link
        rel="stylesheet"
        href="{{ asset('css/ventas.css') }}"
    >

    <style>
        .sales-create-page {
            max-width: 1080px;
            margin: 0 auto;
        }

        .sales-create-layout {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(300px, 0.8fr);
            gap: 24px;
            align-items: start;
        }

        .sales-create-card,
        .sales-create-info {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 22px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
        }

        .sales-create-card {
            padding: 30px;
        }

        .sales-create-info {
            padding: 26px;
        }

        .sales-create-card-header {
            margin-bottom: 28px;
        }

        .sales-create-card-header span,
        .sales-info-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #2563eb;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .sales-create-card-header h2 {
            margin: 8px 0 6px;
            font-size: 28px;
            color: #0f172a;
        }

        .sales-create-card-header p {
            margin: 0;
            color: #64748b;
            line-height: 1.6;
        }

        .sales-form-group {
            margin-bottom: 22px;
        }

        .sales-form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 700;
            color: #334155;
        }

        .sales-form-group select,
        .sales-form-group input {
            width: 100%;
            min-height: 52px;
            padding: 0 16px;
            border: 1px solid #dbe2ea;
            border-radius: 14px;
            background: #f8fafc;
            color: #0f172a;
            font-size: 15px;
            outline: none;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .sales-form-group select:focus,
        .sales-form-group input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.10);
            background: #ffffff;
        }

        .sales-product-meta {
            margin-top: 10px;
            padding: 14px 16px;
            border-radius: 14px;
            background: #eff6ff;
            color: #1e40af;
            font-size: 14px;
            display: none;
        }

        .sales-product-meta.visible {
            display: block;
        }

        .sales-product-meta strong {
            font-weight: 800;
        }

        .sales-error {
            margin-top: 8px;
            color: #dc2626;
            font-size: 13px;
        }

        .sales-success,
        .sales-alert-error {
            margin-bottom: 20px;
            padding: 15px 17px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 600;
        }

        .sales-success {
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .sales-alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }

        .sales-form-actions {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .sales-form-button,
        .sales-back-button {
            min-height: 50px;
            padding: 0 22px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            cursor: pointer;
            border: none;
        }

        .sales-form-button {
            flex: 1;
            background: #2563eb;
            color: #ffffff;
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.20);
        }

        .sales-form-button:hover {
            background: #1d4ed8;
        }

        .sales-back-button {
            background: #f1f5f9;
            color: #334155;
        }

        .sales-back-button:hover {
            background: #e2e8f0;
        }

        .sales-info-title {
            margin: 9px 0 10px;
            color: #0f172a;
            font-size: 22px;
        }

        .sales-info-description {
            margin: 0 0 20px;
            color: #64748b;
            line-height: 1.6;
            font-size: 14px;
        }

        .sales-info-list {
            display: grid;
            gap: 12px;
        }

        .sales-info-item {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 13px;
            border-radius: 14px;
            background: #f8fafc;
        }

        .sales-info-item-icon {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #dbeafe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 34px;
        }

        .sales-info-item strong {
            display: block;
            margin-bottom: 3px;
            color: #1e293b;
            font-size: 14px;
        }

        .sales-info-item span {
            color: #64748b;
            font-size: 13px;
            line-height: 1.45;
        }

        .sales-create-summary {
            margin-top: 18px;
            padding: 18px;
            border-radius: 16px;
            background: linear-gradient(
                135deg,
                #eff6ff 0%,
                #f8fafc 100%
            );
            border: 1px solid #dbeafe;
        }

        .sales-create-summary span {
            display: block;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .sales-create-summary strong {
            display: block;
            margin-top: 5px;
            color: #1d4ed8;
            font-size: 26px;
        }

        @media (max-width: 900px) {
            .sales-create-layout {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .sales-create-card,
            .sales-create-info {
                padding: 20px;
                border-radius: 18px;
            }

            .sales-form-actions {
                flex-direction: column;
            }

            .sales-back-button,
            .sales-form-button {
                width: 100%;
            }
        }
    </style>
@endpush

@section('content')

<main class="sales-page sales-create-page">

    {{-- =========================================================
         ENCABEZADO
    ========================================================== --}}

    <header class="sales-hero">

        <div>

            <span class="sales-eyebrow">
                VENTAS
            </span>

            <h1>
                Registrar una nueva venta
            </h1>

            <p>
                Ingresa una venta y actualiza automáticamente
                el inventario del producto.
            </p>

        </div>

    </header>


    {{-- =========================================================
         MENSAJES
    ========================================================== --}}

    @if(session('success'))

        <div class="sales-success">

            <i class="bi bi-check-circle-fill"></i>

            {{ session('success') }}

        </div>

    @endif


    @if(session('error'))

        <div class="sales-alert-error">

            <i class="bi bi-exclamation-circle-fill"></i>

            {{ session('error') }}

        </div>

    @endif


    @if($errors->any())

        <div class="sales-alert-error">

            <strong>
                No se pudo registrar la venta.
            </strong>

            @foreach($errors->all() as $error)
                <div style="margin-top: 4px;">
                    {{ $error }}
                </div>
            @endforeach

        </div>

    @endif


    {{-- =========================================================
         CONTENIDO
    ========================================================== --}}

    <section class="sales-create-layout">

        {{-- =====================================================
             FORMULARIO
        ====================================================== --}}

        <article class="sales-create-card">

            <div class="sales-create-card-header">

                <span>
                    <i class="bi bi-plus-circle"></i>
                    Nueva operación
                </span>

                <h2>
                    Registrar venta
                </h2>

                <p>
                    Selecciona el producto y la cantidad vendida.
                    El sistema calculará el total automáticamente.
                </p>

            </div>


            <form
                method="POST"
                action="{{ route('ventas.store') }}"
            >

                @csrf


                {{-- PRODUCTO --}}

                <div class="sales-form-group">

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
                                data-precio="{{ (float) $producto->precio }}"
                                data-stock="{{ (int) $producto->stock }}"
                                {{ old('producto_id') == $producto->id ? 'selected' : '' }}
                            >
                                {{ $producto->nombre }}
                                — S/ {{ number_format((float) $producto->precio, 2) }}
                            </option>

                        @endforeach

                    </select>


                    <div
                        id="salesProductMeta"
                        class="sales-product-meta"
                    >

                        <div>
                            <strong>Precio:</strong>
                            <span id="salesProductPrice">
                                S/ 0.00
                            </span>
                        </div>

                        <div style="margin-top: 4px;">
                            <strong>Stock disponible:</strong>
                            <span id="salesProductStock">
                                0
                            </span>
                            unidades
                        </div>

                    </div>


                    @error('producto_id')

                        <div class="sales-error">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- CANTIDAD --}}

                <div class="sales-form-group">

                    <label for="cantidad">
                        Cantidad
                    </label>

                    <input
                        type="number"
                        name="cantidad"
                        id="cantidad"
                        min="1"
                        max="999"
                        value="{{ old('cantidad', 1) }}"
                        required
                    >

                    @error('cantidad')

                        <div class="sales-error">
                            {{ $message }}
                        </div>

                    @enderror

                </div>


                {{-- RESUMEN TOTAL --}}

                <div class="sales-create-summary">

                    <span>
                        Total de la venta
                    </span>

                    <strong id="salesTotal">
                        S/ 0.00
                    </strong>

                </div>


                {{-- ACCIONES --}}

                <div class="sales-form-actions">

                    <a
                        href="{{ route('ventas.index') }}"
                        class="sales-back-button"
                    >
                        <i class="bi bi-arrow-left"></i>
                        Volver
                    </a>

                    <button
                        type="submit"
                        class="sales-form-button"
                    >
                        <i class="bi bi-check2-circle"></i>
                        Registrar venta
                    </button>

                </div>

            </form>

        </article>


        {{-- =====================================================
             INFORMACIÓN
        ====================================================== --}}

        <aside class="sales-create-info">

            <span class="sales-info-eyebrow">
                <i class="bi bi-info-circle"></i>
                ¿Qué sucede?
            </span>

            <h2 class="sales-info-title">
                El sistema actualiza todo
            </h2>

            <p class="sales-info-description">
                Al confirmar la venta, el sistema realiza
                las operaciones relacionadas automáticamente.
            </p>


            <div class="sales-info-list">

                <div class="sales-info-item">

                    <div class="sales-info-item-icon">
                        <i class="bi bi-receipt"></i>
                    </div>

                    <div>

                        <strong>
                            1. Registra la venta
                        </strong>

                        <span>
                            Se crea la operación y se guarda
                            su total.
                        </span>

                    </div>

                </div>


                <div class="sales-info-item">

                    <div class="sales-info-item-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>

                    <div>

                        <strong>
                            2. Guarda el detalle
                        </strong>

                        <span>
                            Se registra qué producto y
                            cantidad se vendió.
                        </span>

                    </div>

                </div>


                <div class="sales-info-item">

                    <div class="sales-info-item-icon">
                        <i class="bi bi-database-check"></i>
                    </div>

                    <div>

                        <strong>
                            3. Actualiza el stock
                        </strong>

                        <span>
                            La cantidad vendida se descuenta
                            del inventario.
                        </span>

                    </div>

                </div>


                <div class="sales-info-item">

                    <div class="sales-info-item-icon">
                        <i class="bi bi-arrow-repeat"></i>
                    </div>

                    <div>

                        <strong>
                            4. Registra el movimiento
                        </strong>

                        <span>
                            Queda registrada la salida
                            generada por la venta.
                        </span>

                    </div>

                </div>

            </div>

        </aside>

    </section>

</main>


@push('scripts')

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const productoSelect =
            document.getElementById(
                'producto_id'
            );

        const cantidadInput =
            document.getElementById(
                'cantidad'
            );

        const meta =
            document.getElementById(
                'salesProductMeta'
            );

        const price =
            document.getElementById(
                'salesProductPrice'
            );

        const stock =
            document.getElementById(
                'salesProductStock'
            );

        const total =
            document.getElementById(
                'salesTotal'
            );


        function actualizarResumen() {

            const option =
                productoSelect.options[
                    productoSelect.selectedIndex
                ];

            if (
                !option ||
                !option.value
            ) {

                meta.classList.remove(
                    'visible'
                );

                price.textContent =
                    'S/ 0.00';

                stock.textContent =
                    '0';

                total.textContent =
                    'S/ 0.00';

                return;

            }


            const precio =
                parseFloat(
                    option.dataset.precio || 0
                );

            const stockDisponible =
                parseInt(
                    option.dataset.stock || 0,
                    10
                );

            const cantidad =
                parseInt(
                    cantidadInput.value || 0,
                    10
                );


            meta.classList.add(
                'visible'
            );

            price.textContent =
                'S/ ' +
                precio.toFixed(2);

            stock.textContent =
                stockDisponible;


            const subtotal =
                precio *
                cantidad;

            total.textContent =
                'S/ ' +
                subtotal.toFixed(2);


            cantidadInput.max =
                stockDisponible > 0
                    ? stockDisponible
                    : 1;

        }


        productoSelect.addEventListener(
            'change',
            actualizarResumen
        );


        cantidadInput.addEventListener(
            'input',
            actualizarResumen
        );


        actualizarResumen();

    }

);

</script>

@endpush

@endsection