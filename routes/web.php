<?php

use Illuminate\Support\Facades\Route;

Route::get('/diag', function () {
  return response()->json([
    'ok' => true,
    'routes_prefijo' => 'api', // recordatorio
    'session' => config('session.driver'),
    'db' => config('database.default'),
  ]);
});

Route::get('/', function () {
    return view('convertidor');
});

