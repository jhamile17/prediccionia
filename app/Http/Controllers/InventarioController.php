<?php

namespace App\Http\Controllers;

use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventarioController extends Controller
{
    /**
     * ============================================================
     * MOSTRAR INVENTARIO
     * ============================================================
     */
    public function index()
    {
        $productos = Producto::with('categoria')
            ->orderBy('nombre')
            ->get();

        $movimientos = MovimientoInventario::with([
                'producto',
                'usuario',
            ])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->take(20)
            ->get();

        $totalProductos = $productos->count();

        $stockBajo = $productos->filter(
            function ($producto) {
                return $producto->stock <= $producto->stock_minimo;
            }
        )->count();

        $stockTotal = $productos->sum('stock');

        $productosActivos = $productos
            ->where('activo', true)
            ->count();

        return view(
            'inventario.index',
            compact(
                'productos',
                'movimientos',
                'totalProductos',
                'stockBajo',
                'stockTotal',
                'productosActivos'
            )
        );
    }


    /**
     * ============================================================
     * REGISTRAR MOVIMIENTO DE INVENTARIO
     * ============================================================
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


        /*
         * ========================================================
         * TRANSACCIÓN
         * ========================================================
         *
         * Se bloquea el producto para evitar que dos operaciones
         * modifiquen el stock simultáneamente.
         */

        $movimiento = DB::transaction(
            function () use ($request) {

                $producto = Producto::where(
                    'id',
                    $request->producto_id
                )
                ->lockForUpdate()
                ->firstOrFail();


                /*
                 * ------------------------------------------------
                 * DATOS ACTUALES
                 * ------------------------------------------------
                 */

                $stockAnterior =
                    (int) $producto->stock;

                $cantidad =
                    (int) $request->cantidad;

                $tipo =
                    $request->tipo;


                /*
                 * ------------------------------------------------
                 * CALCULAR NUEVO STOCK
                 * ------------------------------------------------
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
                         * En un ajuste, la cantidad representa
                         * directamente el nuevo stock.
                         */
                        $stockNuevo =
                            $cantidad;

                        break;


                    default:

                        throw new \RuntimeException(
                            'Tipo de movimiento no válido.'
                        );
                }


                /*
                 * ------------------------------------------------
                 * VALIDAR STOCK
                 * ------------------------------------------------
                 */

                if ($stockNuevo < 0) {

                    throw new \RuntimeException(
                        'No hay suficiente stock para realizar esta salida.'
                    );
                }


                /*
                 * ------------------------------------------------
                 * ACTUALIZAR STOCK
                 * ------------------------------------------------
                 */

                $producto->update([
                    'stock' =>
                        $stockNuevo,
                ]);


                /*
                 * ------------------------------------------------
                 * MOTIVO
                 * ------------------------------------------------
                 */

                $motivo =
                    $request->filled('motivo')
                        ? $request->input('motivo')
                        : $this->obtenerMotivoPorDefecto(
                            $tipo
                        );


                /*
                 * ------------------------------------------------
                 * REGISTRAR MOVIMIENTO
                 * ------------------------------------------------
                 */

                $registro =
                    MovimientoInventario::create([

                        'producto_id' =>
                            $producto->id,

                        'usuario_id' =>
                            auth()->id(),

                        'tipo' =>
                            $tipo,

                        'cantidad' =>
                            $cantidad,

                        'stock_anterior' =>
                            $stockAnterior,

                        'stock_nuevo' =>
                            $stockNuevo,

                        'motivo' =>
                            $motivo,

                        'fecha' =>
                            now(),
                    ]);


                /*
                 * ------------------------------------------------
                 * DATOS PARA LA RESPUESTA
                 * ------------------------------------------------
                 */

                return [

                    'producto_id' =>
                        (int) $producto->id,

                    'producto' =>
                        $producto->nombre,

                    'stock_anterior' =>
                        $stockAnterior,

                    'cantidad' =>
                        $cantidad,

                    'stock_nuevo' =>
                        $stockNuevo,

                    'tipo' =>
                        $tipo,

                    'movimiento_id' =>
                        (int) $registro->id,
                ];
            }
        );


        /*
         * ========================================================
         * RESPUESTA JSON
         * ========================================================
         *
         * El Dashboard utiliza esta respuesta para actualizar
         * el estado sin tener que navegar al inventario.
         */

        if ($request->expectsJson()) {

            return response()->json([

                'success' =>
                    true,

                'message' =>
                    $movimiento['tipo'] === 'reposicion'
                        ? 'Reposición registrada correctamente.'
                        : 'Movimiento registrado correctamente.',

                'producto_id' =>
                    $movimiento['producto_id'],

                'producto' =>
                    $movimiento['producto'],

                'stock_anterior' =>
                    $movimiento['stock_anterior'],

                'cantidad' =>
                    $movimiento['cantidad'],

                'stock_nuevo' =>
                    $movimiento['stock_nuevo'],

                'tipo' =>
                    $movimiento['tipo'],

                'movimiento_id' =>
                    $movimiento['movimiento_id'],
            ]);
        }


        /*
         * ========================================================
         * FORMULARIO NORMAL
         * ========================================================
         *
         * La pantalla de Inventario continúa funcionando como
         * antes.
         */

        return redirect()
            ->route('inventario.index')
            ->with(
                'success',
                'Movimiento registrado correctamente.'
            );
    }


    /**
     * ============================================================
     * MOTIVO AUTOMÁTICO
     * ============================================================
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
                'Reposición sugerida por el sistema',

            'ajuste' =>
                'Ajuste manual de inventario',

            default =>
                'Movimiento de inventario',
        };
    }
}