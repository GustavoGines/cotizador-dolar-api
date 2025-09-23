<?php

return [
    // Versión publicada actualmente (tu Flutter está en 1.0.6)
    'latest'  => env('APP_LATEST_VERSION', '1.0.6'),

    // Versión mínima soportada (si el user tiene menos, forzá update)
    'minimum' => env('APP_MIN_VERSION', '1.0.5'),

    // URL del APK (podés dejarla vacía hasta subir la 1.0.6)
    'url'    => env('APP_UPDATE_URL', 'https://firebasestorage.googleapis.com/v0/b/cotizador-dolar-api.firebasestorage.app/o/releases%2Fapp-release-1.0.6.apk?alt=media&token=390f65a7-f6d2-42f6-b75a-ab3e03a8bf79'),

    // (opcional) notas de la versión para mostrar en Flutter
    'notes'   => env('APP_UPDATE_NOTES', 'Testing new version...'),
];
