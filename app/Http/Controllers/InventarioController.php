<?php

namespace App\Http\Controllers;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioController extends Controller
{
    /**
     * Mostrar inventario.
     */
    public function index()
    {
        $productos = Producto::with('categoria')
            ->orderBy('nombre')
            ->get();

        $movimientos = MovimientoInventario::with([
                'producto',
                'usuario'
            ])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->take(20)
            ->get();

        $totalProductos = $productos->count();

        $stockBajo = $productos->filter(function ($producto) {
            return $producto->stock <= $producto->stock_minimo;
        })->count();

        $stockTotal = $productos->sum('stock');

        $productosActivos = $productos
            ->where('activo', true)
            ->count();

        return view('inventario.index', compact(
            'productos',
            'movimientos',
            'totalProductos',
            'stockBajo',
            'stockTotal',
            'productosActivos'
        ));
    }


    /**
     * Registrar un movimiento de inventario.
     */
    public function storeMovimiento(Request $request)
    {
        $request->validate([
            'producto_id' => [
                'required',
                'integer',
                'exists:productos,id',
            ],

            'tipo' => [
                'required',
                'in:entrada,salida,reposicion,ajuste',
            ],

            'cantidad' => [
                'required',
                'integer',
                'min:1',
            ],

            'motivo' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);


        DB::transaction(function () use ($request) {

            /*
            |--------------------------------------------------------------------------
            | Bloquear producto mientras se modifica
            |--------------------------------------------------------------------------
            */

            $producto = Producto::where(
                'id',
                $request->producto_id
            )
            ->lockForUpdate()
            ->firstOrFail();


            $stockAnterior = (int) $producto->stock;

            $cantidad = (int) $request->cantidad;

            $tipo = $request->tipo;


            /*
            |--------------------------------------------------------------------------
            | Calcular nuevo stock
            |--------------------------------------------------------------------------
            */

            switch ($tipo) {

                case 'entrada':

                    $stockNuevo =
                        $stockAnterior + $cantidad;

                    break;


                case 'reposicion':

                    $stockNuevo =
                        $stockAnterior + $cantidad;

                    break;


                case 'salida':

                    $stockNuevo =
                        $stockAnterior - $cantidad;

                    break;


                case 'ajuste':

                    /*
                     * Para un ajuste, la cantidad representa
                     * el nuevo stock directamente.
                     */
                    $stockNuevo = $cantidad;

                    break;


                default:

                    throw new \Exception(
                        'Tipo de movimiento no válido.'
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | No permitir stock negativo
            |--------------------------------------------------------------------------
            */

            if ($stockNuevo < 0) {

                throw new \Exception(
                    'No hay suficiente stock para realizar esta salida.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Actualizar producto
            |--------------------------------------------------------------------------
            */

            $producto->update([
                'stock' => $stockNuevo,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Registrar movimiento
            |--------------------------------------------------------------------------
            */

            MovimientoInventario::create([

                'producto_id' =>
                    $producto->id,

                'usuario_id' => auth()->id(),

                'tipo' =>
                    $tipo,

                'cantidad' =>
                    $cantidad,

                'stock_anterior' =>
                    $stockAnterior,

                'stock_nuevo' =>
                    $stockNuevo,

                'motivo' =>
                    $request->motivo
                    ?: $this->obtenerMotivoPorDefecto($tipo),

                'fecha' =>
                    now(),

            ]);
        });


        return redirect()
            ->route('inventario.index')
            ->with(
                'success',
                'Movimiento registrado correctamente.'
            );
    }


    /**
     * Motivo automático.
     */
    private function obtenerMotivoPorDefecto(
        string $tipo
    ): string {

        return match ($tipo) {

            'entrada' =>
                'Entrada manual de inventario',

            'salida' =>
                'Salida manual de inventario',

            'reposicion' =>
                'Reposición de inventario',

            'ajuste' =>
                'Ajuste manual de inventario',

            default =>
                'Movimiento de inventario',
        };
    }
}