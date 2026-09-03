<?php

namespace App\Http\Controllers;

use App\Services\DemandaService;

class DashboardController extends Controller
{
    public function __construct(
        private DemandaService $demandaService
    ) {
    }

    public function index()
    {
        $resumen =
            $this->demandaService->obtenerResumenReposicion();

        $productos = collect(
            $resumen['productos'] ?? []
        );

        /*
         * =========================================================
         * ESTADO ACTUAL
         * =========================================================
         */

        $totalProductos = $productos->count();

        $reposicionInmediata = $productos
            ->where('nivel', 'inmediata')
            ->values();

        $reposicionPronta = $productos
            ->where('nivel', 'pronto')
            ->values();

        $productosEstables = $productos
            ->where('nivel', 'suficiente')
            ->values();

        $totalAtencion =
            $reposicionInmediata->count() +
            $reposicionPronta->count();

        $stockTotal = (int) $productos->sum(
            fn ($producto) =>
                (int) ($producto['stock_actual'] ?? 0)
        );

        $demandaHoy = (int) $productos->sum(
            fn ($producto) =>
                (int) ($producto['demanda_predicha'] ?? 0)
        );

        $faltanteTotal = (int) $productos->sum(
            fn ($producto) =>
                (int) ($producto['faltante_estimado'] ?? 0)
        );

        /*
         * Porcentaje de productos actualmente bajo control.
         */
        $porcentajeEstable = $totalProductos > 0
            ? round(
                (
                    $productosEstables->count()
                    / $totalProductos
                ) * 100
            )
            : 0;

        /*
         * =========================================================
         * PRIORIDADES
         * =========================================================
         *
         * Primero faltante mayor.
         */

        $prioridades = $productos
            ->filter(
                fn ($producto) =>
                    ($producto['nivel'] ?? '') !== 'suficiente'
            )
            ->sortByDesc(
                fn ($producto) =>
                    (int) ($producto['faltante_estimado'] ?? 0)
            )
            ->values()
            ->take(5);

        /*
         * =========================================================
         * PRODUCTOS PARA GRÁFICO DE PRESIÓN
         * =========================================================
         *
         * Mostramos primero los productos con mayor riesgo
         * y luego completamos con productos de stock suficiente.
         */

        $productosRiesgo = $productos
            ->sortByDesc(
                fn ($producto) =>
                    (
                        (int) ($producto['faltante_estimado'] ?? 0)
                    )
            )
            ->values()
            ->take(6);

        $maxDemandaGrafico = max(
            1,
            (int) $productosRiesgo->max(
                fn ($producto) =>
                    (int) ($producto['demanda_predicha'] ?? 0)
            )
        );

        /*
         * =========================================================
         * RECOMENDACIÓN
         * =========================================================
         */

        if ($reposicionInmediata->count() > 0) {

            $primero = $reposicionInmediata
                ->sortByDesc(
                    fn ($producto) =>
                        (int) ($producto['faltante_estimado'] ?? 0)
                )
                ->first();

            $segundo = $reposicionInmediata
                ->sortByDesc(
                    fn ($producto) =>
                        (int) ($producto['faltante_estimado'] ?? 0)
                )
                ->skip(1)
                ->first();

            $nombrePrimero =
                $primero['producto'] ?? 'el producto prioritario';

            $faltantePrimero =
                (int) ($primero['faltante_estimado'] ?? 0);

            if ($segundo) {

                $nombreSegundo =
                    $segundo['producto'] ?? 'otro producto';

                $recomendacionTitulo =
                    'Prioriza la reposición de estos productos.';

                $recomendacionTexto =
                    "{$nombrePrimero} necesita aproximadamente "
                    . "{$faltantePrimero} unidades. "
                    . "También conviene atender {$nombreSegundo} "
                    . "antes de que aumente el riesgo de faltante.";

            } else {

                $recomendacionTitulo =
                    'Hay una prioridad clara para hoy.';

                $recomendacionTexto =
                    "{$nombrePrimero} presenta el mayor faltante, "
                    . "con aproximadamente {$faltantePrimero} unidades "
                    . "por cubrir.";
            }

            $recomendacionNivel = 'danger';

        } elseif ($reposicionPronta->count() > 0) {

            $primero = $reposicionPronta
                ->sortByDesc(
                    fn ($producto) =>
                        (int) ($producto['demanda_predicha'] ?? 0)
                )
                ->first();

            $nombrePrimero =
                $primero['producto'] ?? 'estos productos';

            $recomendacionTitulo =
                'El inventario está estable, pero conviene vigilar.';

            $recomendacionTexto =
                "{$nombrePrimero} está cerca de su nivel mínimo. "
                . "Revisa el inventario durante el día.";

            $recomendacionNivel = 'warning';

        } else {

            $recomendacionTitulo =
                'Todo está bajo control.';

            $recomendacionTexto =
                'El inventario actual cubre adecuadamente '
                . 'las necesidades estimadas para hoy.';

            $recomendacionNivel = 'success';
        }

        /*
         * =========================================================
         * MENSAJE DE ESTADO
         * =========================================================
         */

        if ($reposicionInmediata->count() >= 3) {

            $estadoTitulo =
                'Atención prioritaria';

            $estadoTexto =
                'Hay varios productos que necesitan intervención hoy.';

            $estadoClase = 'danger';

        } elseif ($totalAtencion > 0) {

            $estadoTitulo =
                'Requiere atención';

            $estadoTexto =
                'Hay algunos productos que conviene revisar hoy.';

            $estadoClase = 'warning';

        } else {

            $estadoTitulo =
                'Inventario estable';

            $estadoTexto =
                'No se detectan necesidades urgentes de reposición.';

            $estadoClase = 'success';
        }

        /*
         * =========================================================
         * FECHA Y SALUDO
         * =========================================================
         */

        $hora = now()->hour;

        if ($hora < 12) {
            $saludo = 'Buenos días';
        } elseif ($hora < 19) {
            $saludo = 'Buenas tardes';
        } else {
            $saludo = 'Buenas noches';
        }

        $fechaActual =
            now()
                ->locale('es')
                ->translatedFormat('l, d \d\e F \d\e Y');

        /*
         * =========================================================
         * DASHBOARD
         * =========================================================
         */

        $dashboard = [

            'productos' =>
                $productos,

            'total_productos' =>
                $totalProductos,

            'reposicion_inmediata' =>
                $reposicionInmediata->count(),

            'reposicion_pronta' =>
                $reposicionPronta->count(),

            'productos_estables' =>
                $productosEstables->count(),

            'total_atencion' =>
                $totalAtencion,

            'stock_total' =>
                $stockTotal,

            'demanda_hoy' =>
                $demandaHoy,

            'faltante_total' =>
                $faltanteTotal,

            'porcentaje_estable' =>
                min(100, max(0, $porcentajeEstable)),

            'prioridades' =>
                $prioridades,

            'productos_riesgo' =>
                $productosRiesgo,

            'max_demanda_grafico' =>
                $maxDemandaGrafico,

            'recomendacion' => [

                'titulo' =>
                    $recomendacionTitulo,

                'texto' =>
                    $recomendacionTexto,

                'nivel' =>
                    $recomendacionNivel,
            ],

            'estado' => [

                'titulo' =>
                    $estadoTitulo,

                'texto' =>
                    $estadoTexto,

                'clase' =>
                    $estadoClase,
            ],

            'saludo' =>
                $saludo,

            'fecha_actual' =>
                $fechaActual,
        ];

        return view(
            'dashboard.index',
            compact('dashboard')
        );
    }
}