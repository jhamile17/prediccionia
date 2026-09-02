<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalisisController extends Controller
{
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | FILTROS
        |--------------------------------------------------------------------------
        */

        $productoId = $request->input('producto_id');

        $periodo = $request->input('periodo', '30');


        /*
        |--------------------------------------------------------------------------
        | VALIDAR FILTROS
        |--------------------------------------------------------------------------
        */

        if ($productoId !== null && $productoId !== '') {
            $productoId = (int) $productoId;

            $existeProducto = DB::table('productos')
                ->where('id', $productoId)
                ->where('activo', 1)
                ->exists();

            if (!$existeProducto) {
                $productoId = null;
            }
        }

        $periodosPermitidos = [
            '7',
            '30',
            '90',
            '365',
        ];

        if (!in_array($periodo, $periodosPermitidos, true)) {
            $periodo = '30';
        }


        /*
        |--------------------------------------------------------------------------
        | FECHAS
        |--------------------------------------------------------------------------
        */

        $dias = (int) $periodo;

        $fechaFin = now()->endOfDay();

        $fechaInicio = now()
            ->subDays($dias - 1)
            ->startOfDay();


        /*
        |--------------------------------------------------------------------------
        | PRODUCTOS
        |--------------------------------------------------------------------------
        */

        $productos = DB::table('productos')
            ->where('activo', 1)
            ->orderBy('nombre')
            ->get([
                'id',
                'nombre',
            ]);


        /*
        |--------------------------------------------------------------------------
        | VENTAS DIARIAS
        |--------------------------------------------------------------------------
        */

        $consulta = DB::table('detalle_ventas')
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
                'ventas.fecha',
                [
                    $fechaInicio,
                    $fechaFin,
                ]
            );

        if ($productoId !== null) {
            $consulta->where(
                'detalle_ventas.producto_id',
                $productoId
            );
        }

        $ventasPorFecha = $consulta
            ->selectRaw(
                'DATE(ventas.fecha) as fecha'
            )
            ->selectRaw(
                'SUM(detalle_ventas.cantidad) as cantidad'
            )
            ->groupBy(
                DB::raw('DATE(ventas.fecha)')
            )
            ->orderBy('fecha')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | CONSTRUIR TODOS LOS DÍAS
        |--------------------------------------------------------------------------
        |
        | Es importante incluir los días sin ventas como 0.
        | De esta forma el promedio y el gráfico representan
        | realmente el período seleccionado.
        |
        */

        $ventasMap = [];

        foreach ($ventasPorFecha as $venta) {
            $ventasMap[$venta->fecha] = (int) $venta->cantidad;
        }


        $datosGrafico = [];

        $fecha = $fechaInicio->copy();

        while ($fecha->lte($fechaFin)) {

            $fechaTexto = $fecha->toDateString();

            $datosGrafico[] = [
                'fecha' => $fechaTexto,
                'cantidad' => $ventasMap[$fechaTexto] ?? 0,
            ];

            $fecha->addDay();
        }


        /*
        |--------------------------------------------------------------------------
        | RESUMEN
        |--------------------------------------------------------------------------
        */

        $demandaTotal = collect($datosGrafico)
            ->sum('cantidad');

        $demandaPromedio = $dias > 0
            ? $demandaTotal / $dias
            : 0;


        /*
        |--------------------------------------------------------------------------
        | DÍA DE MAYOR DEMANDA
        |--------------------------------------------------------------------------
        */

        $diaMayor = collect($datosGrafico)
            ->sortByDesc('cantidad')
            ->first();

        $fechaMayorDemanda = null;
        $cantidadMayorDemanda = 0;

        if ($diaMayor && $diaMayor['cantidad'] > 0) {

            $fechaMayorDemanda = Carbon::parse(
                $diaMayor['fecha']
            )->locale('es')->translatedFormat('d \d\e F');

            $cantidadMayorDemanda =
                $diaMayor['cantidad'];
        }


        /*
        |--------------------------------------------------------------------------
        | TENDENCIA
        |--------------------------------------------------------------------------
        */

        $mitad = max(1, (int) floor($dias / 2));

        $primeraMitad = collect($datosGrafico)
            ->take($mitad);

        $segundaMitad = collect($datosGrafico)
            ->slice($mitad);

        $promedioPrimeraMitad =
            $primeraMitad->avg('cantidad') ?? 0;

        $promedioSegundaMitad =
            $segundaMitad->avg('cantidad') ?? 0;


        $tendenciaPorcentaje = 0;

        if ($promedioPrimeraMitad > 0) {

            $tendenciaPorcentaje =
                (
                    (
                        $promedioSegundaMitad
                        - $promedioPrimeraMitad
                    )
                    / $promedioPrimeraMitad
                ) * 100;
        }


        if ($tendenciaPorcentaje > 5) {

            $tendencia = 'Creciente';

        } elseif ($tendenciaPorcentaje < -5) {

            $tendencia = 'Decreciente';

        } else {

            $tendencia = 'Estable';
        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCTO SELECCIONADO
        |--------------------------------------------------------------------------
        */

        $productoSeleccionado = null;

        if ($productoId !== null) {

            $productoSeleccionado = $productos
                ->firstWhere('id', $productoId);
        }


        /*
        |--------------------------------------------------------------------------
        | DÍAS CON MAYOR DEMANDA
        |--------------------------------------------------------------------------
        */

        $principalesDias = collect($datosGrafico)
            ->filter(function ($dato) {
                return $dato['cantidad'] > 0;
            })
            ->sortByDesc('cantidad')
            ->take(5)
            ->values()
            ->map(function ($dato) {

                return [
                    'fecha' => Carbon::parse(
                        $dato['fecha']
                    )->locale('es')->translatedFormat(
                        'd \d\e F'
                    ),

                    'cantidad' => $dato['cantidad'],
                ];
            })
            ->all();


        /*
        |--------------------------------------------------------------------------
        | NOMBRE DEL PERÍODO
        |--------------------------------------------------------------------------
        */

        $nombrePeriodo = match ($periodo) {
            '7' => 'Últimos 7 días',
            '30' => 'Últimos 30 días',
            '90' => 'Últimos 90 días',
            '365' => 'Último año',
            default => 'Últimos 30 días',
        };


        /*
        |--------------------------------------------------------------------------
        | RETORNAR VISTA
        |--------------------------------------------------------------------------
        */

        return view(
            'analisis.index',
            compact(
                'productos',
                'productoId',
                'productoSeleccionado',
                'periodo',
                'nombrePeriodo',
                'datosGrafico',
                'demandaTotal',
                'demandaPromedio',
                'fechaMayorDemanda',
                'cantidadMayorDemanda',
                'tendencia',
                'tendenciaPorcentaje',
                'principalesDias'
            )
        );
    }
}