<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrediccionController;
Route::get('/prediccion/reposicion', [PrediccionController::class, 'reposicion'])
    ->name('prediccion.reposicion');
Route::post('/prediccion/predecir', [PrediccionController::class, 'predecir'])
    ->name('prediccion.predecir');
