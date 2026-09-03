@extends('layouts.app')

@section('title', 'Inicio | PrediccionIA')

@push('styles')
    <link
        rel="stylesheet"
        href="{{ asset('css/dashboard.css') }}"
    >
@endpush

@section('content')

@php

    $totalProductos =
        (int) ($dashboard['total_productos'] ?? 0);

    $reposicionInmediata =
        (int) ($dashboard['reposicion_inmediata'] ?? 0);

    $reposicionPronta =
        (int) ($dashboard['reposicion_pronta'] ?? 0);

    $productosEstables =
        (int) ($dashboard['productos_estables'] ?? 0);

    $totalAtencion =
        (int) ($dashboard['total_atencion'] ?? 0);

    $stockTotal =
        (int) ($dashboard['stock_total'] ?? 0);

    $demandaHoy =
        (int) ($dashboard['demanda_hoy'] ?? 0);

    $faltanteTotal =
        (int) ($dashboard['faltante_total'] ?? 0);

    $porcentajeEstable =
        (int) ($dashboard['porcentaje_estable'] ?? 0);

    $prioridades =
        collect($dashboard['prioridades'] ?? []);

    $productosRiesgo =
        collect($dashboard['productos_riesgo'] ?? []);

    $maxDemandaGrafico =
        max(
            1,
            (int) ($dashboard['max_demanda_grafico'] ?? 1)
        );

    $estado =
        $dashboard['estado'] ?? [];

    $recomendacion =
        $dashboard['recomendacion'] ?? [];

@endphp


