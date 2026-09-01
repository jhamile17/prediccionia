<?php

namespace App\Http\Controllers;

use App\Services\DemandaService;

class DashboardController extends Controller
{
    public function index(DemandaService $demandaService)
    {
        $resumenReposicion = $demandaService->obtenerResumenReposicion();

        return view('dashboard', compact('resumenReposicion'));
    }
}