<?php

namespace App\Http\Controllers;

use App\Models\DetalleVenta;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    /**
     * =========================================================
     * LISTADO Y CONSULTA DE VENTAS
     * =========================================================
     */
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | FECHA SELECCIONADA
        |--------------------------------------------------------------------------
        */

        $fechaSeleccionada = $request->input(
            'fecha',
            Carbon::today()->toDateString()
        );

        /*
        |--------------------------------------------------------------------------
        | VALIDAR FECHA
        |--------------------------------------------------------------------------
        */

        try {

            $fecha = Carbon::parse($fechaSeleccionada);

        } catch (\Throwable $e) {

            $fecha = Carbon::today();

            $fechaSeleccionada =
                $fecha->toDateString();
        }


        /*
        |--------------------------------------------------------------------------
        | CONSULTA BASE DE VENTAS
        |--------------------------------------------------------------------------
        */

        $ventasDelDiaQuery = Venta::query()
            ->whereDate('fecha', $fecha->toDateString())
            ->whereNotIn('estado', [
                'cancelada',
                'anulada',
            ]);


        /*
        |--------------------------------------------------------------------------
        | CANTIDAD DE VENTAS
        |--------------------------------------------------------------------------
        */

        $ventasHoy =
            (clone $ventasDelDiaQuery)->count();


        /*
        |--------------------------------------------------------------------------
        | INGRESOS
        |--------------------------------------------------------------------------
        */

        $ingresosHoy =
            (float) (
                (clone $ventasDelDiaQuery)
                    ->sum('total')
            );


        /*
        |--------------------------------------------------------------------------
        | UNIDADES VENDIDAS
        |--------------------------------------------------------------------------
        */

        $unidadesHoy =
            (int) DetalleVenta::query()
                ->whereHas('venta', function ($query) use ($fecha) {

                    $query
                        ->whereDate(
                            'fecha',
                            $fecha->toDateString()
                        )
                        ->whereNotIn('estado', [
                            'cancelada',
                            'anulada',
                        ]);

                })
                ->sum('cantidad');


        /*
        |--------------------------------------------------------------------------
        | PRODUCTO MÁS VENDIDO
        |--------------------------------------------------------------------------
        */

        $productoMasVendido =
            DetalleVenta::query()
                ->select(
                    'producto_id',
                    DB::raw(
                        'SUM(cantidad) as cantidad_total'
                    )
                )
                ->whereHas('venta', function ($query) use ($fecha) {

                    $query
                        ->whereDate(
                            'fecha',
                            $fecha->toDateString()
                        )
                        ->whereNotIn('estado', [
                            'cancelada',
                            'anulada',
                        ]);

                })
                ->with('producto')
                ->groupBy('producto_id')
                ->orderByDesc('cantidad_total')
                ->first();


        /*
        |--------------------------------------------------------------------------
        | VENTAS DEL DÍA
        |--------------------------------------------------------------------------
        */

        $ventas =
            Venta::query()
                ->with([
                    'usuario',
                    'detalles.producto',
                ])
                ->whereDate(
                    'fecha',
                    $fecha->toDateString()
                )
                ->whereNotIn('estado', [
                    'cancelada',
                    'anulada',
                ])
                ->orderByDesc('fecha')
                ->limit(50)
                ->get();


        /*
        |--------------------------------------------------------------------------
        | TOTAL HISTÓRICO
        |--------------------------------------------------------------------------
        */

        $totalVentas =
            Venta::query()
                ->whereNotIn('estado', [
                    'cancelada',
                    'anulada',
                ])
                ->count();


        /*
        |--------------------------------------------------------------------------
        | TEXTO DE FECHA
        |--------------------------------------------------------------------------
        */

        $esHoy =
            $fecha->isToday();


        $fechaTexto =
            $esHoy
                ? 'Hoy'
                : $fecha->translatedFormat('d \d\e F \d\e Y');


        /*
        |--------------------------------------------------------------------------
        | RESUMEN
        |--------------------------------------------------------------------------
        */

        $resumen = [

            'ventas_hoy' =>
                $ventasHoy,

            'unidades_hoy' =>
                $unidadesHoy,

            'ingresos_hoy' =>
                $ingresosHoy,

            'producto_mas_vendido' =>
                $productoMasVendido?->producto?->nombre
                    ?? null,

            'cantidad_producto_mas_vendido' =>
                (int) (
                    $productoMasVendido
                        ->cantidad_total
                    ?? 0
                ),

            'total_ventas' =>
                $totalVentas,

            'es_hoy' =>
                $esHoy,

            'fecha_texto' =>
                $fechaTexto,

            'fecha_seleccionada' =>
                $fechaSeleccionada,

        ];


        return view(
            'ventas.index',
            compact(
                'ventas',
                'resumen'
            )
        );
    }
}