<main class="dashboard-page">

    {{-- =========================================================
         HEADER
    ========================================================== --}}

    <header class="dashboard-header">

        <div class="dashboard-header-main">

            <span class="dashboard-eyebrow">
                CENTRO DE CONTROL
            </span>

            <h1>
                {{ $dashboard['saludo'] ?? 'Hola' }},
                Administrador
            </h1>

            <p>
                Aquí tienes lo más importante para decidir qué hacer hoy.
            </p>

        </div>

        <div class="dashboard-date">

            <span>
                HOY
            </span>

            <strong>
                {{ $dashboard['fecha_actual'] ?? '' }}
            </strong>

        </div>

    </header>


    {{-- =========================================================
         ESTADO PRINCIPAL
    ========================================================== --}}

    <section class="dashboard-status-card">

        <div class="status-card-content">

            <div class="status-card-icon {{ $estado['clase'] ?? 'success' }}">

                @if(($estado['clase'] ?? '') === 'danger')

                    <i class="bi bi-exclamation-lg"></i>

                @elseif(($estado['clase'] ?? '') === 'warning')

                    <i class="bi bi-eye"></i>

                @else

                    <i class="bi bi-check-lg"></i>

                @endif

            </div>

            <div>

                <span class="dashboard-label">
                    ESTADO DE HOY
                </span>

                <h2>
                    {{ $estado['titulo'] ?? 'Inventario estable' }}
                </h2>

                <p>
                    {{ $estado['texto'] ?? '' }}
                </p>

            </div>

        </div>

        <div class="status-card-summary">

            <strong>
                {{ $porcentajeEstable }}%
            </strong>

            <span>
                de los productos
                bajo control
            </span>

        </div>

    </section>


    {{-- =========================================================
         INDICADORES RÁPIDOS
    ========================================================== --}}

    <section class="dashboard-metrics">

        <article class="metric-card metric-danger">

            <div class="metric-icon">

                <i class="bi bi-exclamation-triangle"></i>

            </div>

            <div class="metric-content">

                <span>
                    Necesitan atención
                </span>

                <strong>
                    {{ $reposicionInmediata }}
                </strong>

                <small>
                    reposición prioritaria
                </small>

            </div>

        </article>


        <article class="metric-card metric-warning">

            <div class="metric-icon">

                <i class="bi bi-clock-history"></i>

            </div>

            <div class="metric-content">

                <span>
                    Para vigilar
                </span>

                <strong>
                    {{ $reposicionPronta }}
                </strong>

                <small>
                    cerca del nivel mínimo
                </small>

            </div>

        </article>


        <article class="metric-card metric-neutral">

            <div class="metric-icon">

                <i class="bi bi-box-seam"></i>

            </div>

            <div class="metric-content">

                <span>
                    Stock disponible
                </span>

                <strong>
                    {{ number_format($stockTotal) }}
                </strong>

                <small>
                    unidades en inventario
                </small>

            </div>

        </article>


        <article class="metric-card metric-purple">

            <div class="metric-icon">

                <i class="bi bi-graph-up-arrow"></i>

            </div>

            <div class="metric-content">

                <span>
                    Movimiento estimado
                </span>

                <strong>
                    {{ number_format($demandaHoy) }}
                </strong>

                <small>
                    unidades para hoy
                </small>

            </div>

        </article>

    </section>


    {{-- =========================================================
         PRIORIDADES + ESTADO
    ========================================================== --}}

    <section class="dashboard-main-grid">


        {{-- =====================================================
             PRIORIDADES
        ====================================================== --}}

        <article class="dashboard-panel priority-panel">

            <header class="panel-header">

                <div>

                    <span class="dashboard-label">
                        ACCIÓN RECOMENDADA
                    </span>

                    <h2>
                        ¿Qué debo atender ahora?
                    </h2>

                    <p>
                        Empieza por los productos con mayor riesgo
                        de quedarse cortos.
                    </p>

                </div>

                @if($totalAtencion > 0)

                    <span class="panel-count danger">
                        {{ $totalAtencion }}
                    </span>

                @else

                    <span class="panel-check">

                        <i class="bi bi-check-circle-fill"></i>

                    </span>

                @endif

            </header>


            @if($prioridades->count() > 0)

                <div class="priority-list">

                    @foreach($prioridades as $indice => $producto)

                        @php

                            $stock =
                                (int) (
                                    $producto['stock_actual'] ?? 0
                                );

                            $demanda =
                                (int) (
                                    $producto['demanda_predicha'] ?? 0
                                );

                            $faltante =
                                (int) (
                                    $producto['faltante_estimado'] ?? 0
                                );

                            $nivel =
                                $producto['nivel'] ?? 'pronto';

                        @endphp


                        <div class="priority-item">

                            {{-- NÚMERO --}}

                            <div class="priority-index
                                {{ $nivel === 'inmediata'
                                    ? 'danger'
                                    : 'warning'
                                }}"
                            >
                                {{ str_pad(
                                    $indice + 1,
                                    2,
                                    '0',
                                    STR_PAD_LEFT
                                ) }}
                            </div>


                            {{-- INFORMACIÓN --}}

                            <div class="priority-info">

                                <div class="priority-title-row">

                                    <strong>
                                        {{ $producto['producto'] ?? 'Producto' }}
                                    </strong>


                                    @if($nivel === 'inmediata')

                                        <span class="priority-badge danger">

                                            <i></i>

                                            Atención ahora

                                        </span>

                                    @else

                                        <span class="priority-badge warning">

                                            <i></i>

                                            Vigilar

                                        </span>

                                    @endif

                                </div>


                                <span class="priority-description">

                                    @if($nivel === 'inmediata')

                                        El stock disponible no alcanza
                                        para cubrir el movimiento esperado.

                                    @else

                                        El producto está cerca de su
                                        nivel mínimo.

                                    @endif

                                </span>


                                <div class="priority-numbers">

                                    <span>

                                        Stock

                                        <strong>
                                            {{ $stock }}
                                        </strong>

                                    </span>


                                    <span>

                                        Esperado

                                        <strong>
                                            {{ $demanda }}
                                        </strong>

                                    </span>


                                    @if($faltante > 0)

                                        <span class="priority-shortage">

                                            Faltan

                                            <strong>
                                                {{ $faltante }}
                                            </strong>

                                        </span>

                                    @endif

                                </div>

                            </div>


                            {{-- =================================================
                                 ACCIÓN
                            ================================================== --}}

                            @if($nivel === 'inmediata')

                                <button
                                    type="button"
                                    class="priority-action danger js-open-reposition"
                                    data-producto-id="{{ $producto['producto_id'] ?? '' }}"
                                    data-producto="{{ $producto['producto'] ?? 'Producto' }}"
                                    data-stock="{{ $stock }}"
                                    data-demanda="{{ $demanda }}"
                                    data-faltante="{{ $faltante }}"
                                >

                                    Reponer

                                    <i class="bi bi-arrow-right"></i>

                                </button>

                            @else

                                <a
                                    href="{{ route('inventario.index') }}"
                                    class="priority-action warning"
                                >

                                    Revisar

                                    <i class="bi bi-arrow-right"></i>

                                </a>

                            @endif

                        </div>

                    @endforeach

                </div>

            @else

                <div class="dashboard-empty">

                    <div class="dashboard-empty-icon">

                        <i class="bi bi-check2-circle"></i>

                    </div>

                    <h3>
                        No hay tareas urgentes
                    </h3>

                    <p>
                        El inventario puede continuar con normalidad.
                    </p>

                </div>

            @endif

        </article>


        {{-- =====================================================
             DONUT
        ====================================================== --}}

        <article class="dashboard-panel health-panel">

            <header class="panel-header">

                <div>

                    <span class="dashboard-label">
                        EN UNA MIRADA
                    </span>

                    <h2>
                        Estado del inventario
                    </h2>

                </div>

            </header>


            @php

                $porcentajeDanger =
                    $totalProductos > 0
                        ? round(
                            (
                                $reposicionInmediata /
                                $totalProductos
                            ) * 100
                        )
                        : 0;

                $porcentajeWarning =
                    $totalProductos > 0
                        ? round(
                            (
                                $reposicionPronta /
                                $totalProductos
                            ) * 100
                        )
                        : 0;

            @endphp


            <div class="health-visual">

                <div
                    class="health-donut"
                    style="
                        background:
                            conic-gradient(
                                #16a34a 0 {{ $porcentajeEstable }}%,
                                #f59e0b {{ $porcentajeEstable }}% {{ $porcentajeEstable + $porcentajeWarning }}%,
                                #ef4444 {{ $porcentajeEstable + $porcentajeWarning }}% 100%
                            );
                    "
                >

                    <div class="health-donut-center">

                        <strong>
                            {{ $totalProductos }}
                        </strong>

                        <span>
                            productos
                        </span>

                    </div>

                </div>

            </div>


            <div class="health-legend">

                <div>

                    <span class="legend-dot stable"></span>

                    <div>

                        <strong>
                            {{ $productosEstables }}
                        </strong>

                        <span>
                            bajo control
                        </span>

                    </div>

                </div>


                <div>

                    <span class="legend-dot warning"></span>

                    <div>

                        <strong>
                            {{ $reposicionPronta }}
                        </strong>

                        <span>
                            para vigilar
                        </span>

                    </div>

                </div>


                <div>

                    <span class="legend-dot danger"></span>

                    <div>

                        <strong>
                            {{ $reposicionInmediata }}
                        </strong>

                        <span>
                            requieren acción
                        </span>

                    </div>

                </div>

            </div>

        </article>

    </section>


    {{-- =========================================================
         GRÁFICO DE PRESIÓN DE STOCK
    ========================================================== --}}

    <section class="dashboard-panel pressure-panel">

        <header class="panel-header pressure-header">

            <div>

                <span class="dashboard-label">
                    PRESIÓN DE STOCK
                </span>

                <h2>
                    ¿Dónde puede aparecer el próximo faltante?
                </h2>

                <p>
                    Compara el movimiento esperado con las unidades
                    que tienes disponibles hoy.
                </p>

            </div>


            <a
                href="{{ route('inventario.index') }}"
                class="panel-link"
            >

                Ver inventario

                <i class="bi bi-arrow-right"></i>

            </a>

        </header>


        <div class="pressure-list">

            @foreach($productosRiesgo as $producto)

                @php

                    $stock =
                        (int) (
                            $producto['stock_actual'] ?? 0
                        );

                    $demanda =
                        (int) (
                            $producto['demanda_predicha'] ?? 0
                        );

                    $faltante =
                        (int) (
                            $producto['faltante_estimado'] ?? 0
                        );

                    $stockWidth =
                        min(
                            100,
                            round(
                                ($stock / $maxDemandaGrafico) * 100
                            )
                        );

                    $demandaWidth =
                        min(
                            100,
                            round(
                                ($demanda / $maxDemandaGrafico) * 100
                            )
                        );

                @endphp


                <div class="pressure-row">

                    <div class="pressure-name">

                        <strong>
                            {{ $producto['producto'] ?? 'Producto' }}
                        </strong>


                        @if($faltante > 0)

                            <span class="pressure-risk">
                                Faltan {{ $faltante }}
                            </span>

                        @else

                            <span class="pressure-ok">
                                Cubierto
                            </span>

                        @endif

                    </div>


                    <div class="pressure-bars">

                        <div class="pressure-line">

                            <span class="pressure-line-label">
                                Esperado
                            </span>

                            <div class="pressure-track">

                                <span
                                    class="pressure-bar expected"
                                    style="width: {{ $demandaWidth }}%"
                                ></span>

                            </div>

                            <strong>
                                {{ $demanda }}
                            </strong>

                        </div>


                        <div class="pressure-line">

                            <span class="pressure-line-label">
                                Disponible
                            </span>

                            <div class="pressure-track">

                                <span
                                    class="pressure-bar available
                                        {{ $faltante > 0 ? 'low' : '' }}"
                                    style="width: {{ $stockWidth }}%"
                                ></span>

                            </div>

                            <strong>
                                {{ $stock }}
                            </strong>

                        </div>

                    </div>

                </div>

            @endforeach

        </div>

    </section>


    {{-- =========================================================
         RECOMENDACIÓN
    ========================================================== --}}

    <section class="dashboard-recommendation">

        <div class="recommendation-symbol">

            <i class="bi bi-stars"></i>

        </div>


        <div class="recommendation-content">

            <span class="dashboard-label">
                RECOMENDACIÓN DEL SISTEMA
            </span>

            <h2>
                {{ $recomendacion['titulo'] ?? '' }}
            </h2>

            <p>
                {{ $recomendacion['texto'] ?? '' }}
            </p>

        </div>


        <div class="recommendation-action">

            @if(($recomendacion['nivel'] ?? '') === 'danger')

                <a
                    href="{{ route('inventario.index') }}"
                    class="recommendation-button danger"
                >

                    Revisar ahora

                    <i class="bi bi-arrow-right"></i>

                </a>

            @elseif(($recomendacion['nivel'] ?? '') === 'warning')

                <a
                    href="{{ route('inventario.index') }}"
                    class="recommendation-button warning"
                >

                    Revisar inventario

                    <i class="bi bi-arrow-right"></i>

                </a>

            @else

                <a
                    href="{{ route('inventario.index') }}"
                    class="recommendation-button"
                >

                    Ver inventario

                    <i class="bi bi-arrow-right"></i>

                </a>

            @endif

        </div>

    </section>


    {{-- =========================================================
         PIE
    ========================================================== --}}

    <div class="dashboard-footer-note">

        <i class="bi bi-info-circle"></i>

        <span>
            Las recomendaciones se basan en el stock disponible
            y en la demanda estimada para hoy.
        </span>

    </div>

