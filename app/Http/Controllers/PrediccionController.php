<?php

namespace App\Http\Controllers;

use App\Services\DemandaService;
use App\Services\PredictionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PrediccionController extends Controller
{
    public function __construct(
        private DemandaService $demandaService,
        private PredictionService $predictionService
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Predicción diaria - API
    |--------------------------------------------------------------------------
    */

    public function predecir(Request $request)
    {
        $datos = $request->json()->all();

        if (empty($datos)) {
            $contenido = $request->getContent();

            $datos = json_decode($contenido, true);

            if (!is_array($datos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El cuerpo de la petición no contiene un JSON válido.',
                    'raw' => $contenido,
                ], 400);
            }
        }

        $validator = Validator::make($datos, [
            'producto_id' => [
                'required',
                'integer',
                'exists:productos,id',
            ],
            'fecha' => [
                'required',
                'date',
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos.',
                'errors' => $validator->errors(),
                'datos_recibidos' => $datos,
            ], 422);
        }

        $datosPrediccion = $this->demandaService->prepararDatos(
            $datos['producto_id'],
            $datos['fecha']
        );

        $resultado = $this->predictionService->predecir(
            $datosPrediccion
        );

        return response()->json([
            'success' => true,
            'datos' => $datosPrediccion,
            'resultado' => $resultado,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Predicción mensual - Vista web
    |--------------------------------------------------------------------------
    */

    public function mensual(Request $request)
    {
        $anio = $request->input('anio');
        $mes = $request->input('mes');

        $anio = $anio
            ? (int) $anio
            : now()->year;

        $mes = $mes
            ? (int) $mes
            : now()->month;

        /*
         * Validar mes y año.
         */
        if ($mes < 1 || $mes > 12) {
            $mes = now()->month;
        }

        if ($anio < 2020 || $anio > 2100) {
            $anio = now()->year;
        }

        /*
         * Obtener predicciones mensuales
         * usando el servicio existente.
         */
        $predicciones = $this->demandaService
            ->obtenerPrediccionesMensuales(
                $anio,
                $mes
            );

        /*
         * Nombre del mes para la interfaz.
         */
        $nombreMes = \Carbon\Carbon::create(
            $anio,
            $mes,
            1
        )->translatedFormat('F');

        return view('prediccion.mensual', compact(
            'predicciones',
            'anio',
            'mes',
            'nombreMes'
        ));
    }
}