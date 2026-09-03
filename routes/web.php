<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PrediccionController;
use App\Http\Controllers\AnalisisController;
use App\Http\Controllers\AlertasController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\VentaController;

/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

Route::get('/login', [LoginController::class, 'showLogin'])
    ->name('login');

Route::post('/login', [LoginController::class, 'login'])
    ->name('login.store');

Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout');


/*
|--------------------------------------------------------------------------
| PANEL ADMINISTRATIVO
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get('/', [DashboardController::class, 'index'])
        ->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | PRODUCTOS
    |--------------------------------------------------------------------------
    */

    Route::get('/productos', [ProductoController::class, 'index'])
        ->name('productos.index');

    Route::get('/productos/crear', [ProductoController::class, 'create'])
        ->name('productos.create');

    Route::post('/productos', [ProductoController::class, 'store'])
        ->name('productos.store');

    Route::get('/productos/{producto}/editar', [
        ProductoController::class,
        'edit'
    ])->name('productos.edit');

    Route::put('/productos/{producto}', [
        ProductoController::class,
        'update'
    ])->name('productos.update');

    Route::patch('/productos/{producto}/toggle', [
        ProductoController::class,
        'toggle'
    ])->name('productos.toggle');


    /*
    |--------------------------------------------------------------------------
    | CATEGORÍAS
    |--------------------------------------------------------------------------
    */

    Route::get('/categorias', [CategoriaController::class, 'index'])
        ->name('categorias.index');

    Route::get('/categorias/crear', [CategoriaController::class, 'create'])
        ->name('categorias.create');

    Route::post('/categorias', [CategoriaController::class, 'store'])
        ->name('categorias.store');

    Route::get('/categorias/{categoria}/editar', [
        CategoriaController::class,
        'edit'
    ])->name('categorias.edit');

    Route::put('/categorias/{categoria}', [
        CategoriaController::class,
        'update'
    ])->name('categorias.update');

    Route::patch('/categorias/{categoria}/toggle', [
        CategoriaController::class,
        'toggle'
    ])->name('categorias.toggle');


    /*
    |--------------------------------------------------------------------------
    | INVENTARIO
    |--------------------------------------------------------------------------
    */

    Route::get('/inventario', [
        InventarioController::class,
        'index'
    ])->name('inventario.index');

    Route::post('/inventario/movimiento', [
        InventarioController::class,
        'storeMovimiento'
    ])->name('inventario.movimiento.store');

    Route::get('/ventas', [VentaController::class, 'index'])
    ->name('ventas.index');
    /*
    |--------------------------------------------------------------------------
    | PREDICCIÓN MENSUAL
    |--------------------------------------------------------------------------
    */

    Route::get('/prediccion/mensual', [
        PrediccionController::class,
        'mensual'
    ])->name('prediccion.mensual');


    /*
    |--------------------------------------------------------------------------
    | ANÁLISIS
    |--------------------------------------------------------------------------
    */

    Route::get('/analisis', [
        AnalisisController::class,
        'index'
    ])->name('analisis.index');


    /*
    |--------------------------------------------------------------------------
    | ALERTAS
    |--------------------------------------------------------------------------
    */

    Route::get('/alertas', [
        AlertasController::class,
        'index'
    ])->name('alertas.index');


    /*
    |--------------------------------------------------------------------------
    | REPORTES
    |--------------------------------------------------------------------------
    */

    Route::get('/reportes', [
        ReportesController::class,
        'index'
    ])->name('reportes.index');

    Route::get('/reportes/generar', [
        ReportesController::class,
        'generar'
    ])->name('reportes.generar');

    Route::get('/reportes/exportar-excel', [
        ReportesController::class,
        'exportarExcel'
    ])->name('reportes.exportar.excel');


    /*
    |--------------------------------------------------------------------------
    | CONFIGURACIÓN
    |--------------------------------------------------------------------------
    */

    Route::get('/configuracion', function () {
        return view('configuracion.index');
    })->name('configuracion.index');

});