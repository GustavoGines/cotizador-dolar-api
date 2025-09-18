<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CotizacionController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Conversión
Route::get('/convertir', [CotizacionController::class, 'convertir'])
    ->name('api.convertir')
    ->middleware('throttle:60,1'); // opcional

// Promedios (ruta “canónica”)
Route::get('/promedio-mensual', [CotizacionController::class, 'promedioMensual'])
    ->name('api.promedio')
    ->middleware('throttle:60,1'); // opcional

// Alias compatible (tu ruta actual)
Route::get('/cotizaciones/promedio-mensual', [CotizacionController::class, 'promedioMensual'])
    ->name('api.promedio.alt');

// Ejemplos de uso:
// Conversión: http://127.0.0.1:8000/api/convertir?valor=150&tipo=blue
// Promedios (todo):  http://127.0.0.1:8000/api/cotizaciones/promedio-mensual?tipo=oficial
// Promedios (solo 2024): http://127.0.0.1:8000/api/cotizaciones/promedio-mensual?tipo=oficial&anio=2024
// Promedios (solo marzo 2024): http://127.0.0.1:8000/api/cotizaciones/promedio-mensual?tipo=oficial&anio=2024&mes=3