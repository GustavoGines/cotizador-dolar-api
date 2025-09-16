<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;

Route::get('/diag', function () {
  return [
    'ok' => true,
    'routes_prefijo' => 'api', // recordatorio
    'session' => config('session.driver'),
    'db' => config('database.default'),
  ];
});

Route::get('/', fn() => 'cotizador-dolar-api running ✅');

Route::get('/', function () {
    return view('convertidor');
});