</main>


{{-- =============================================================
     MODAL DE REPOSICIÓN
============================================================= --}}

<div
    class="reposition-modal"
    id="repositionModal"
    aria-hidden="true"
>

    <div class="reposition-modal-backdrop"></div>


    <div
        class="reposition-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="repositionModalTitle"
    >

        {{-- CERRAR --}}

        <button
            type="button"
            class="reposition-close"
            id="repositionClose"
            aria-label="Cerrar"
        >

            <i class="bi bi-x-lg"></i>

        </button>


        {{-- ICONO --}}

        <div class="reposition-icon">

            <i class="bi bi-box-arrow-in-down"></i>

        </div>


        <span class="dashboard-label">
            REPOSICIÓN SUGERIDA
        </span>


        <h2 id="repositionModalTitle">
            Reponer producto
        </h2>


        <p class="reposition-description">

            El sistema detectó que el stock actual no cubre
            la demanda estimada.

        </p>


        {{-- RESUMEN --}}

        <div class="reposition-summary">

            <div>

                <span>
                    Stock actual
                </span>

                <strong id="repositionStock">
                    0
                </strong>

                <small>
                    unidades
                </small>

            </div>


            <div>

                <span>
                    Demanda estimada
                </span>

                <strong id="repositionDemand">
                    0
                </strong>

                <small>
                    unidades
                </small>

            </div>


            <div class="highlight">

                <span>
                    Faltante estimado
                </span>

                <strong id="repositionShortage">
                    0
                </strong>

                <small>
                    unidades
                </small>

            </div>

        </div>


        {{-- FORMULARIO --}}

        <form
            id="repositionForm"
            method="POST"
            action="{{ route('inventario.movimiento.store') }}"
        >

            @csrf


            <input
                type="hidden"
                name="producto_id"
                id="repositionProductId"
            >


            <input
                type="hidden"
                name="tipo"
                value="reposicion"
            >


            <div class="reposition-field">

                <label for="repositionQuantity">
                    Cantidad a reponer
                </label>


                <div class="reposition-input-wrapper">

                    <input
                        type="number"
                        name="cantidad"
                        id="repositionQuantity"
                        min="1"
                        step="1"
                        required
                    >

                    <span>
                        unidades
                    </span>

                </div>


                <small>

                    La cantidad sugerida se calcula a partir
                    del faltante estimado.

                </small>

            </div>


            <div class="reposition-note">

                <i class="bi bi-info-circle"></i>

                <span>

                    La reposición solo se realizará después
                    de tu confirmación.

                </span>

            </div>


            <div
                class="reposition-error"
                id="repositionError"
            ></div>


            <div class="reposition-actions">

                <button
                    type="button"
                    class="reposition-cancel"
                    id="repositionCancel"
                >
                    Cancelar
                </button>


                <button
                    type="submit"
                    class="reposition-confirm"
                    id="repositionConfirm"
                >

                    <span class="reposition-confirm-text">
                        Confirmar reposición
                    </span>


                    <span
                        class="reposition-loading"
                        hidden
                    >
                        Procesando...
                    </span>


                    <i class="bi bi-check-lg"></i>

                </button>

            </div>

        </form>

    </div>

