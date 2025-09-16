<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CotizacionController extends Controller
{
    public function convertir(Request $request)
    {
        $valor = $request->query('valor');
        $tipo = $request->query('tipo', 'oficial'); // por defecto: oficial

        // ✅ Validar el valor
        if (!$valor || !is_numeric($valor)) {
            return response()->json([
                'error' => 'Debe enviar un valor numérico en dólares.'
            ], 400);
        }

        // ✅ Tipos válidos que soporta la API
        $tiposDolar = ['oficial', 'blue', 'bolsa', 'ccl', 'tarjeta', 'mayorista', 'cripto'];

        // ✅ Base URL desde .env / config
        $baseUrl = rtrim(config('services.dolarapi.url'), '/');

        // ✅ Construir la URL según tipo
        if (in_array($tipo, $tiposDolar)) {
            $url = "{$baseUrl}/{$tipo}";
        } else {
            $url = "{$baseUrl}/cotizaciones/{$tipo}";
        }

        // ✅ Llamar a la API externa (ignorando SSL en local)
        $response = Http::withOptions([
            'verify' => false,
        ])->get($url);

        if ($response->failed()) {
            return response()->json([
                'error' => 'No se pudo obtener la cotización.'
            ], 500);
        }

        $data = $response->json();

        // ✅ La API devuelve "venta" en dólares y "promedio" en otras monedas
        $cotizacion = $data['venta'] ?? $data['promedio'] ?? null;

        if (!$cotizacion) {
            return response()->json([
                'error' => 'Cotización no disponible.'
            ], 500);
        }

        // ✅ Calcular resultado en pesos
        $resultado = (float) $valor * (float) $cotizacion;

        // ✅ Respuesta unificada
        return response()->json([
            'tipo'       => $tipo,
            'valor'      => (float) $valor,
            'cotizacion' => (float) $cotizacion,
            'resultado'  => round($resultado, 2)
        ]);
    }
}
