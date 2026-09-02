<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use App\Exports\InventarioExport;
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

        /*
         * Por ahora tomamos los productos registrados como
         * productos disponibles para análisis predictivo.
         *
         * No ejecutamos todavía el modelo Python aquí.
         */
        $totalPredicciones = Producto::where('activo', true)->count();

        /*
         * Todavía no tenemos una tabla de historial de reportes.
         * Por eso mostramos "Sin generar".
         */
        $ultimoReporte = 'Sin generar';

        /*
        |--------------------------------------------------------------------------
        | MENSAJE DE GENERACIÓN
        |--------------------------------------------------------------------------
        */

        $mensaje = null;

        if ($request->filled('tipo') && $request->filled('periodo')) {

            $tipo = $request->input('tipo');
            $periodo = $request->input('periodo');

            if (
                array_key_exists($tipo, $tiposReportes) &&
                in_array($periodo, [7, 30, 90, 365])
            ) {
                $mensaje = 'Reporte seleccionado correctamente. La generación del documento se conectará en el siguiente paso.';
            }
        }

        return view('reportes.index', compact(
            'tiposReportes',
            'totalReportes',
            'totalProductos',
            'totalPredicciones',
            'ultimoReporte',
            'mensaje'
        ));
    }

    /**
     * Procesar la solicitud de generación de reporte.
     *
     * Por ahora valida la selección y devuelve a la vista.
     * La exportación a Excel/PDF la implementaremos después.
     */
    public function generar(Request $request)
    {
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

        /*
        |--------------------------------------------------------------------------
        | REPORTE DE INVENTARIO
        |--------------------------------------------------------------------------
        */

        if ($request->tipo === 'inventario') {

            $nombreArchivo = 'reporte_inventario_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            return Excel::download(
                new InventarioExport(),
                $nombreArchivo
            );
        }

        /*
        |--------------------------------------------------------------------------
        | OTROS REPORTES
        |--------------------------------------------------------------------------
        |
        | Los implementaremos progresivamente.
        |
        */

        return redirect()
            ->route('reportes.index', [
                'tipo' => $request->tipo,
                'periodo' => $request->periodo,
            ])
            ->with(
                'success',
                'El reporte seleccionado todavía se encuentra en implementación.'
            );
    }
}