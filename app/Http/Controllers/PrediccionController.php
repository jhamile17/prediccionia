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

    public function predecir(Request $request)
    {
        /*
         * =====================================================
         * OBTENER EL JSON DE FORMA SEGURA
         * =====================================================
         */

        $datos = $request->json()->all();

        /*
         * Si Laravel no pudo interpretar automáticamente
         * el JSON, lo interpretamos manualmente.
         */
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

        /*
         * =====================================================
         * VALIDACIÓN
         * =====================================================
         */

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

        /*
         * =====================================================
         * PREPARAR DATOS PARA EL MODELO
         * =====================================================
         */

        $datosPrediccion = $this->demandaService->prepararDatos(
            $datos['producto_id'],
            $datos['fecha']
        );

        /*
         * =====================================================
         * EJECUTAR PREDICCIÓN
         * =====================================================
         */

        $resultado = $this->predictionService->predecir(
            $datosPrediccion
        );

        /*
         * =====================================================
         * RESPUESTA
         * =====================================================
         */

        return response()->json([
            'success' => true,
            'datos' => $datosPrediccion,
            'resultado' => $resultado,
        ]);
    }
}