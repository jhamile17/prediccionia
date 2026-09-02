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
     */
    public function obtenerResumenReposicion(): array
    {
        $predicciones =
            $this->obtenerPrediccionesReposicion();

        $inmediatos = array_values(
            array_filter(
                $predicciones,
                fn ($producto) =>
                    $producto['nivel'] === 'inmediata'
            )
        );

        $prontos = array_values(
            array_filter(
                $predicciones,
                fn ($producto) =>
                    $producto['nivel'] === 'pronto'
            )
        );

        $suficientes = array_values(
            array_filter(
                $predicciones,
                fn ($producto) =>
                    $producto['nivel'] === 'suficiente'
            )
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
        ];
    }


    /**
     * ============================================================
     * PREDICCIÓN MENSUAL OPTIMIZADA
     * ============================================================
     *
     * Laravel obtiene los datos históricos una sola vez.
     *
     * Python realiza la simulación recursiva de las fechas
     * futuras mediante una sola ejecución por producto.
     *
     * Para un mes futuro:
     *
     *       HOY
     *        ↓
     *    predicción
     *        ↓
     *    predicción
     *        ↓
     *       ...
     *        ↓
     *    MES OBJETIVO
     *
     * Las fechas anteriores al día actual utilizan demanda real.
     */
    public function obtenerPrediccionesMensuales(
        ?int $anio = null,
        ?int $mes = null
    ): array {

        $ahora = now()->startOfDay();

        $anio = $anio ?? $ahora->year;

        $mes = $mes ?? $ahora->month;

        /*
         * Mes solicitado.
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
         * Productos activos.
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
         * CASO 1:
         * MES COMPLETAMENTE PASADO
         * ========================================================
         *
         * No necesitamos Python.
         * Todo se obtiene de ventas reales.
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
         *
         * Python necesita información histórica desde,
         * como mínimo, 30 días antes del primer día que
         * vamos a predecir.
         *
         * Para mayor seguridad cargamos desde 30 días antes
         * de hoy hasta ayer.
         */
        $inicioHistorial =
            $ahora->copy()->subDays(30);

        $finHistorial =
            $ahora->copy()->subDay();

        /*
         * Obtener todas las ventas históricas en UNA consulta.
         */
        $ventasHistoricas =
            $this->obtenerVentasHistoricas(
                $inicioHistorial,
                $finHistorial
            );

        /*
         * Obtener días especiales en UNA consulta.
         *
         * Se cargan desde hoy hasta el final del mes solicitado.
         */
        $diasEspeciales =
            $this->obtenerDiasEspeciales(
                $ahora,
                $finMes
            );

        $resultado = [];

        foreach ($productos as $producto) {

            try {

                /*
                 * =================================================
                 * HISTORIAL DEL PRODUCTO
                 * =================================================
                 */
                $historial =
                    $ventasHistoricas[
                        (int) $producto->id
                    ] ?? [];

                /*
                 * =================================================
                 * FECHAS A SIMULAR
                 * =================================================
                 *
                 * Si hoy es 02/09 y se pide septiembre:
                 *
                 * 02/09 → 30/09
                 *
                 * Si se pide octubre:
                 *
                 * 02/09 → 31/10
                 *
                 * Python necesita simular el tramo intermedio
                 * para que las predicciones de octubre tengan
                 * datos previos.
                 */
                $fechaInicioPrediccion =
                    $ahora->copy();

                $fechas = [];

                $fecha =
                    $fechaInicioPrediccion->copy();

                while ($fecha->lte($finMes)) {

                    $fechaTexto =
                        $fecha->toDateString();

                    $fechas[] = [

                        'fecha' =>
                            $fechaTexto,

                        'dia_semana' =>
                            (int) $fecha->dayOfWeekIso,

                        'mes' =>
                            (int) $fecha->month,

                        /*
                         * Usamos "anio" en el JSON externo.
                         * Python lo normaliza internamente
                         * a "año".
                         */
                        'anio' =>
                            (int) $fecha->year,

                        'es_fin_de_semana' =>
                            $fecha->isWeekend()
                                ? 1
                                : 0,

                        'es_dia_especial' =>
                            isset(
                                $diasEspeciales[
                                    $fechaTexto
                                ]
                            )
                                ? 1
                                : 0,
                    ];

                    $fecha->addDay();
                }

                /*
                 * =================================================
                 * PAYLOAD PARA PYTHON
                 * =================================================
                 */
                $payload = [

                    'modo' =>
                        'mensual',

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

                /*
                 * =================================================
                 * UNA SOLA EJECUCIÓN DE PYTHON
                 * =================================================
                 */
                $respuesta =
                    $this->predictionService
                        ->predecir($payload);

                $prediccionesPython =
                    $respuesta['predicciones']
                    ?? [];

                /*
                 * =================================================
                 * CONSTRUIR DETALLE DEL MES
                 * =================================================
                 */
                $detalleDiario = [];

                $demandaMensual = 0;

                /*
                 * Primero agregamos las fechas reales
                 * anteriores a hoy que pertenezcan al mes.
                 */
                if (
                    $inicioMes->lt($ahora)
                ) {

                    $fechaReal =
                        $inicioMes->copy();

                    while (
                        $fechaReal->lt($ahora)
                    ) {

                        /*
                         * No debería superar fin de mes,
                         * pero lo protegemos.
                         */
                        if (
                            $fechaReal->gt($finMes)
                        ) {
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
                 * Agregar predicciones.
                 *
                 * Python puede devolver fechas desde hoy
                 * hasta el final del mes objetivo.
                 *
                 * Solo guardamos las fechas pertenecientes
                 * al mes solicitado.
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
                     * Filtrar únicamente el mes solicitado.
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
                 * Ordenar detalle por fecha.
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
                 * =================================================
                 * STOCK
                 * =================================================
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
                 * =================================================
                 * NIVEL DE REPOSICIÓN
                 * =================================================
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
                 * =================================================
                 * RESULTADO
                 * =================================================
                 */
                $resultado[] = [

                    'producto_id' =>
                        (int) $producto->id,

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
         * Ordenar por prioridad.
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
     *
     * Evita hacer una consulta por cada día y producto.
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
     *
     * Se utiliza cuando el usuario solicita un mes anterior
     * al mes actual.
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