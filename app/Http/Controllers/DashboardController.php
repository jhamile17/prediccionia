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
        $resumenReposicion =
            $this->demandaService->obtenerResumenReposicion();

        $dashboard = [
            'resumenReposicion' => $resumenReposicion,
        ];

        return view(
            'dashboard.index',
            compact('dashboard')
        );
    }
}