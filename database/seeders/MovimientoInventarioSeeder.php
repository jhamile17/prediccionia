<?php

namespace Database\Seeders;

use App\Models\DetalleVenta;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MovimientoInventarioSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = User::first();

        if (!$usuario) {
            $this->command->error('No existe ningún usuario.');
            return;
        }

        /*
         * IMPORTANTE:
         * La tabla real se llama:
         *
         * movimientos_inventario
         *
         * Por eso utilizamos DB::table() directamente.
         */

        if (DB::table('movimientos_inventario')->exists()) {
            $this->command->warn(
                'Ya existen movimientos de inventario. No se generaron duplicados.'
            );

            return;
        }

        /*
         * Obtenemos los detalles de ventas completadas.
         */
        $detalles = DetalleVenta::with('venta', 'producto')
            ->whereHas('venta', function ($query) {
                $query->where('estado', 'completada');
            })
            ->orderBy('venta_id')
            ->orderBy('id')
            ->get();

        if ($detalles->isEmpty()) {
            $this->command->error(
                'No existen detalles de ventas para generar movimientos.'
            );
            return;
        }

        DB::transaction(function () use ($detalles, $usuario) {

            /*
             * Stock histórico por producto.
             */
            $stocks = [];

            foreach ($detalles as $detalle) {

                if (!$detalle->venta || !$detalle->producto) {
                    continue;
                }

                $productoId = $detalle->producto_id;
                $cantidad = (int) $detalle->cantidad;

                if ($cantidad <= 0) {
                    continue;
                }

                /*
                 * Inicializamos el stock del producto.
                 */
                if (!isset($stocks[$productoId])) {
                    $stocks[$productoId] =
                        (int) $detalle->producto->stock;
                }

                $stockActual = $stocks[$productoId];

                /*
                 * Si no hay stock suficiente,
                 * hacemos una reposición.
                 */
                if ($stockActual < $cantidad) {

                    $cantidadReposicion = max(
                        30,
                        $cantidad * 2
                    );

                    $stockAnterior = $stockActual;
                    $stockNuevo = $stockActual + $cantidadReposicion;

                    DB::table('movimientos_inventario')->insert([
                        'producto_id' => $productoId,
                        'usuario_id' => $usuario->id,
                        'tipo' => 'reposicion',
                        'cantidad' => $cantidadReposicion,
                        'stock_anterior' => $stockAnterior,
                        'stock_nuevo' => $stockNuevo,
                        'motivo' => 'Reposición de stock por demanda',
                        'fecha' => $detalle->venta->fecha,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $stockActual = $stockNuevo;
                }

                /*
                 * Registramos la salida correspondiente
                 * a la venta.
                 */
                $stockAnterior = $stockActual;
                $stockNuevo = $stockActual - $cantidad;

                DB::table('movimientos_inventario')->insert([
                    'producto_id' => $productoId,
                    'usuario_id' => $usuario->id,
                    'tipo' => 'salida',
                    'cantidad' => $cantidad,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $stockNuevo,
                    'motivo' => 'Salida por venta',
                    'fecha' => $detalle->venta->fecha,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $stocks[$productoId] = $stockNuevo;
            }
        });

        $total = DB::table('movimientos_inventario')->count();

        $this->command->info(
            "Movimientos de inventario generados correctamente: {$total}"
        );
    }
}

