<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;

class AlertasController extends Controller
{
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | FILTROS
        |--------------------------------------------------------------------------
        */

        $tipo = $request->input('tipo');
        $estado = $request->input('estado');


        /*
        |--------------------------------------------------------------------------
        | PRODUCTOS
        |--------------------------------------------------------------------------
        */

        $productos = Producto::where('activo', true)
            ->orderBy('nombre')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | GENERAR ALERTAS
        |--------------------------------------------------------------------------
        */

        $alertas = collect();


        foreach ($productos as $producto) {

            $stock = (int) $producto->stock;
            $stockMinimo = (int) $producto->stock_minimo;
            $stockSeguridad = (int) $producto->stock_seguridad;


            /*
            |--------------------------------------------------------------------------
            | ALERTA CRÍTICA
            |--------------------------------------------------------------------------
            */

            if ($stock <= $stockMinimo) {

                $alertas->push([
                    'tipo' => 'critica',
                    'estado' => 'pendiente',
                    'titulo' => 'Stock crítico',
                    'producto' => $producto->nombre,
                    'descripcion' =>
                        "El producto {$producto->nombre} tiene {$stock} unidades " .
                        "disponibles y su stock mínimo es de {$stockMinimo} unidades.",
                    'icono' => 'bi-exclamation-triangle-fill',
                    'origen' => 'Inventario',
                    'accion' => 'Requiere atención',
                    'stock' => $stock,
                    'stock_minimo' => $stockMinimo,
                ]);

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | ALERTA DE ADVERTENCIA
            |--------------------------------------------------------------------------
            */

            $limiteAdvertencia = $stockMinimo + $stockSeguridad;

            if (
                $stockSeguridad > 0 &&
                $stock <= $limiteAdvertencia
            ) {

                $alertas->push([
                    'tipo' => 'advertencia',
                    'estado' => 'pendiente',
                    'titulo' => 'Reposición recomendada',
                    'producto' => $producto->nombre,
                    'descripcion' =>
                        "El producto {$producto->nombre} tiene {$stock} unidades " .
                        "y se encuentra dentro del nivel de reposición recomendado.",
                    'icono' => 'bi-exclamation-circle-fill',
                    'origen' => 'Inventario',
                    'accion' => 'Requiere revisión',
                    'stock' => $stock,
                    'stock_minimo' => $stockMinimo,
                ]);
            }
        }


        /*
        |--------------------------------------------------------------------------
        | ALERTA INFORMATIVA
        |--------------------------------------------------------------------------
        */

        $alertas->push([
            'tipo' => 'informativa',
            'estado' => 'atendida',
            'titulo' => 'Monitoreo de inventario activo',
            'producto' => null,
            'descripcion' =>
                'El sistema está monitoreando automáticamente el stock ' .
                'de los productos activos.',
            'icono' => 'bi-info-circle-fill',
            'origen' => 'Sistema',
            'accion' => 'Información',
            'stock' => null,
            'stock_minimo' => null,
        ]);


        /*
        |--------------------------------------------------------------------------
        | RESUMEN
        |--------------------------------------------------------------------------
        */

        $alertasCriticas = $alertas
            ->where('tipo', 'critica')
            ->count();

        $alertasPendientes = $alertas
            ->where('estado', 'pendiente')
            ->count();

        $alertasInformativas = $alertas
            ->where('tipo', 'informativa')
            ->count();

        $alertasAtendidas = $alertas
            ->where('estado', 'atendida')
            ->count();


        /*
        |--------------------------------------------------------------------------
        | APLICAR FILTROS
        |--------------------------------------------------------------------------
        */

        $alertasFiltradas = $alertas;

        if ($tipo) {
            $alertasFiltradas = $alertasFiltradas
                ->where('tipo', $tipo);
        }

        if ($estado) {
            $alertasFiltradas = $alertasFiltradas
                ->where('estado', $estado);
        }


        /*
        |--------------------------------------------------------------------------
        | RETORNAR VISTA
        |--------------------------------------------------------------------------
        */

        return view('alertas.index', compact(
            'alertasFiltradas',
            'alertasCriticas',
            'alertasPendientes',
            'alertasInformativas',
            'alertasAtendidas',
            'tipo',
            'estado'
        ));
    }
}