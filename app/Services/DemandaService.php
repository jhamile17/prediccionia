<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DemandaService
{
    public function __construct(
        private PredictionService $predictionService
    ) {
    }

    /**
     * ============================================================
     * PREPARAR DATOS PARA PREDICCIÓN DIARIA
     * ============================================================
     */
    public function prepararDatos(
        int $productoId,
        string $fecha
    ): array {

        $fechaConsulta = Carbon::parse($fecha)->startOfDay();

        /*
         * Obtener producto.
         */
        $producto = DB::table('productos')
            ->where('id', $productoId)
            ->first();

        if (!$producto) {
            throw new \RuntimeException(
                "No existe el producto con ID {$productoId}."
            );
        }

        /*
         * Obtener demanda de una fecha.
         */
        $demandaPorFecha = function (Carbon $fecha) use ($productoId) {

            return (int) DB::table('detalle_ventas')
                ->join(
                    'ventas',
                    'ventas.id',
                    '=',
                    'detalle_ventas.venta_id'
                )
                ->where(
                    'detalle_ventas.producto_id',
                    $productoId
                )
                ->where(
                    'ventas.estado',
                    'completada'
                )
                ->whereDate(
                    'ventas.fecha',
                    $fecha->toDateString()
                )
                ->sum('detalle_ventas.cantidad');
        };

        /*
         * Demanda anterior.
         */
        $demandaAnterior = $demandaPorFecha(
            $fechaConsulta->copy()->subDay()
        );

        /*
         * Demanda de hace 7 días.
         */
        $demanda7Dias = $demandaPorFecha(
            $fechaConsulta->copy()->subDays(7)
        );

        /*
         * Demanda de hace 14 días.
         */
        $demanda14Dias = $demandaPorFecha(
            $fechaConsulta->copy()->subDays(14)
        );

        /*
         * Promedio últimos 7 días.
         */
        $promedio7Dias = $this->calcularPromedio(
            $productoId,
            $fechaConsulta->copy()->subDays(7),
            $fechaConsulta->copy()->subDay()
        );

        /*
         * Promedio últimos 30 días.
         */
        $promedio30Dias = $this->calcularPromedio(
            $productoId,
            $fechaConsulta->copy()->subDays(30),
            $fechaConsulta->copy()->subDay()
        );

        /*
         * Variables temporales.
         */
        $diaSemana = (int) $fechaConsulta->dayOfWeekIso;

        $mes = (int) $fechaConsulta->month;

        $anio = (int) $fechaConsulta->year;

        $esFinDeSemana =
            $fechaConsulta->isWeekend()
                ? 1
                : 0;

        /*
         * Día especial.
         */
        $diaEspecial = DB::table('dias_especiales')
            ->whereDate(
                'fecha',
                $fechaConsulta->toDateString()
            )
            ->where(
                'activo',
                1
            )
            ->first();

        $esDiaEspecial = $diaEspecial
            ? 1
            : 0;

        /*
         * Las 12 variables exactas del modelo.
         */
        return [

            'producto_id' =>
                (int) $producto->id,

            'categoria_id' =>
                (int) $producto->categoria_id,

            'demanda_anterior' =>
                $demandaAnterior,

            'demanda_7_dias' =>
                $demanda7Dias,

            'demanda_14_dias' =>
                $demanda14Dias,

            'promedio_7_dias' =>
                round($promedio7Dias, 2),

            'promedio_30_dias' =>
                round($promedio30Dias, 2),

            'dia_semana' =>
                $diaSemana,

            'mes' =>
                $mes,

            'año' =>
                $anio,

            'es_fin_de_semana' =>
                $esFinDeSemana,

            'es_dia_especial' =>
                $esDiaEspecial,
        ];
    }


    /**
     * ============================================================
     * CALCULAR PROMEDIO
     * ============================================================
     */
    private function calcularPromedio(
        int $productoId,
        Carbon $inicio,
        Carbon $fin
    ): float {

        $dias =
            $inicio->diffInDays($fin) + 1;

        if ($dias <= 0) {
            return 0;
        }

        $cantidad = DB::table('detalle_ventas')
            ->join(
                'ventas',
                'ventas.id',
                '=',
                'detalle_ventas.venta_id'
            )
            ->where(
                'detalle_ventas.producto_id',
                $productoId
            )
            ->where(
                'ventas.estado',
                'completada'
            )
            ->whereBetween(
                DB::raw('DATE(ventas.fecha)'),
                [
                    $inicio->toDateString(),
                    $fin->toDateString()
                ]
            )
            ->sum(
                'detalle_ventas.cantidad'
            );

        return (float) $cantidad / $dias;
    }


    /**
     * ============================================================
     * PREDICCIONES DIARIAS + REPOSICIÓN
     * ============================================================
     */
    public function obtenerPrediccionesReposicion(): array
    {
        $productos = DB::table('productos')
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get();

        $predicciones = [];

        foreach ($productos as $producto) {

            try {

                $datos = $this->prepararDatos(
                    (int) $producto->id,
                    now()->toDateString()
                );

                $resultado =
                    $this->predictionService
                        ->predecir($datos);

                $demandaPredicha =
                    (int) (
                        $resultado['prediccion'] ?? 0
                    );

                $demandaPredicha =
                    max(0, $demandaPredicha);

                $stockActual =
                    (int) $producto->stock;

                $stockMinimo =
                    (int) $producto->stock_minimo;

                $faltanteEstimado =
                    max(
                        0,
                        $demandaPredicha -
                        $stockActual
                    );

                if (
                    $stockActual <
                    $demandaPredicha
                ) {

                    $nivel = 'inmediata';

                    $mensaje =
                        'El stock actual no cubre la demanda estimada.';

                } elseif (
                    $stockActual <=
                    $stockMinimo
                ) {

                    $nivel = 'pronto';

                    $mensaje =
                        'El stock está cerca del nivel mínimo.';

                } else {

                    $nivel = 'suficiente';

                    $mensaje =
                        'El stock disponible es suficiente.';
                }

                $predicciones[] = [

                    'producto_id' =>
                        (int) $producto->id,

                    'producto' =>
                        $producto->nombre,

                    'stock_actual' =>
                        $stockActual,

                    'stock_minimo' =>
                        $stockMinimo,

                    'demanda_predicha' =>
                        $demandaPredicha,

                    'faltante_estimado' =>
                        $faltanteEstimado,

                    'nivel' =>
                        $nivel,

                    'mensaje' =>
                        $mensaje,
                ];

            } catch (\Throwable $e) {

                Log::error(
                    'Error en predicción de producto',
                    [
                        'producto_id' =>
                            $producto->id,

                        'producto' =>
                            $producto->nombre,

                        'error' =>
                            $e->getMessage(),
                    ]
                );

                continue;
            }
        }

        $this->ordenarPorPrioridad(
            $predicciones
        );

        return $predicciones;
    }


    /**
 * ============================================================
 * RESUMEN PARA DASHBOARD
 * ============================================================
 *
 * Este método concentra la información que necesita
 * el Dashboard para ayudar al usuario a tomar decisiones.
 */
public function obtenerResumenReposicion(): array
{
    $predicciones = $this->obtenerPrediccionesReposicion();

    /*
     * Productos que necesitan reposición inmediata.
     */
    $inmediatos = array_values(
        array_filter(
            $predicciones,
            fn ($producto) =>
                ($producto['nivel'] ?? '') === 'inmediata'
        )
    );

    /*
     * Productos que deben revisarse pronto.
     */
    $prontos = array_values(
        array_filter(
            $predicciones,
            fn ($producto) =>
                ($producto['nivel'] ?? '') === 'pronto'
        )
    );

    /*
     * Productos con stock suficiente.
     */
    $suficientes = array_values(
        array_filter(
            $predicciones,
            fn ($producto) =>
                ($producto['nivel'] ?? '') === 'suficiente'
        )
    );

    /*
     * Ordenar productos por demanda esperada.
     */
    $productosMayorDemanda = $predicciones;

    usort(
        $productosMayorDemanda,
        fn ($a, $b) =>
            ((int) ($b['demanda_predicha'] ?? 0))
            <=>
            ((int) ($a['demanda_predicha'] ?? 0))
    );

    /*
     * Mostrar solamente los 5 productos principales.
     */
    $productosMayorDemanda = array_slice(
        $productosMayorDemanda,
        0,
        5
    );

    return [

        'total_productos' =>
            count($predicciones),

        'reposicion_inmediata' =>
            count($inmediatos),

        'reposicion_pronta' =>
            count($prontos),

        'stock_suficiente' =>
            count($suficientes),

        'productos_criticos' =>
            $inmediatos,

        'productos_por_revisar' =>
            $prontos,

        'productos' =>
            $predicciones,

        'productos_mayor_demanda' =>
            $productosMayorDemanda,
    ];
}


    /**
     * ============================================================
     * DATOS COMPLETOS DEL DASHBOARD
     * ============================================================
     */
    public function obtenerDatosDashboard(): array
    {
        $hoy = now()->startOfDay();

        /*
         * ========================================================
         * MES ANTERIOR
         * ========================================================
         */

        $inicioMesAnterior = $hoy->copy()
            ->subMonthNoOverflow()
            ->startOfMonth();

        $finMesAnterior = $inicioMesAnterior->copy()
            ->endOfMonth();

        $ventasMesAnterior = $this->obtenerVentasHistoricas(
            $inicioMesAnterior,
            $finMesAnterior
        );

        $ventasTotales = 0;

        foreach ($ventasMesAnterior as $ventasProducto) {
            $ventasTotales += array_sum($ventasProducto);
        }


        /*
         * ========================================================
         * PRÓXIMO MES
         * ========================================================
         */

        $inicioProximoMes = $hoy->copy()
            ->addMonthNoOverflow()
            ->startOfMonth();

        $prediccionesMensuales =
            $this->obtenerPrediccionesMensuales(
                $inicioProximoMes->year,
                $inicioProximoMes->month
            );

        $demandaEstimada = 0;

        foreach ($prediccionesMensuales as $producto) {

            $demandaEstimada += (int) (
                $producto['demanda_mensual'] ?? 0
            );
        }


        /*
         * ========================================================
         * STOCK ACTUAL
         * ========================================================
         */

        $stockDisponible = (int) DB::table('productos')
            ->where('activo', 1)
            ->sum('stock');


        /*
         * ========================================================
         * PRODUCTOS A REVISAR
         * ========================================================
         */

        $productosRevision = [];

        foreach ($prediccionesMensuales as $producto) {

            $stock = (int) (
                $producto['stock_actual'] ?? 0
            );

            $stockMinimo = (int) (
                $producto['stock_minimo'] ?? 0
            );

            if (
                ($producto['nivel'] ?? '') === 'inmediata'
                ||
                ($producto['nivel'] ?? '') === 'pronto'
                ||
                $stock <= $stockMinimo
            ) {
                $productosRevision[] = $producto;
            }
        }


        /*
         * ========================================================
         * TOP 5 PRODUCTOS
         * ========================================================
         */

        $topProductos = $prediccionesMensuales;

        usort(
            $topProductos,
            function ($a, $b) {

                return (
                    ($b['demanda_mensual'] ?? 0)
                    <=>
                    ($a['demanda_mensual'] ?? 0)
                );
            }
        );

        $topProductos = array_slice(
            $topProductos,
            0,
            5
        );


        /*
         * ========================================================
         * SERIE PARA GRÁFICO
         * ========================================================
         */

        $serieDashboard =
            $this->obtenerSerieDashboard(
                $ventasMesAnterior,
                $prediccionesMensuales
            );


        /*
         * ========================================================
         * RESULTADO FINAL
         * ========================================================
         */

        return [

            'mes_anterior' => [

                'nombre' =>
                    $inicioMesAnterior
                        ->locale('es')
                        ->translatedFormat('F Y'),

                'ventas' =>
                    $ventasTotales,
            ],

            'proximo_mes' => [

                'nombre' =>
                    $inicioProximoMes
                        ->locale('es')
                        ->translatedFormat('F Y'),

                'demanda_estimada' =>
                    $demandaEstimada,
            ],

            'stock_disponible' =>
                $stockDisponible,

            'productos_a_revisar' =>
                count($productosRevision),

            'productos_revision' =>
                $productosRevision,

            'top_productos' =>
                $topProductos,

            'serie_demanda' =>
                $serieDashboard,

            'predicciones_mensuales' =>
                $prediccionesMensuales,
        ];
    }
/**
 * ============================================================
 * PREDICCIONES MENSUALES OPTIMIZADAS
 * ============================================================
 *
 * Todos los productos se envían a Python en una sola ejecución.
 */
public function obtenerPrediccionesMensuales(
    ?int $anio = null,
    ?int $mes = null
): array {

    $ahora = now()->startOfDay();

    $anio = $anio ?? $ahora->year;
    $mes = $mes ?? $ahora->month;

    /*
     * ========================================================
     * MES SOLICITADO
     * ========================================================
     */

    $inicioMes = Carbon::create(
        $anio,
        $mes,
        1
    )->startOfDay();

    $finMes = $inicioMes
        ->copy()
        ->endOfMonth();

    /*
     * ========================================================
     * PRODUCTOS ACTIVOS
     * ========================================================
     */

    $productos = DB::table('productos')
        ->where('activo', 1)
        ->orderBy('nombre')
        ->get();

    if ($productos->isEmpty()) {
        return [];
    }

    /*
     * ========================================================
     * MES COMPLETAMENTE PASADO
     * ========================================================
     *
     * No necesitamos Python.
     */

    if ($finMes->lt($ahora)) {

        return $this->obtenerMesCompletamenteReal(
            $productos,
            $inicioMes,
            $finMes
        );
    }

    /*
     * ========================================================
     * HISTORIAL COMÚN
     * ========================================================
     */

    $inicioHistorial = $ahora
        ->copy()
        ->subDays(30);

    $finHistorial = $ahora
        ->copy()
        ->subDay();

    $ventasHistoricas = $this->obtenerVentasHistoricas(
        $inicioHistorial,
        $finHistorial
    );

    /*
     * ========================================================
     * DÍAS ESPECIALES
     * ========================================================
     */

    $diasEspeciales = $this->obtenerDiasEspeciales(
        $ahora,
        $finMes
    );

    /*
     * ========================================================
     * PREPARAR TODAS LAS SOLICITUDES
     * ========================================================
     */

    $solicitudes = [];

    foreach ($productos as $producto) {

        $historial = $ventasHistoricas[
            (int) $producto->id
        ] ?? [];

        /*
         * Fechas a predecir.
         *
         * Desde hoy hasta el final del mes solicitado.
         */
        $fechas = [];

        $fecha = $ahora->copy();

        while ($fecha->lte($finMes)) {

            $fechaTexto = $fecha->toDateString();

            $fechas[] = [

                'fecha' =>
                    $fechaTexto,

                'dia_semana' =>
                    (int) $fecha->dayOfWeekIso,

                'mes' =>
                    (int) $fecha->month,

                'anio' =>
                    (int) $fecha->year,

                'es_fin_de_semana' =>
                    $fecha->isWeekend()
                        ? 1
                        : 0,

                'es_dia_especial' =>
                    isset(
                        $diasEspeciales[$fechaTexto]
                    )
                        ? 1
                        : 0,
            ];

            $fecha->addDay();
        }

        /*
         * ====================================================
         * SOLICITUD DEL PRODUCTO
         * ====================================================
         */

        $solicitudes[] = [

            'producto' => [

                'producto_id' =>
                    (int) $producto->id,

                'categoria_id' =>
                    (int) $producto->categoria_id,
            ],

            'historial' =>
                $historial,

            'fechas' =>
                $fechas,
        ];
    }

    /*
     * ========================================================
     * UNA SOLA EJECUCIÓN DE PYTHON
     * ========================================================
     */

    $payload = [

        'modo' =>
            'mensual_multiple',

        'solicitudes' =>
            $solicitudes,
    ];

    $respuesta =
        $this->predictionService
            ->predecirMensualMultiple(
                $payload
            );

    /*
     * ========================================================
     * INDEXAR RESPUESTAS POR PRODUCTO
     * ========================================================
     */

    $prediccionesPorProducto = [];

    foreach (
        $respuesta['predicciones'] ?? []
        as $grupo
    ) {

        $productoId =
            (int) (
                $grupo['producto_id'] ?? 0
            );

        $prediccionesPorProducto[$productoId] =
            $grupo['predicciones'] ?? [];
    }

    /*
     * ========================================================
     * CONSTRUIR RESULTADO FINAL
     * ========================================================
     */

    $resultado = [];

    foreach ($productos as $producto) {

        try {

            $productoId =
                (int) $producto->id;

            $historial =
                $ventasHistoricas[
                    $productoId
                ] ?? [];

            $prediccionesPython =
                $prediccionesPorProducto[
                    $productoId
                ] ?? [];

            /*
             * ================================================
             * DETALLE DIARIO
             * ================================================
             */

            $detalleDiario = [];

            $demandaMensual = 0;

            /*
             * Primero agregamos las ventas reales
             * del mes actual anteriores a hoy.
             */

            if ($inicioMes->lt($ahora)) {

                $fechaReal =
                    $inicioMes->copy();

                while ($fechaReal->lt($ahora)) {

                    if ($fechaReal->gt($finMes)) {
                        break;
                    }

                    $fechaTexto =
                        $fechaReal->toDateString();

                    $demanda =
                        (int) (
                            $historial[
                                $fechaTexto
                            ] ?? 0
                        );

                    $demandaMensual +=
                        $demanda;

                    $detalleDiario[] = [

                        'fecha' =>
                            $fechaTexto,

                        'demanda' =>
                            $demanda,

                        'tipo' =>
                            'real',
                    ];

                    $fechaReal->addDay();
                }
            }

            /*
             * ================================================
             * PREDICCIONES
             * ================================================
             */

            foreach (
                $prediccionesPython
                as $prediccion
            ) {

                $fechaPrediccion =
                    Carbon::parse(
                        $prediccion['fecha']
                    );

                /*
                 * Solo el mes solicitado.
                 */

                if (
                    $fechaPrediccion->lt(
                        $inicioMes
                    ) ||
                    $fechaPrediccion->gt(
                        $finMes
                    )
                ) {
                    continue;
                }

                $demanda =
                    max(
                        0,
                        (int) (
                            $prediccion[
                                'prediccion'
                            ] ?? 0
                        )
                    );

                $demandaMensual +=
                    $demanda;

                $detalleDiario[] = [

                    'fecha' =>
                        $fechaPrediccion
                            ->toDateString(),

                    'demanda' =>
                        $demanda,

                    'tipo' =>
                        'prediccion',
                ];
            }

            /*
             * ================================================
             * ORDENAR DETALLE
             * ================================================
             */

            usort(
                $detalleDiario,
                fn ($a, $b) =>
                    strcmp(
                        $a['fecha'],
                        $b['fecha']
                    )
            );

            /*
             * ================================================
             * STOCK
             * ================================================
             */

            $stockActual =
                (int) $producto->stock;

            $stockMinimo =
                (int) $producto->stock_minimo;

            $faltante =
                max(
                    0,
                    $demandaMensual -
                    $stockActual
                );

            /*
             * ================================================
             * NIVEL
             * ================================================
             */

            if (
                $stockActual <
                $demandaMensual
            ) {

                $nivel =
                    'inmediata';

                $mensaje =
                    'El stock actual no cubre la demanda mensual estimada.';

            } elseif (
                $stockActual <=
                $stockMinimo
            ) {

                $nivel =
                    'pronto';

                $mensaje =
                    'El stock está cerca del nivel mínimo.';

            } else {

                $nivel =
                    'suficiente';

                $mensaje =
                    'El stock disponible es suficiente.';
            }

            /*
             * ================================================
             * RESULTADO
             * ================================================
             */

            $resultado[] = [

                'producto_id' =>
                    $productoId,

                'producto' =>
                    $producto->nombre,

                'stock_actual' =>
                    $stockActual,

                'stock_minimo' =>
                    $stockMinimo,

                'demanda_mensual' =>
                    $demandaMensual,

                'faltante_estimado' =>
                    $faltante,

                'nivel' =>
                    $nivel,

                'mensaje' =>
                    $mensaje,

                'detalle_diario' =>
                    $detalleDiario,
            ];

        } catch (\Throwable $e) {

            Log::error(
                'Error en predicción mensual',
                [
                    'producto_id' =>
                        $producto->id,

                    'producto' =>
                        $producto->nombre,

                    'anio' =>
                        $anio,

                    'mes' =>
                        $mes,

                    'error' =>
                        $e->getMessage(),
                ]
            );

            continue;
        }
    }

    /*
     * ========================================================
     * ORDENAR POR PRIORIDAD
     * ========================================================
     */

    $this->ordenarPorPrioridad(
        $resultado
    );

    return $resultado;
}


    /**
     * ============================================================
     * OBTENER VENTAS HISTÓRICAS EN BLOQUE
     * ============================================================
     */
    private function obtenerVentasHistoricas(
        Carbon $inicio,
        Carbon $fin
    ): array {

        $ventas = DB::table('detalle_ventas')
            ->join(
                'ventas',
                'ventas.id',
                '=',
                'detalle_ventas.venta_id'
            )
            ->where(
                'ventas.estado',
                'completada'
            )
            ->whereBetween(
                DB::raw('DATE(ventas.fecha)'),
                [
                    $inicio->toDateString(),
                    $fin->toDateString()
                ]
            )
            ->select(
                'detalle_ventas.producto_id',

                DB::raw(
                    'DATE(ventas.fecha) as fecha'
                ),

                DB::raw(
                    'SUM(detalle_ventas.cantidad) as cantidad'
                )
            )
            ->groupBy(
                'detalle_ventas.producto_id',
                DB::raw(
                    'DATE(ventas.fecha)'
                )
            )
            ->orderBy(
                'detalle_ventas.producto_id'
            )
            ->orderBy(
                'fecha'
            )
            ->get();

        $resultado = [];

        foreach ($ventas as $venta) {

            $productoId =
                (int) $venta->producto_id;

            $fecha =
                Carbon::parse(
                    $venta->fecha
                )->toDateString();

            if (
                !isset(
                    $resultado[$productoId]
                )
            ) {

                $resultado[$productoId] = [];
            }

            $resultado[$productoId][$fecha] =
                (int) $venta->cantidad;
        }

        return $resultado;
    }


    /**
     * ============================================================
     * OBTENER DÍAS ESPECIALES EN BLOQUE
     * ============================================================
     */
    private function obtenerDiasEspeciales(
        Carbon $inicio,
        Carbon $fin
    ): array {

        $dias = DB::table('dias_especiales')
            ->whereBetween(
                DB::raw('DATE(fecha)'),
                [
                    $inicio->toDateString(),
                    $fin->toDateString()
                ]
            )
            ->where(
                'activo',
                1
            )
            ->pluck(
                'fecha'
            );

        $resultado = [];

        foreach ($dias as $fecha) {

            $resultado[
                Carbon::parse($fecha)
                    ->toDateString()
            ] = true;
        }

        return $resultado;
    }


    /**
     * ============================================================
     * MES COMPLETAMENTE REAL
     * ============================================================
     */
    private function obtenerMesCompletamenteReal(
        $productos,
        Carbon $inicioMes,
        Carbon $finMes
    ): array {

        $ventas =
            $this->obtenerVentasHistoricas(
                $inicioMes,
                $finMes
            );

        $resultado = [];

        foreach ($productos as $producto) {

            $productoId =
                (int) $producto->id;

            $historial =
                $ventas[$productoId] ?? [];

            $detalleDiario = [];

            $demandaMensual = 0;

            $fecha =
                $inicioMes->copy();

            while (
                $fecha->lte($finMes)
            ) {

                $fechaTexto =
                    $fecha->toDateString();

                $demanda =
                    (int) (
                        $historial[
                            $fechaTexto
                        ] ?? 0
                    );

                $demandaMensual +=
                    $demanda;

                $detalleDiario[] = [

                    'fecha' =>
                        $fechaTexto,

                    'demanda' =>
                        $demanda,

                    'tipo' =>
                        'real',
                ];

                $fecha->addDay();
            }

            $stockActual =
                (int) $producto->stock;

            $stockMinimo =
                (int) $producto->stock_minimo;

            $faltante =
                max(
                    0,
                    $demandaMensual -
                    $stockActual
                );

            if (
                $stockActual <
                $demandaMensual
            ) {

                $nivel =
                    'inmediata';

                $mensaje =
                    'El stock actual no cubre la demanda mensual estimada.';

            } elseif (
                $stockActual <=
                $stockMinimo
            ) {

                $nivel =
                    'pronto';

                $mensaje =
                    'El stock está cerca del nivel mínimo.';

            } else {

                $nivel =
                    'suficiente';

                $mensaje =
                    'El stock disponible es suficiente.';
            }

            $resultado[] = [

                'producto_id' =>
                    $productoId,

                'producto' =>
                    $producto->nombre,

                'stock_actual' =>
                    $stockActual,

                'stock_minimo' =>
                    $stockMinimo,

                'demanda_mensual' =>
                    $demandaMensual,

                'faltante_estimado' =>
                    $faltante,

                'nivel' =>
                    $nivel,

                'mensaje' =>
                    $mensaje,

                'detalle_diario' =>
                    $detalleDiario,
            ];
        }

        $this->ordenarPorPrioridad(
            $resultado
        );

        return $resultado;
    }


    /**
     * ============================================================
     * SERIE PARA GRÁFICO DEL DASHBOARD
     * ============================================================
     */
    private function obtenerSerieDashboard(
        array $ventasMesAnterior,
        array $prediccionesMensuales
    ): array {

        /*
         * Ventas reales del mes anterior.
         */
        $ventasPorFecha = [];

        foreach ($ventasMesAnterior as $ventasProducto) {

            foreach ($ventasProducto as $fecha => $cantidad) {

                if (!isset($ventasPorFecha[$fecha])) {
                    $ventasPorFecha[$fecha] = 0;
                }

                $ventasPorFecha[$fecha] +=
                    (int) $cantidad;
            }
        }


        /*
         * Demanda estimada del próximo mes.
         */
        $prediccionPorFecha = [];

        foreach ($prediccionesMensuales as $producto) {

            foreach (
                ($producto['detalle_diario'] ?? [])
                as $dia
            ) {

                if (
                    ($dia['tipo'] ?? '') !==
                    'prediccion'
                ) {
                    continue;
                }

                $fecha =
                    $dia['fecha'] ?? null;

                if (!$fecha) {
                    continue;
                }

                if (
                    !isset(
                        $prediccionPorFecha[$fecha]
                    )
                ) {
                    $prediccionPorFecha[$fecha] = 0;
                }

                $prediccionPorFecha[$fecha] +=
                    max(
                        0,
                        (int) (
                            $dia['demanda'] ?? 0
                        )
                    );
            }
        }


        ksort($ventasPorFecha);
        ksort($prediccionPorFecha);


        return [

            'fechas_reales' =>
                array_keys(
                    $ventasPorFecha
                ),

            'ventas_reales' =>
                array_values(
                    $ventasPorFecha
                ),

            'fechas_prediccion' =>
                array_keys(
                    $prediccionPorFecha
                ),

            'demanda_predicha' =>
                array_values(
                    $prediccionPorFecha
                ),
        ];
    }


    /**
     * ============================================================
     * OBTENER DEMANDA REAL DE UN DÍA
     * ============================================================
     */
    private function obtenerDemandaReal(
        int $productoId,
        Carbon $fecha
    ): int {

        return (int) DB::table('detalle_ventas')
            ->join(
                'ventas',
                'ventas.id',
                '=',
                'detalle_ventas.venta_id'
            )
            ->where(
                'detalle_ventas.producto_id',
                $productoId
            )
            ->where(
                'ventas.estado',
                'completada'
            )
            ->whereDate(
                'ventas.fecha',
                $fecha->toDateString()
            )
            ->sum(
                'detalle_ventas.cantidad'
            );
    }


    /**
     * ============================================================
     * ORDENAR POR PRIORIDAD
     * ============================================================
     */
    private function ordenarPorPrioridad(
        array &$resultados
    ): void {

        usort(
            $resultados,
            function ($a, $b) {

                $prioridad = [

                    'inmediata' => 1,

                    'pronto' => 2,

                    'suficiente' => 3,
                ];

                return
                    ($prioridad[$a['nivel']] ?? 99)
                    <=>
                    ($prioridad[$b['nivel']] ?? 99);
            }
        );
    }
}