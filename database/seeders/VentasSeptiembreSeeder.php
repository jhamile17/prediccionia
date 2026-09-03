<?php

namespace Database\Seeders;

use App\Models\DetalleVenta;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VentasSeptiembreSeeder extends Seeder
{
    public function run(): void
    {
        $usuario = User::first();

        if (!$usuario) {
            $this->command->error(
                'No existe un usuario administrador.'
            );

            return;
        }

        $productos = Producto::where('activo', true)
            ->orderBy('id')
            ->get();

        if ($productos->isEmpty()) {
            $this->command->error(
                'No existen productos activos.'
            );

            return;
        }

        DB::transaction(function () use (
            $usuario,
            $productos
        ) {

            /*
             * =====================================================
             * FECHAS
             * =====================================================
             */

            $fechas = [
                Carbon::create(2026, 9, 1),
                Carbon::create(2026, 9, 2),
            ];

            foreach ($fechas as $fecha) {

                $existe = Venta::whereBetween('fecha', [
                    $fecha->copy()->startOfDay(),
                    $fecha->copy()->endOfDay(),
                ])->exists();

                if ($existe) {

                    $this->command->warn(
                        'Ya existen ventas para ' .
                        $fecha->toDateString() .
                        '. No se generarán nuevamente.'
                    );

                    return;
                }
            }
            /*
             * =====================================================
             * DEMANDA BASE POR PRODUCTO
             * =====================================================
             *
             * Mantenemos la misma lógica de la simulación.
             */

            $demandaBase = [
                1  => 8,
                2  => 6,
                3  => 11,
                4  => 8,
                5  => 6,
                6  => 9,
                7  => 6,
                8  => 11,
                9  => 8,
                10 => 6,
                11 => 8,
                12 => 6,
            ];

            /*
             * =====================================================
             * FACTORES POR DÍA
             * =====================================================
             */

            $factorDia = [
                Carbon::MONDAY    => 0.85,
                Carbon::TUESDAY   => 0.90,
                Carbon::WEDNESDAY => 0.95,
                Carbon::THURSDAY  => 1.00,
                Carbon::FRIDAY    => 1.20,
                Carbon::SATURDAY  => 1.35,
                Carbon::SUNDAY    => 1.10,
            ];

            /*
             * =====================================================
             * FACTORES MENSUALES
             * =====================================================
             */

            $factorMes = [
                1  => 0.95,
                2  => 0.95,
                3  => 1.00,
                4  => 1.00,
                5  => 1.10,
                6  => 1.05,
                7  => 1.20,
                8  => 1.15,
                9  => 1.00,
                10 => 1.05,
                11 => 1.10,
                12 => 1.30,
            ];

            /*
             * =====================================================
             * DEMANDA ANTERIOR POR PRODUCTO
             * =====================================================
             *
             * Partimos de la demanda real del 31/08.
             */

            $demandaAnterior = [];

            foreach ($productos as $producto) {

                $demandaAnterior[$producto->id] =
                    (int) DB::table('detalle_ventas')
                        ->join(
                            'ventas',
                            'ventas.id',
                            '=',
                            'detalle_ventas.venta_id'
                        )
                        ->where(
                            'detalle_ventas.producto_id',
                            $producto->id
                        )
                        ->where(
                            'ventas.estado',
                            'completada'
                        )
                        ->whereDate(
                            'ventas.fecha',
                            '2026-08-31'
                        )
                        ->sum(
                            'detalle_ventas.cantidad'
                        );

                /*
                 * Seguridad por si algún producto no tuviera
                 * ventas el 31/08.
                 */
                if ($demandaAnterior[$producto->id] <= 0) {
                    $demandaAnterior[$producto->id] =
                        $demandaBase[$producto->id] ?? 7;
                }
            }

            /*
             * =====================================================
             * GENERAR 01/09 Y 02/09
             * =====================================================
             */

            foreach ($fechas as $fecha) {

                $factorDiaActual =
                    $factorDia[$fecha->dayOfWeek];

                $factorMesActual =
                    $factorMes[$fecha->month];

                /*
                 * Pequeña tendencia coherente.
                 */
                $factorTendencia = 1.12;

                /*
                 * =================================================
                 * DEMANDA DEL DÍA
                 * =================================================
                 */

                $demandaDelDia = [];

                foreach ($productos as $producto) {

                    $id = $producto->id;

                    $base =
                        $demandaBase[$id] ?? 7;

                    /*
                     * Misma idea utilizada en VentaSeeder:
                     * parte de la demanda base y parte del
                     * comportamiento anterior.
                     */

                    $demandaEsperada =
                        (
                            0.65 * $base +
                            0.35 * $demandaAnterior[$id]
                        )
                        * $factorDiaActual
                        * $factorMesActual
                        * $factorTendencia;

                    /*
                     * Ruido pequeño y controlado.
                     */
                    $ruido =
                        rand(95, 105) / 100;

                    $cantidad =
                        max(
                            1,
                            (int) round(
                                $demandaEsperada * $ruido
                            )
                        );

                    /*
                     * Limite razonable.
                     */
                    $cantidad = min(
                        $cantidad,
                        35
                    );

                    $demandaDelDia[$id] =
                        $cantidad;
                }

                /*
                 * =================================================
                 * DISTRIBUIR LA DEMANDA EN VENTAS
                 * =================================================
                 *
                 * Cada producto se reparte entre varias
                 * operaciones pequeñas para que no tengamos
                 * una sola venta gigantesca.
                 */

                $pendientePorProducto =
                    $demandaDelDia;

                /*
                 * Aproximadamente 10-14 operaciones iniciales.
                 * Las operaciones adicionales se crean solo cuando
                 * todavía queda demanda pendiente.
                 */

                $cantidadVentasObjetivo = 12;

                for (
                    $i = 0;
                    $i < $cantidadVentasObjetivo;
                    $i++
                ) {

                    $disponibles = $productos->filter(
                        function ($producto) use (
                            $pendientePorProducto
                        ) {
                            return (
                                ($pendientePorProducto[
                                    $producto->id
                                ] ?? 0) > 0
                            );
                        }
                    );

                    if ($disponibles->isEmpty()) {
                        break;
                    }

                    $producto =
                        $disponibles->random();

                    $pendiente =
                        $pendientePorProducto[
                            $producto->id
                        ];

                    /*
                     * Normalmente 1 unidad,
                     * ocasionalmente 2.
                     */
                    $cantidad =
                        (
                            $pendiente >= 2 &&
                            rand(1, 100) <= 25
                        )
                            ? 2
                            : 1;

                    $cantidad =
                        min(
                            $cantidad,
                            $pendiente
                        );

                    $fechaVenta =
                        $fecha->copy()->setTime(
                            rand(7, 20),
                            rand(0, 59),
                            rand(0, 59)
                        );

                    $venta = Venta::create([
                        'usuario_id' =>
                            $usuario->id,

                        'fecha' =>
                            $fechaVenta,

                        'total' =>
                            0,

                        'estado' =>
                            'completada',
                    ]);

                    $precio =
                        $producto->precio;

                    $subtotal =
                        $cantidad * $precio;

                    DetalleVenta::create([
                        'venta_id' =>
                            $venta->id,

                        'producto_id' =>
                            $producto->id,

                        'cantidad' =>
                            $cantidad,

                        'precio_unitario' =>
                            $precio,

                        'subtotal' =>
                            $subtotal,
                    ]);

                    $venta->update([
                        'total' =>
                            $subtotal,
                    ]);

                    $pendientePorProducto[
                        $producto->id
                    ] -= $cantidad;
                }

                /*
                 * =================================================
                 * COMPLETAR DEMANDA PENDIENTE
                 * =================================================
                 */

                foreach ($productos as $producto) {

                    $pendiente =
                        $pendientePorProducto[
                            $producto->id
                        ] ?? 0;

                    while ($pendiente > 0) {

                        $cantidad =
                            min(
                                $pendiente,
                                rand(1, 2)
                            );

                        $fechaVenta =
                            $fecha->copy()->setTime(
                                rand(7, 20),
                                rand(0, 59),
                                rand(0, 59)
                            );

                        $venta = Venta::create([
                            'usuario_id' =>
                                $usuario->id,

                            'fecha' =>
                                $fechaVenta,

                            'total' =>
                                0,

                            'estado' =>
                                'completada',
                        ]);

                        $precio =
                            $producto->precio;

                        $subtotal =
                            $cantidad * $precio;

                        DetalleVenta::create([
                            'venta_id' =>
                                $venta->id,

                            'producto_id' =>
                                $producto->id,

                            'cantidad' =>
                                $cantidad,

                            'precio_unitario' =>
                                $precio,

                            'subtotal' =>
                                $subtotal,
                        ]);

                        $venta->update([
                            'total' =>
                                $subtotal,
                        ]);

                        $pendiente -= $cantidad;
                    }

                    /*
                     * La demanda de este día se convierte
                     * en la referencia del siguiente.
                     */
                    $demandaAnterior[
                        $producto->id
                    ] = $demandaDelDia[
                        $producto->id
                    ];
                }
            }
        });

        $this->command->info(
            'Ventas simuladas del 01/09/2026 y 02/09/2026 generadas correctamente.'
        );
    }
}