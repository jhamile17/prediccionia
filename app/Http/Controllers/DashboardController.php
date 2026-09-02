<?php

namespace App\Http\Controllers;

use App\Services\DemandaService;

class DashboardController extends Controller
{
    public function __construct(
        private DemandaService $demandaService
    ) {
    }

    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | RESUMEN DE REPOSICIÓN
        |--------------------------------------------------------------------------
        | Obtenemos los datos reales generados por el sistema
        | de predicción.
        |--------------------------------------------------------------------------
        */

        $resumenReposicion =
            $this->demandaService->obtenerResumenReposicion();

        return view(
            'dashboard.index',
            compact('resumenReposicion')
        );
    }
}