</div>


{{-- =============================================================
     NOTIFICACIÓN DE ÉXITO
============================================================= --}}

<div
    class="dashboard-toast"
    id="dashboardToast"
    aria-hidden="true"
>

    <div class="dashboard-toast-icon">

        <i class="bi bi-check-lg"></i>

    </div>


    <div>

        <strong id="toastTitle">
            Reposición registrada
        </strong>

        <span id="toastMessage">
            El inventario ha sido actualizado.
        </span>

    </div>

</div>

@endsection


{{-- =============================================================
     JAVASCRIPT
============================================================= --}}

@push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {

    /*
     * ==========================================================
     * ELEMENTOS
     * ==========================================================
     */

    const modal =
        document.getElementById('repositionModal');

    const form =
        document.getElementById('repositionForm');

    const closeButton =
        document.getElementById('repositionClose');

    const cancelButton =
        document.getElementById('repositionCancel');

    const productIdInput =
        document.getElementById('repositionProductId');

    const quantityInput =
        document.getElementById('repositionQuantity');

    const stockElement =
        document.getElementById('repositionStock');

    const demandElement =
        document.getElementById('repositionDemand');

    const shortageElement =
        document.getElementById('repositionShortage');

    const titleElement =
        document.getElementById('repositionModalTitle');

    const errorElement =
        document.getElementById('repositionError');

    const confirmButton =
        document.getElementById('repositionConfirm');

    const confirmText =
        document.querySelector('.reposition-confirm-text');

    const loadingText =
        document.querySelector('.reposition-loading');

    const toast =
        document.getElementById('dashboardToast');

    const toastTitle =
        document.getElementById('toastTitle');

    const toastMessage =
        document.getElementById('toastMessage');


    /*
     * ==========================================================
     * VALIDAR QUE EXISTAN LOS ELEMENTOS
     * ==========================================================
     */

    if (
        !modal ||
        !form ||
        !closeButton ||
        !cancelButton ||
        !productIdInput ||
        !quantityInput
    ) {

        console.error(
            'Dashboard: no se encontraron los elementos del modal de reposición.'
        );

        return;

    }


    /*
     * ==========================================================
     * ABRIR MODAL
     * ==========================================================
     */

    document
        .querySelectorAll('.js-open-reposition')
        .forEach(function (button) {

            button.addEventListener(
                'click',
                function () {

                    const productoId =
                        this.dataset.productoId || '';

                    const producto =
                        this.dataset.producto || 'Producto';

                    const stock =
                        Number(
                            this.dataset.stock || 0
                        );

                    const demanda =
                        Number(
                            this.dataset.demanda || 0
                        );

                    const faltante =
                        Number(
                            this.dataset.faltante || 0
                        );


                    /*
                     * Datos del producto
                     */

                    productIdInput.value =
                        productoId;

                    titleElement.textContent =
                        'Reponer ' + producto;

                    stockElement.textContent =
                        stock;

                    demandElement.textContent =
                        demanda;

                    shortageElement.textContent =
                        faltante;


                    /*
                     * Cantidad sugerida
                     */

                    quantityInput.value =
                        Math.max(
                            1,
                            faltante
                        );

                    quantityInput.max =
                        9999;


                    /*
                     * Limpiar errores
                     */

                    errorElement.textContent =
                        '';

                    errorElement.classList.remove(
                        'visible'
                    );


                    /*
                     * Abrir
                     */

                    modal.classList.add(
                        'is-open'
                    );

                    modal.setAttribute(
                        'aria-hidden',
                        'false'
                    );

                    document.body.classList.add(
                        'modal-open-dashboard'
                    );


                    /*
                     * Enfocar cantidad
                     */

                    setTimeout(
                        function () {

                            quantityInput.focus();

                            quantityInput.select();

                        },
                        100
                    );

                }
            );

        });


    /*
     * ==========================================================
     * CERRAR MODAL
     * ==========================================================
     */

    function cerrarModal() {

        modal.classList.remove(
            'is-open'
        );

        modal.setAttribute(
            'aria-hidden',
            'true'
        );

        document.body.classList.remove(
            'modal-open-dashboard'
        );

        errorElement.textContent =
            '';

        errorElement.classList.remove(
            'visible'
        );

    }


    /*
     * BOTÓN X
     */

    closeButton.addEventListener(
        'click',
        cerrarModal
    );


    /*
     * BOTÓN CANCELAR
     */

    cancelButton.addEventListener(
        'click',
        cerrarModal
    );


    /*
     * FONDO
     */

    const backdrop =
        modal.querySelector(
            '.reposition-modal-backdrop'
        );

    if (backdrop) {

        backdrop.addEventListener(
            'click',
            cerrarModal
        );

    }


    /*
     * ESC
     */

    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape' &&
                modal.classList.contains('is-open')
            ) {

                cerrarModal();

            }

        }
    );


    /*
     * ==========================================================
     * ENVIAR REPOSICIÓN
     * ==========================================================
     */

    form.addEventListener(
        'submit',
        async function (event) {

            event.preventDefault();


            const cantidad =
                Number(
                    quantityInput.value || 0
                );

            const productoId =
                productIdInput.value;


            /*
             * VALIDAR
             */

            if (
                !productoId ||
                cantidad < 1 ||
                !Number.isInteger(cantidad)
            ) {

                errorElement.textContent =
                    'Ingresa una cantidad válida.';

                errorElement.classList.add(
                    'visible'
                );

                return;

            }


            /*
             * BLOQUEAR BOTÓN
             */

            confirmButton.disabled =
                true;

            confirmText.hidden =
                true;

            loadingText.hidden =
                false;


            errorElement.textContent =
                '';

            errorElement.classList.remove(
                'visible'
            );


            try {

                /*
                 * Crear datos
                 */

                const formData =
                    new FormData(form);


                /*
                 * Token CSRF
                 */

                const csrfInput =
                    form.querySelector(
                        'input[name="_token"]'
                    );

                const csrfToken =
                    csrfInput
                        ? csrfInput.value
                        : '';


                /*
                 * Petición
                 */

                const response =
                    await fetch(
                        form.action,
                        {
                            method: 'POST',

                            headers: {

                                'Accept':
                                    'application/json',

                                'X-Requested-With':
                                    'XMLHttpRequest',

                                'X-CSRF-TOKEN':
                                    csrfToken

                            },

                            body: formData

                        }
                    );


                /*
                 * Leer respuesta
                 */

                let data;

                try {

                    data =
                        await response.json();

                } catch (jsonError) {

                    throw new Error(
                        'El servidor devolvió una respuesta no válida.'
                    );

                }


                /*
                 * Verificar resultado
                 */

                if (
                    !response.ok ||
                    !data.success
                ) {

                    throw new Error(
                        data.message ||
                        'No se pudo registrar la reposición.'
                    );

                }


                /*
                 * Cerrar modal
                 */

                cerrarModal();


                /*
                 * Mostrar éxito
                 */

                toastTitle.textContent =
                    'Reposición registrada';

                toastMessage.textContent =
                    `${data.cantidad} unidades agregadas. ` +
                    `Nuevo stock: ${data.stock_nuevo}.`;


                toast.classList.add(
                    'show'
                );

                toast.setAttribute(
                    'aria-hidden',
                    'false'
                );


                /*
                 * Recargar dashboard
                 */

                setTimeout(
                    function () {

                        window.location.reload();

                    },
                    1200
                );


            } catch (error) {

                console.error(
                    'Error al registrar reposición:',
                    error
                );


                errorElement.textContent =
                    error.message ||
                    'Ocurrió un error al registrar la reposición.';


                errorElement.classList.add(
                    'visible'
                );


            } finally {

                /*
                 * Reactivar botón
                 */

                confirmButton.disabled =
                    false;

                confirmText.hidden =
                    false;

                loadingText.hidden =
                    true;

            }

        }
    );

});

</script>

@endpush