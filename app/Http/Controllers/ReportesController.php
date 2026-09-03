<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\InventarioExport;
use App\Exports\ReporteExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportesController extends Controller
{
    /**
     * Mostrar página principal de reportes.
     */
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | TIPOS DE REPORTES DISPONIBLES
        |--------------------------------------------------------------------------
        */

        $tiposReportes = [
            'inventario' => 'Reporte de inventario',
            'productos' => 'Reporte de productos',
            'predicciones' => 'Reporte de predicciones',
            'ventas' => 'Reporte de ventas',
            'demanda' => 'Reporte de demanda',
            'alertas' => 'Reporte de alertas',
        ];


        /*
        |--------------------------------------------------------------------------
        | RESUMEN
        |--------------------------------------------------------------------------
        */

        $totalReportes = count($tiposReportes);

        $totalProductos = Producto::count();

        $totalPredicciones = Producto::where('activo', true)->count();

        $ultimoReporte = session(
            'ultimo_reporte',
            'Sin generar'
        );


        /*
        |--------------------------------------------------------------------------
        | VALORES DEL FORMULARIO
        |--------------------------------------------------------------------------
        */

        $tipo = $request->input('tipo');

        $periodo = $request->input('periodo');


        /*
        |--------------------------------------------------------------------------
        | MENSAJE
        |--------------------------------------------------------------------------
        */

        $mensaje = session('success');


        /*
        |--------------------------------------------------------------------------
        | VISTA
        |--------------------------------------------------------------------------
        */

        return view(
            'reportes.index',
            compact(
                'tiposReportes',
                'totalReportes',
                'totalProductos',
                'totalPredicciones',
                'ultimoReporte',
                'mensaje',
                'tipo',
                'periodo'
            )
        );
    }


    /**
     * Generar reporte y mostrar sus datos.
     */
    public function generar(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | VALIDACIÓN
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'tipo' => [
                'required',
                'in:inventario,productos,predicciones,ventas,demanda,alertas',
            ],

            'periodo' => [
                'required',
                'in:7,30,90,365',
            ],
        ]);


        $tipo = $request->input('tipo');

        $periodo = (int) $request->input('periodo');


        /*
        |--------------------------------------------------------------------------
        | FECHAS
        |--------------------------------------------------------------------------
        */

        $fechaFin = Carbon::today();

        $fechaInicio = $fechaFin
            ->copy()
            ->subDays($periodo - 1);


        /*
        |--------------------------------------------------------------------------
        | OBTENER DATOS
        |--------------------------------------------------------------------------
        */

        $datos = $this->obtenerDatosReporte(
            $tipo,
            $fechaInicio,
            $fechaFin
        );


        /*
        |--------------------------------------------------------------------------
        | CONFIGURACIÓN
        |--------------------------------------------------------------------------
        */

        $configuracion = $this->configuracionReporte($tipo);


        $titulo = $configuracion['titulo'];

        $descripcion = $configuracion['descripcion'];


        /*
        |--------------------------------------------------------------------------
        | TOTAL DE REGISTROS
        |--------------------------------------------------------------------------
        */

        $totalRegistros = $datos->count();


        /*
        |--------------------------------------------------------------------------
        | GUARDAR ÚLTIMO REPORTE
        |--------------------------------------------------------------------------
        */

        session([
            'ultimo_reporte' =>
                now()->format('d/m/Y H:i'),
        ]);


        /*
        |--------------------------------------------------------------------------
        | MOSTRAR RESULTADO
        |--------------------------------------------------------------------------
        */

        return view(
            'reportes.resultado',
            compact(
                'titulo',
                'descripcion',
                'tipo',
                'periodo',
                'fechaInicio',
                'fechaFin',
                'datos',
                'totalRegistros'
            )
        );
    }


    /**
     * Descargar reporte en Excel.
     */
    public function exportarExcel(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | VALIDACIÓN
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'tipo' => [
                'required',
                'in:inventario,productos,predicciones,ventas,demanda,alertas',
            ],

            'periodo' => [
                'required',
                'in:7,30,90,365',
            ],
        ]);


        $tipo = $request->input('tipo');

        $periodo = (int) $request->input('periodo');


        /*
        |--------------------------------------------------------------------------
        | INVENTARIO
        |--------------------------------------------------------------------------
        |
        | Conservamos tu exportador existente.
        |
        */

        if ($tipo === 'inventario') {

            $nombreArchivo =
                'reporte_inventario_' .
                now()->format('Y-m-d_H-i-s') .
                '.xlsx';

            return Excel::download(
                new InventarioExport(),
                $nombreArchivo
            );
        }


        /*
        |--------------------------------------------------------------------------
        | FECHAS
        |--------------------------------------------------------------------------
        */

        $fechaFin = Carbon::today();

        $fechaInicio = $fechaFin
            ->copy()
            ->subDays($periodo - 1);


        /*
        |--------------------------------------------------------------------------
        | OBTENER DATOS
        |--------------------------------------------------------------------------
        */

        $datos = $this->obtenerDatosReporte(
            $tipo,
            $fechaInicio,
            $fechaFin
        );


        /*
        |--------------------------------------------------------------------------
        | NOMBRE DEL ARCHIVO
        |--------------------------------------------------------------------------
        */

        $nombres = [

            'productos' => 'productos',

            'predicciones' => 'predicciones',

            'ventas' => 'ventas',

            'demanda' => 'demanda',

            'alertas' => 'alertas',
        ];


        $nombre =
            $nombres[$tipo] ?? 'reporte';


        $nombreArchivo =
            'reporte_' .
            $nombre .
            '_' .
            now()->format('Y-m-d_H-i-s') .
            '.xlsx';


        /*
        |--------------------------------------------------------------------------
        | DESCARGAR
        |--------------------------------------------------------------------------
        */

        return Excel::download(
            new ReporteExport(
                $datos,
                $tipo
            ),
            $nombreArchivo
        );
    }


    /**
     * Obtener datos según el tipo de reporte.
     */
    private function obtenerDatosReporte(
        string $tipo,
        Carbon $fechaInicio,
        Carbon $fechaFin
    ) {

        /*
        |--------------------------------------------------------------------------
        | PRODUCTOS
        |--------------------------------------------------------------------------
        */

        if ($tipo === 'productos') {

            return Producto::query()
                ->leftJoin(
                    'categorias',
                    'categorias.id',
                    '=',
                    'productos.categoria_id'
                )
                ->select(
                    'productos.id',
                    'productos.nombre',
                    'categorias.nombre as categoria',
                    'productos.precio',
                    'productos.costo',
                    'productos.stock',
                    'productos.activo'
                )
                ->orderBy('productos.nombre')
                ->get();
        }


        /*
        |--------------------------------------------------------------------------
        | INVENTARIO
        |--------------------------------------------------------------------------
        */

        if ($tipo === 'inventario') {

            return Producto::query()
                ->leftJoin(
                    'categorias',
                    'categorias.id',
                    '=',
                    'productos.categoria_id'
                )
                ->select(
                    'productos.id',
                    'productos.nombre',
                    'categorias.nombre as categoria',
                    'productos.stock',
                    'productos.stock_minimo',
                    'productos.stock_seguridad'
                )
                ->where(
                    'productos.activo',
                    true
                )
                ->orderBy('productos.nombre')
                ->get();
        }


        /*
        |--------------------------------------------------------------------------
        | VENTAS
        |--------------------------------------------------------------------------
        */

        if ($tipo === 'ventas') {

            return DB::table('detalle_ventas')
                ->join(
                    'ventas',
                    'ventas.id',
                    '=',
                    'detalle_ventas.venta_id'
                )
                ->join(
                    'productos',
                    'productos.id',
                    '=',
                    'detalle_ventas.producto_id'
                )
                ->where(
                    'ventas.estado',
                    'completada'
                )
                ->whereBetween(
                    DB::raw('DATE(ventas.fecha)'),
                    [
                        $fechaInicio->toDateString(),
                        $fechaFin->toDateString(),
                    ]
                )
                ->select(
                    'productos.nombre',
                    DB::raw(
                        'SUM(detalle_ventas.cantidad) as cantidad'
                    )
                )
                ->groupBy(
                    'productos.id',
                    'productos.nombre'
                )
                ->orderByDesc('cantidad')
                ->get();
        }


        /*
        |--------------------------------------------------------------------------
        | DEMANDA
        |--------------------------------------------------------------------------
        */

        if ($tipo === 'demanda') {

            return DB::table('detalle_ventas')
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
                    DB::raw('DATE(ventas.fecha)'),
                    [
                        $fechaInicio->toDateString(),
                        $fechaFin->toDateString(),
                    ]
                )
                ->select(
                    DB::raw(
                        'DATE(ventas.fecha) as fecha'
                    ),
                    DB::raw(
                        'SUM(detalle_ventas.cantidad) as cantidad'
                    )
                )
                ->groupBy(
                    DB::raw('DATE(ventas.fecha)')
                )
                ->orderBy('fecha')
                ->get();
        }


        /*
        |--------------------------------------------------------------------------
        | PREDICCIONES
        |--------------------------------------------------------------------------
        */

        if ($tipo === 'predicciones') {

            return Producto::query()
                ->select(
                    'id',
                    'nombre',
                    'stock',
                    'stock_minimo',
                    'stock_seguridad'
                )
                ->where(
                    'activo',
                    true
                )
                ->orderBy('nombre')
                ->get();
        }


        /*
        |--------------------------------------------------------------------------
        | ALERTAS
        |--------------------------------------------------------------------------
        */

        if ($tipo === 'alertas') {

            $productos = Producto::query()
                ->select(
                    'id',
                    'nombre',
                    'stock',
                    'stock_minimo'
                )
                ->where(
                    'activo',
                    true
                )
                ->where(
                    'stock',
                    '<=',
                    DB::raw('stock_minimo')
                )
                ->orderBy('stock')
                ->get();


            return $productos->map(
                function ($producto) {

                    if ($producto->stock <= 0) {

                        return (object) [

                            'tipo' => 'critica',

                            'producto' =>
                                $producto->nombre,

                            'descripcion' =>
                                'El producto no tiene unidades disponibles.',

                            'estado' =>
                                'pendiente',
                        ];
                    }


                    return (object) [

                        'tipo' => 'advertencia',

                        'producto' =>
                            $producto->nombre,

                        'descripcion' =>
                            'El stock se encuentra en el nivel mínimo o por debajo.',

                        'estado' =>
                            'pendiente',
                    ];
                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SI NO EXISTE EL TIPO
        |--------------------------------------------------------------------------
        */

        return collect();
    }


    /**
     * Configuración visual de cada reporte.
     */
    private function configuracionReporte(string $tipo): array
    {
        $configuracion = [

            'productos' => [

                'titulo' =>
                    'Reporte de productos',

                'descripcion' =>
                    'Información general de los productos registrados en el sistema.',
            ],

            'inventario' => [

                'titulo' =>
                    'Reporte de inventario',

                'descripcion' =>
                    'Estado actual del inventario y niveles disponibles.',
            ],

            'ventas' => [

                'titulo' =>
                    'Reporte de ventas',

                'descripcion' =>
                    'Resumen de las unidades vendidas durante el período seleccionado.',
            ],

            'demanda' => [

                'titulo' =>
                    'Reporte de demanda',

                'descripcion' =>
                    'Comportamiento de la demanda registrada durante el período seleccionado.',
            ],

            'predicciones' => [

                'titulo' =>
                    'Reporte de predicciones',

                'descripcion' =>
                    'Productos activos disponibles para análisis predictivo.',
            ],

            'alertas' => [

                'titulo' =>
                    'Reporte de alertas',

                'descripcion' =>
                    'Situaciones de stock que requieren atención.',
            ],
        ];


        return $configuracion[$tipo];
    }
}