<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

/*
|--------------------------------------------------------------------------
| Arrancar Laravel
|--------------------------------------------------------------------------
*/

$app->make(Kernel::class)->bootstrap();

/*
|--------------------------------------------------------------------------
| Ejecutar servicio
|--------------------------------------------------------------------------
*/

$service = $app->make(
    \App\Services\DemandaService::class
);

try {

    $resultado = $service->obtenerPrediccionesMensuales(
        2026,
        9
    );

    echo json_encode(
        $resultado,
        JSON_PRETTY_PRINT |
        JSON_UNESCAPED_UNICODE
    );

} catch (\Throwable $e) {

    echo "ERROR:\n\n";

    echo $e->getMessage();

    echo "\n\nARCHIVO:\n";

    echo $e->getFile();

    echo "\n\nLINEA:\n";

    echo $e->getLine();

    echo "\n\nTRACE:\n";

    echo $e->getTraceAsString();
}