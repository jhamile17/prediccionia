<?php

namespace App\Http\Controllers;

use App\Services\DemandaService;

class DashboardController extends Controller
{
    public function index(DemandaService $demandaService)
    {
        $prediccionesMensuales =
            $demandaService->obtenerPrediccionesMensuales(
                2026,
                9
            );

        dd($prediccionesMensuales);

        return view(
            'dashboard',
            compact('prediccionesMensuales')
        );
    }
}