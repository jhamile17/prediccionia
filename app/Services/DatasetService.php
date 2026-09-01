<?php

namespace App\Services;

use App\Models\DiaEspecial;
use App\Models\Producto;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DatasetService
{
    public function generar(): Collection
    {
        /*
         * 1. Obtener rango de fechas de las ventas completadas.
         */
        $rango = DB::table('ventas')
            ->where('estado', 'completada')
            ->selectRaw('MIN(DATE(fecha)) as inicio, MAX(DATE(fecha)) as fin')
            ->first();

        if (!$rango || !$rango->inicio || !$rango->fin) {
            return collect();
        }

        $fechaInicio = Carbon::parse($rango->inicio)->startOfDay();
        $fechaFin = Carbon::parse($rango->fin)->startOfDay();

        /*
         * 2. Obtener demanda diaria.
         *
         * Solo traemos los datos agrupados.
         * Esto evita cargar los 17,549 detalles completos.
         */
        $ventas = DB::table('detalle_ventas')
            ->join(
                'ventas',
                'ventas.id',
                '=',
                'detalle_ventas.venta_id'
            )
            ->where('ventas.estado', 'completada')
            ->selectRaw('
                DATE(ventas.fecha) as fecha,
                detalle_ventas.producto_id,
                SUM(detalle_ventas.cantidad) as demanda,
                SUM(detalle_ventas.subtotal) as ingresos
            ')
            ->groupBy(
                DB::raw('DATE(ventas.fecha)'),
                'detalle_ventas.producto_id'
            )
            ->orderBy('fecha')
            ->get();

        /*
         * 3. Convertir las ventas a una estructura rápida
         *    para consultas en memoria.
         */
        $demandaDiaria = [];

        foreach ($ventas as $venta) {
            $fecha = $venta->fecha;
            $productoId = (int) $venta->producto_id;

            $demandaDiaria[$productoId][$fecha] = [
                'demanda' => (int) $venta->demanda,
                'ingresos' => (float) $venta->ingresos,
            ];
        }

        /*
         * 4. Obtener los productos.
         */
        $productos = Producto::select(
            'id',
            'nombre',
            'categoria_id'
        )->get();

        /*
         * 5. Obtener días especiales.
         */
        $diasEspeciales = DiaEspecial::where('activo', true)
            ->get()
            ->keyBy(function ($dia) {
                return Carbon::parse($dia->fecha)->format('Y-m-d');
            });

        /*
         * 6. Crear dataset.
         */
        $dataset = collect();

        foreach ($productos as $producto) {

            $productoId = $producto->id;

            /*
             * Historial de demanda de este producto.
             */
            $historico = [];

            for (
                $fecha = $fechaInicio->copy();
                $fecha->lte($fechaFin);
                $fecha->addDay()
            ) {

                $fechaTexto = $fecha->format('Y-m-d');

                /*
                 * Demanda del día.
                 */
                $demanda =
                    $demandaDiaria[$productoId][$fechaTexto]['demanda']
                    ?? 0;

                /*
                 * Ingresos del día.
                 */
                $ingresos =
                    $demandaDiaria[$productoId][$fechaTexto]['ingresos']
                    ?? 0;

                /*
                 * Demanda anterior.
                 */
                $fechaAnterior = $fecha
                    ->copy()
                    ->subDay()
                    ->format('Y-m-d');

                $demandaAnterior =
                    $historico[$fechaAnterior] ?? 0;

                /*
                 * Demanda de hace 7 días.
                 */
                $fecha7 = $fecha
                    ->copy()
                    ->subDays(7)
                    ->format('Y-m-d');

                $demanda7 =
                    $historico[$fecha7] ?? 0;

                /*
                 * Demanda de hace 14 días.
                 */
                $fecha14 = $fecha
                    ->copy()
                    ->subDays(14)
                    ->format('Y-m-d');

                $demanda14 =
                    $historico[$fecha14] ?? 0;

                /*
                 * Promedio últimos 7 días.
                 */
                $suma7 = 0;
                $contador7 = 0;

                for ($i = 1; $i <= 7; $i++) {

                    $fechaHist = $fecha
                        ->copy()
                        ->subDays($i)
                        ->format('Y-m-d');

                    if (isset($historico[$fechaHist])) {
                        $suma7 += $historico[$fechaHist];
                        $contador7++;
                    }
                }

                $promedio7 = $contador7 > 0
                    ? round($suma7 / $contador7, 2)
                    : 0;

                /*
                 * Promedio últimos 30 días.
                 */
                $suma30 = 0;
                $contador30 = 0;

                for ($i = 1; $i <= 30; $i++) {

                    $fechaHist = $fecha
                        ->copy()
                        ->subDays($i)
                        ->format('Y-m-d');

                    if (isset($historico[$fechaHist])) {
                        $suma30 += $historico[$fechaHist];
                        $contador30++;
                    }
                }

                $promedio30 = $contador30 > 0
                    ? round($suma30 / $contador30, 2)
                    : 0;

                /*
                 * Día especial.
                 */
                $diaEspecial = $diasEspeciales->get($fechaTexto);

                $esDiaEspecial = $diaEspecial ? 1 : 0;

                $tipoDiaEspecial =
                    $diaEspecial?->tipo;

                $impactoDemanda =
                    $diaEspecial?->impacto_demanda;

                /*
                 * Guardamos únicamente la demanda.
                 * Esto permite calcular históricos posteriores.
                 */
                $historico[$fechaTexto] = $demanda;

                /*
                 * Agregar fila al dataset.
                 */
                $dataset->push([
                    'fecha' => $fechaTexto,

                    'producto_id' => $productoId,
                    'producto' => $producto->nombre,
                    'categoria_id' => $producto->categoria_id,

                    'demanda' => $demanda,
                    'demanda_anterior' => $demandaAnterior,
                    'demanda_7_dias' => $demanda7,
                    'demanda_14_dias' => $demanda14,

                    'promedio_7_dias' => $promedio7,
                    'promedio_30_dias' => $promedio30,

                    'dia_semana' => $fecha->dayOfWeekIso,
                    'mes' => $fecha->month,
                    'año' => $fecha->year,

                    'es_fin_de_semana' =>
                        $fecha->isWeekend() ? 1 : 0,

                    'es_dia_especial' => $esDiaEspecial,

                    'tipo_dia_especial' =>
                        $tipoDiaEspecial,

                    'impacto_demanda' =>
                        $impactoDemanda,
                ]);
            }
        }
    
        return $dataset;
    }
    public function exportarCsv(): string
{
    $dataset = $this->generar();

    $directorio = storage_path('app/datasets');

    if (!is_dir($directorio)) {
        mkdir($directorio, 0755, true);
    }

    $ruta = $directorio . DIRECTORY_SEPARATOR . 'dataset_demanda.csv';

    $archivo = fopen($ruta, 'w');

    if (!$archivo) {
        throw new \RuntimeException(
            'No se pudo crear el archivo CSV.'
        );
    }

    /*
     * Encabezados.
     */
    if ($dataset->isNotEmpty()) {
        fputcsv(
            $archivo,
            array_keys($dataset->first())
        );
    }

    /*
     * Registros.
     */
    foreach ($dataset as $fila) {
        fputcsv($archivo, $fila);
    }

    fclose($archivo);

    return $ruta;
}
}