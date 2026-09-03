<?php

namespace App\Http\Controllers;

use App\Models\DetalleVenta;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            $fechaSeleccionada = $fecha->toDateString();
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

        $ventasHoy = (clone $ventasDelDiaQuery)->count();

        /*
        |--------------------------------------------------------------------------
        | INGRESOS
        |--------------------------------------------------------------------------
        */

        $ingresosHoy = (float) (
            (clone $ventasDelDiaQuery)->sum('total')
        );

        /*
        |--------------------------------------------------------------------------
        | UNIDADES VENDIDAS
        |--------------------------------------------------------------------------
        */

        $unidadesHoy = (int) DetalleVenta::query()
            ->whereHas('venta', function ($query) use ($fecha) {
                $query
                    ->whereDate('fecha', $fecha->toDateString())
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

        $productoMasVendido = DetalleVenta::query()
            ->select(
                'producto_id',
                DB::raw('SUM(cantidad) as cantidad_total')
            )
            ->whereHas('venta', function ($query) use ($fecha) {
                $query
                    ->whereDate('fecha', $fecha->toDateString())
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

        $ventas = Venta::query()
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

        $totalVentas = Venta::query()
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

        $esHoy = $fecha->isToday();

        $fechaTexto = $esHoy
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

    /**
     * =========================================================
     * FORMULARIO DE NUEVA VENTA
     * =========================================================
     */
    public function create()
    {
        $productos = Producto::query()
            ->orderBy('nombre')
            ->get();

        return view(
            'ventas.create',
            compact('productos')
        );
    }

    /**
     * =========================================================
     * REGISTRAR NUEVA VENTA
     * =========================================================
     */
    public function store(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | VALIDACIÓN
        |--------------------------------------------------------------------------
        */

        $datos = $request->validate([
            'producto_id' => [
                'required',
                'integer',
                'exists:productos,id',
            ],

            'cantidad' => [
                'required',
                'integer',
                'min:1',
                'max:999',
            ],
        ], [
            'producto_id.required' =>
                'Selecciona un producto.',

            'producto_id.exists' =>
                'El producto seleccionado no existe.',

            'cantidad.required' =>
                'Ingresa la cantidad.',

            'cantidad.integer' =>
                'La cantidad debe ser un número entero.',

            'cantidad.min' =>
                'La cantidad debe ser como mínimo 1.',

            'cantidad.max' =>
                'La cantidad no puede ser mayor a 999.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | TRANSACCIÓN
        |--------------------------------------------------------------------------
        */

        try {

            $resultado = DB::transaction(function () use ($datos) {

                /*
                |--------------------------------------------------------------------------
                | BLOQUEAR PRODUCTO
                |--------------------------------------------------------------------------
                */

                $producto = Producto::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $datos['producto_id']
                    );

                $cantidad = (int) $datos['cantidad'];

                $stockAnterior = (int) $producto->stock;

                /*
                |--------------------------------------------------------------------------
                | VALIDAR STOCK
                |--------------------------------------------------------------------------
                */

                if ($stockAnterior < $cantidad) {

                    throw ValidationException::withMessages([
                        'cantidad' =>
                            "No hay suficiente stock. " .
                            "Stock disponible: {$stockAnterior} unidades."
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | PRECIO DEL PRODUCTO
                |--------------------------------------------------------------------------
                */

                $precioUnitario =
                    (float) $producto->precio;

                $subtotal =
                    $precioUnitario * $cantidad;

                /*
                |--------------------------------------------------------------------------
                | CREAR VENTA
                |--------------------------------------------------------------------------
                */

                $venta = Venta::create([
                    'usuario_id' =>
                        auth()->id(),

                    'fecha' =>
                        now(),

                    'total' =>
                        $subtotal,

                    'estado' =>
                        'completada',
                ]);

                /*
                |--------------------------------------------------------------------------
                | CREAR DETALLE
                |--------------------------------------------------------------------------
                */

                $detalle = DetalleVenta::create([
                    'venta_id' =>
                        $venta->id,

                    'producto_id' =>
                        $producto->id,

                    'cantidad' =>
                        $cantidad,

                    'precio_unitario' =>
                        $precioUnitario,

                    'subtotal' =>
                        $subtotal,
                ]);

                /*
                |--------------------------------------------------------------------------
                | ACTUALIZAR STOCK
                |--------------------------------------------------------------------------
                */

                $stockNuevo =
                    $stockAnterior - $cantidad;

                $producto->update([
                    'stock' => $stockNuevo,
                ]);

                /*
                |--------------------------------------------------------------------------
                | REGISTRAR MOVIMIENTO DE INVENTARIO
                |--------------------------------------------------------------------------
                */

                MovimientoInventario::create([
                    'producto_id' =>
                        $producto->id,

                    'usuario_id' =>
                        auth()->id(),

                    'tipo' =>
                        'salida',

                    'cantidad' =>
                        $cantidad,

                    'stock_anterior' =>
                        $stockAnterior,

                    'stock_nuevo' =>
                        $stockNuevo,

                    'motivo' =>
                        'Salida por venta #' . $venta->id,

                    'fecha' =>
                        now(),
                ]);

                /*
                |--------------------------------------------------------------------------
                | RESULTADO
                |--------------------------------------------------------------------------
                */

                return [
                    'venta' =>
                        $venta,

                    'detalle' =>
                        $detalle,

                    'producto' =>
                        $producto->fresh(),

                    'stock_anterior' =>
                        $stockAnterior,

                    'stock_nuevo' =>
                        $stockNuevo,

                    'cantidad' =>
                        $cantidad,

                    'subtotal' =>
                        $subtotal,
                ];
            });

            /*
            |--------------------------------------------------------------------------
            | RESPUESTA
            |--------------------------------------------------------------------------
            */

            return redirect()
                ->route(
                    'ventas.create'
                )
                ->with(
                    'success',
                    'Venta #' .
                    $resultado['venta']->id .
                    ' registrada correctamente.'
                );

        } catch (ValidationException $e) {

            throw $e;

        } catch (\Throwable $e) {

            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'No se pudo registrar la venta. ' .
                    'Revisa los datos e inténtalo nuevamente.'
                );
        }
    }
}