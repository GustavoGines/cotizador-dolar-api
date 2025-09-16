<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response('cotizador-dolar-api running ✅', 200));

Route::get('/', function () {
    return view('convertidor');
});

