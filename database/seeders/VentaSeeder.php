<?php

namespace Database\Seeders;

use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Models\DetalleVenta;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VentaSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = User::first();

        if (!$usuario) {
            $this->command->error('No existe un usuario administrador.');
            return;
        }

        $productos = Producto::where('activo', true)->get();

        if ($productos->isEmpty()) {
            $this->command->error('No existen productos activos.');
            return;
        }

        DB::transaction(function () use ($usuario, $productos) {

            $inicio = Carbon::create(2025, 1, 1);
            $fin = Carbon::create(2026, 8, 31);

            /*
             * Generamos ventas diariamente.
             *
             * La demanda se modifica considerando:
             * - Día de semana
             * - Fin de semana
             * - Mes
             * - Temporada
             * - Variación aleatoria
             */

            for ($fecha = $inicio->copy(); $fecha->lte($fin); $fecha->addDay()) {

                /*
                 * Cantidad aproximada de ventas del día.
                 *
                 * Entre semana: demanda normal
                 * Viernes: mayor demanda
                 * Sábado: mayor demanda
                 * Domingo: demanda ligeramente menor
                 */
                $diaSemana = $fecha->dayOfWeek;

                $factorDia = match ($diaSemana) {
                    Carbon::MONDAY => 0.85,
                    Carbon::TUESDAY => 0.90,
                    Carbon::WEDNESDAY => 0.95,
                    Carbon::THURSDAY => 1.00,
                    Carbon::FRIDAY => 1.20,
                    Carbon::SATURDAY => 1.35,
                    Carbon::SUNDAY => 1.10,
                };

                /*
                 * Factor mensual.
                 *
                 * Simulamos una demanda ligeramente diferente
                 * durante el año.
                 */
                $factorMes = match ($fecha->month) {
                    1 => 0.95,
                    2 => 0.95,
                    3 => 1.00,
                    4 => 1.00,
                    5 => 1.10,
                    6 => 1.05,
                    7 => 1.20,
                    8 => 1.15,
                    9 => 1.00,
                    10 => 1.05,
                    11 => 1.10,
                    12 => 1.30,
                };

                /*
                 * Cantidad de operaciones del día.
                 */
                $cantidadVentas = max(
                    3,
                    (int) round(rand(8, 18) * $factorDia * $factorMes)
                );

                for ($i = 0; $i < $cantidadVentas; $i++) {

                    /*
                     * Hora aleatoria dentro del horario comercial.
                     */
                    $fechaVenta = $fecha->copy()
                        ->setTime(
                            rand(7, 20),
                            rand(0, 59),
                            rand(0, 59)
                        );

                    /*
                     * Elegimos entre 1 y 3 productos por venta.
                     */
                    $cantidadProductos = min(
                        rand(1, 3),
                        $productos->count()
                    );

                    $productosVenta = $productos
                        ->random($cantidadProductos);

                    $total = 0;

                    $venta = Venta::create([
                        'usuario_id' => $usuario->id,
                        'fecha' => $fechaVenta,
                        'total' => 0,
                        'estado' => 'completada',
                    ]);

                    foreach ($productosVenta as $producto) {

                        /*
                         * Demanda base del producto.
                         *
                         * Productos diferentes tendrán
                         * comportamientos diferentes.
                         */
                        $base = match ($producto->id % 5) {
                            0 => 2,
                            1 => 3,
                            2 => 2,
                            3 => 4,
                            default => 3,
                        };

                        /*
                         * Variación de cantidad vendida.
                         */
                        $cantidad = max(
                            1,
                            (int) round(
                                $base *
                                $factorDia *
                                $factorMes *
                                (rand(70, 130) / 100)
                            )
                        );

                        $precio = $producto->precio;

                        $subtotal = $cantidad * $precio;

                        DetalleVenta::create([
                            'venta_id' => $venta->id,
                            'producto_id' => $producto->id,
                            'cantidad' => $cantidad,
                            'precio_unitario' => $precio,
                            'subtotal' => $subtotal,
                        ]);

                        $total += $subtotal;
                    }

                    $venta->update([
                        'total' => $total,
                    ]);
                }
            }
        });

        $this->command->info('Histórico de ventas generado correctamente.');
    }
}