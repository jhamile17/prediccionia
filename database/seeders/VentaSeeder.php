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

        $productos = Producto::where('activo', true)
            ->orderBy('id')
            ->get();

        if ($productos->isEmpty()) {
            $this->command->error('No existen productos activos.');
            return;
        }

        DB::transaction(function () use ($usuario, $productos) {

            /*
            |--------------------------------------------------------------------------
            | PERIODO DE SIMULACIÓN
            |--------------------------------------------------------------------------
            */

            $inicio = Carbon::create(2025, 1, 1);
            $fin = Carbon::create(2026, 8, 31);

            /*
            |--------------------------------------------------------------------------
            | DEMANDA BASE POR PRODUCTO
            |--------------------------------------------------------------------------
            |
            | Estos valores representan aproximadamente cuántas unidades
            | esperamos vender diariamente antes de aplicar factores.
            |
            */

            $demandaBase = [
                1  => 8,   // Americano
                2  => 6,   // Cappuccino
                3  => 11,  // Latte
                4  => 8,   // Mocaccino
                5  => 6,   // Cold Brew
                6  => 9,   // Frappé de Café
                7  => 6,   // Frappé de Fresa
                8  => 11,  // Jugo de Maracuyá
                9  => 8,   // Pan con Jamón y Queso
                10 => 6,   // Pan con Huevo
                11 => 8,   // Pan con Palta
                12 => 6,   // Torta de Chocolate
            ];

            /*
            |--------------------------------------------------------------------------
            | FACTORES POR DÍA DE SEMANA
            |--------------------------------------------------------------------------
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
            |--------------------------------------------------------------------------
            | FACTORES MENSUALES
            |--------------------------------------------------------------------------
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
            |--------------------------------------------------------------------------
            | TENDENCIA GENERAL
            |--------------------------------------------------------------------------
            |
            | La demanda aumenta ligeramente conforme avanza el periodo.
            | Esto permite que la IA aprenda evolución temporal.
            |
            */

            $totalDias = $inicio->diffInDays($fin) + 1;

            /*
            |--------------------------------------------------------------------------
            | MEMORIA DE DEMANDA
            |--------------------------------------------------------------------------
            |
            | Guardaremos la demanda previa de cada producto para evitar
            | que cada día sea completamente independiente.
            |
            */

            $demandaAnterior = [];

            foreach ($productos as $producto) {
                $demandaAnterior[$producto->id] =
                    $demandaBase[$producto->id] ?? 7;
            }

            /*
            |--------------------------------------------------------------------------
            | GENERACIÓN DIARIA
            |--------------------------------------------------------------------------
            */

            for (
                $fecha = $inicio->copy();
                $fecha->lte($fin);
                $fecha->addDay()
            ) {

                $diaSemana = $fecha->dayOfWeek;

                $factorDiaActual = $factorDia[$diaSemana];

                $factorMesActual = $factorMes[$fecha->month];

                /*
                | Progreso del periodo:
                | 0.00 al inicio
                | 1.00 al final
                */
                $progreso = $inicio->diffInDays($fecha) / $totalDias;

                /*
                | Tendencia gradual:
                | desde 1.00 hasta aproximadamente 1.12
                */
                $factorTendencia = 1.00 + ($progreso * 0.12);

                /*
                |--------------------------------------------------------------------------
                | DÍAS ESPECIALES SIMULADOS
                |--------------------------------------------------------------------------
                */

                $factorEspecial = 1.00;

                /*
                 * Algunos eventos puntuales:
                 *
                 * - Fiestas Patrias
                 * - Día de la Madre
                 * - Navidad
                 * - Año Nuevo
                 */

                if (
                    ($fecha->month === 5 && $fecha->day >= 10 && $fecha->day <= 11) ||
                    ($fecha->month === 7 && $fecha->day >= 27 && $fecha->day <= 29) ||
                    ($fecha->month === 12 && $fecha->day >= 24 && $fecha->day <= 25) ||
                    ($fecha->month === 12 && $fecha->day === 31) ||
                    ($fecha->month === 1 && $fecha->day === 1)
                ) {
                    $factorEspecial = 1.15;
                }

                /*
                 |--------------------------------------------------------------------------
                 | DEMANDA DIARIA POR PRODUCTO
                 |--------------------------------------------------------------------------
                 */

                $demandaDelDia = [];

                foreach ($productos as $producto) {

                    $id = $producto->id;

                    $base = $demandaBase[$id] ?? 7;

                    /*
                    |--------------------------------------------------------------------------
                    | CONTINUIDAD TEMPORAL
                    |--------------------------------------------------------------------------
                    |
                    | Una parte de la demanda depende del día anterior.
                    | Esto genera una serie temporal más realista.
                    |
                    */

                    $factorMemoria = 0.35 * $demandaAnterior[$id];

                    $factorBase = 0.65 * $base;

                    $demandaEsperada =
                        (
                            $factorBase +
                            $factorMemoria
                        )
                        * $factorDiaActual
                        * $factorMesActual
                        * $factorTendencia
                        * $factorEspecial;

                    /*
                    |--------------------------------------------------------------------------
                    | RUIDO MODERADO
                    |--------------------------------------------------------------------------
                    |
                    | Antes teníamos ±30%.
                    | Ahora usamos aproximadamente ±10%.
                    |
                    */

                    $ruido = rand(90, 110) / 100;

                    $cantidad = max(
                        1,
                        (int) round($demandaEsperada * $ruido)
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | EVENTOS PUNTUALES CONTROLADOS
                    |--------------------------------------------------------------------------
                    |
                    | Permitimos algunos aumentos ocasionales,
                    | pero no picos completamente aleatorios.
                    |
                    */

                    $probabilidadEvento = rand(1, 100);

                    if ($probabilidadEvento <= 3) {
                        $cantidad = (int) round($cantidad * 1.20);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | LIMITE DE SEGURIDAD
                    |--------------------------------------------------------------------------
                    |
                    | Evitamos valores absurdamente altos provocados
                    | por la simulación.
                    |
                    */

                    $cantidad = min($cantidad, 40);

                    $demandaDelDia[$id] = $cantidad;

                    /*
                    | Guardamos para el siguiente día.
                    */
                    $demandaAnterior[$id] = $cantidad;
                }

                /*
                |--------------------------------------------------------------------------
                | CREACIÓN DE VENTAS
                |--------------------------------------------------------------------------
                |
                | En lugar de generar cantidades totalmente aleatorias,
                | distribuimos la demanda diaria de cada producto entre
                | varias operaciones.
                |
                */

                $cantidadVentas = max(
                    5,
                    (int) round(
                        10
                        * $factorDiaActual
                        * $factorMesActual
                        * $factorTendencia
                        * $factorEspecial
                    )
                );

                /*
                |--------------------------------------------------------------------------
                | DISTRIBUIR PRODUCTOS
                |--------------------------------------------------------------------------
                */

                $ventasPorProducto = [];

                foreach ($productos as $producto) {
                    $ventasPorProducto[$producto->id] =
                        $demandaDelDia[$producto->id];
                }

                /*
                |--------------------------------------------------------------------------
                | CREAR OPERACIONES
                |--------------------------------------------------------------------------
                */

                for ($i = 0; $i < $cantidadVentas; $i++) {

                    $fechaVenta = $fecha->copy()->setTime(
                        rand(7, 20),
                        rand(0, 59),
                        rand(0, 59)
                    );

                    $venta = Venta::create([
                        'usuario_id' => $usuario->id,
                        'fecha' => $fechaVenta,
                        'total' => 0,
                        'estado' => 'completada',
                    ]);

                    $total = 0;

                    /*
                    | Seleccionamos productos que todavía
                    | tengan demanda pendiente.
                    */
                    $productosDisponibles = $productos->filter(
                        function ($producto) use ($ventasPorProducto) {
                            return ($ventasPorProducto[$producto->id] ?? 0) > 0;
                        }
                    );

                    if ($productosDisponibles->isEmpty()) {
                        $venta->delete();
                        continue;
                    }

                    /*
                    | Cada operación puede tener de 1 a 2 productos.
                    */
                    $cantidadProductos = min(
                        rand(1, 2),
                        $productosDisponibles->count()
                    );

                    $productosVenta = $productosDisponibles
                        ->random($cantidadProductos);

                    foreach ($productosVenta as $producto) {

                        $pendiente =
                            $ventasPorProducto[$producto->id];

                        if ($pendiente <= 0) {
                            continue;
                        }

                        /*
                        | Normalmente vendemos 1 unidad por detalle.
                        | Ocasionalmente 2.
                        */
                        $cantidad = ($pendiente >= 2 && rand(1, 100) <= 20)
                            ? 2
                            : 1;

                        $cantidad = min(
                            $cantidad,
                            $pendiente
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

                        $ventasPorProducto[$producto->id] -= $cantidad;

                        $total += $subtotal;
                    }

                    /*
                    | Si la venta no tiene productos, eliminarla.
                    */
                    if ($total <= 0) {
                        $venta->delete();
                        continue;
                    }

                    $venta->update([
                        'total' => $total,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | SEGURIDAD
                |--------------------------------------------------------------------------
                |
                | Si por alguna razón quedó demanda pendiente,
                | creamos operaciones adicionales para completar
                | el día sin perder unidades.
                |
                */

                foreach ($productos as $producto) {

                    $pendiente =
                        $ventasPorProducto[$producto->id] ?? 0;

                    while ($pendiente > 0) {

                        $cantidad = min(
                            $pendiente,
                            rand(1, 2)
                        );

                        $fechaVenta = $fecha->copy()->setTime(
                            rand(7, 20),
                            rand(0, 59),
                            rand(0, 59)
                        );

                        $venta = Venta::create([
                            'usuario_id' => $usuario->id,
                            'fecha' => $fechaVenta,
                            'total' => 0,
                            'estado' => 'completada',
                        ]);

                        $precio = $producto->precio;

                        $subtotal = $cantidad * $precio;

                        DetalleVenta::create([
                            'venta_id' => $venta->id,
                            'producto_id' => $producto->id,
                            'cantidad' => $cantidad,
                            'precio_unitario' => $precio,
                            'subtotal' => $subtotal,
                        ]);

                        $venta->update([
                            'total' => $subtotal,
                        ]);

                        $pendiente -= $cantidad;
                    }
                }
            }
        });

        $this->command->info(
            'Histórico de ventas simuladas generado correctamente.'
        );
    }
}