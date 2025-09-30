<?php

use Illuminate\Support\Facades\Route;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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

Route::get('/qr/apk', function () {
    $url = config('app.apk_url')
        ?? 'https://github.com/GustavoGines/cotizador-dolar-api/releases/latest/download/app-release.apk';

    $svg = QrCode::format('svg')->size(280)->margin(1)->generate($url);

    return response($svg, 200, [
        'Content-Type'  => 'image/svg+xml; charset=UTF-8',
        'Cache-Control' => 'public, max-age=604800, immutable',
    ]);
})->name('qr.apk');
