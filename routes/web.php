<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;

Route::get('/', fn() => 'cotizador-dolar-api running ✅');

Route::get('/', function () {
    return view('convertidor');
});

