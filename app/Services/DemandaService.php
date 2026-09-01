<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\PredictionService;

class DemandaService
{
    public function __construct(
        private PredictionService $predictionService
    ){
    }
    /**
     * Prepara las 12 variables que necesita el modelo de Python.
     */
    public function prepararDatos(int $productoId, string $fecha): array
    {
        $fechaConsulta = Carbon::parse($fecha)->startOfDay();

        /*
         * Obtener información del producto.
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
         * Función para obtener la demanda de un producto
         * en una fecha determinada.
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
         * Demanda del día anterior.
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
         * Promedio de demanda de los últimos 7 días.
         */
        $promedio7Dias = $this->calcularPromedio(
            $productoId,
            $fechaConsulta->copy()->subDays(7),
            $fechaConsulta->copy()->subDay()
        );

        /*
         * Promedio de demanda de los últimos 30 días.
         */
        $promedio30Dias = $this->calcularPromedio(
            $productoId,
            $fechaConsulta->copy()->subDays(30),
            $fechaConsulta->copy()->subDay()
        );

        /*
         * Información temporal.
         */
        $diaSemana = (int) $fechaConsulta->dayOfWeekIso;
        $mes = (int) $fechaConsulta->month;
        $anio = (int) $fechaConsulta->year;

        /*
         * 0 = lunes ... 6 = domingo
         */
        $esFinDeSemana = $fechaConsulta->isWeekend() ? 1 : 0;

        /*
         * Buscar si existe un día especial.
         */
        $diaEspecial = DB::table('dias_especiales')
            ->whereDate(
                'fecha',
                $fechaConsulta->toDateString()
            )
            ->where('activo', 1)
            ->first();

        $esDiaEspecial = $diaEspecial ? 1 : 0;

        /*
         * El modelo solamente utiliza es_dia_especial.
         * tipo e impacto quedan disponibles para futuras mejoras.
         */
        return [
            'producto_id' => (int) $producto->id,
            'categoria_id' => (int) $producto->categoria_id,

            'demanda_anterior' => $demandaAnterior,
            'demanda_7_dias' => $demanda7Dias,
            'demanda_14_dias' => $demanda14Dias,

            'promedio_7_dias' => round($promedio7Dias, 2),
            'promedio_30_dias' => round($promedio30Dias, 2),

            'dia_semana' => $diaSemana,
            'mes' => $mes,
            'año' => $anio,

            'es_fin_de_semana' => $esFinDeSemana,
            'es_dia_especial' => $esDiaEspecial,
        ];
    }

        /**
         * Calcula el promedio diario de demanda
         * durante un rango de fechas.
         */
        private function calcularPromedio(
            int $productoId,
            Carbon $inicio,
            Carbon $fin
        ): float {

            $dias = $inicio->diffInDays($fin) + 1;

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
                ->sum('detalle_ventas.cantidad');

            return (float) $cantidad / $dias;
        }
        /**
         * Obtiene los productos y determina cuáles requieren
         * reposición según la demanda predicha.
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

            /*
             * Generar las variables requeridas
             * por el modelo de IA.
             */
            $datos = $this->prepararDatos(
                (int) $producto->id,
                now()->toDateString()
            );

            /*
             * Obtener predicción desde Python.
             */
            $resultado = $this->predictionService
                ->predecir($datos);

            $demandaPredicha = (int) (
                $resultado['prediccion'] ?? 0
            );

            $stockActual = (int) $producto->stock;

            $stockMinimo = (int) $producto->stock_minimo;

            /*
             * Cantidad que debería reponerse
             * para cubrir la demanda estimada.
             */
            $faltanteEstimado = max(
                0,
                $demandaPredicha - $stockActual
            );

            /*
             * Determinar nivel de atención.
             */
            if ($stockActual < $demandaPredicha) {

                $nivel = 'inmediata';

                $mensaje = 'El stock actual no cubre la demanda estimada.';

            } elseif ($stockActual <= $stockMinimo) {

                $nivel = 'pronto';

                $mensaje = 'El stock está cerca del nivel mínimo.';

            } else {

                $nivel = 'suficiente';

                $mensaje = 'El stock disponible es suficiente.';
            }

            /*
             * Guardar la predicción del producto.
             */
            $predicciones[] = [

                'producto_id' => (int) $producto->id,

                'producto' => $producto->nombre,

                'stock_actual' => $stockActual,

                'stock_minimo' => $stockMinimo,

                'demanda_predicha' => $demandaPredicha,

                'faltante_estimado' => $faltanteEstimado,

                'nivel' => $nivel,

                'mensaje' => $mensaje,
            ];

        } catch (\Throwable $e) {
        }
    }

    /*
     * Ordenar por prioridad:
     *
     * 1. Reponer ahora
     * 2. Revisar pronto
     * 3. Stock suficiente
     */
    usort($predicciones, function ($a, $b) {

        $prioridad = [
            'inmediata' => 1,
            'pronto' => 2,
            'suficiente' => 3,
        ];

        return $prioridad[$a['nivel']]
            <=> $prioridad[$b['nivel']];
    });

    /*
     * DEBUG TEMPORAL
     *
     * Aquí sí veremos los productos
     * después de haberlos agregado.
     */
    return $predicciones;
}
    /**
     * Genera un resumen de reposición para el dashboard.
     *
     * El dashboard no necesita conocer todos los detalles
     * del modelo de IA. Solo necesita saber qué productos
     * requieren atención.
     */
    public function obtenerResumenReposicion(): array
    {
        $predicciones = $this->obtenerPrediccionesReposicion();

        $inmediatos = array_values(
            array_filter(
                $predicciones,
                fn ($producto) => $producto['nivel'] === 'inmediata'
            )
        );

        $prontos = array_values(
            array_filter(
                $predicciones,
                fn ($producto) => $producto['nivel'] === 'pronto'
            )
        );

        $suficientes = array_values(
            array_filter(
                $predicciones,
                fn ($producto) => $producto['nivel'] === 'suficiente'
            )
        );

        return [
            'total_productos' => count($predicciones),

            'reposicion_inmediata' => count($inmediatos),

            'reposicion_pronta' => count($prontos),

            'stock_suficiente' => count($suficientes),

            /*
            * Solo enviamos al dashboard los productos
            * que necesitan atención.
            */
            'productos_criticos' => $inmediatos,

            'productos_por_revisar' => $prontos,
        ];
    }
}
