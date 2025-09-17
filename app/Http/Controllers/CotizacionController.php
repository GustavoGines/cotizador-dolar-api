<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB; // 👈 IMPORTANTE
use App\Models\Cotizacion;
use Carbon\Carbon;

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

        // ✅ Guardar histórico automáticamente
        Cotizacion::create([
            'tipo'       => $tipo,
            'tipo_valor' => 'venta',
            'valor'      => $cotizacion,
            'fecha'      => Carbon::now(),
        ]);

        // ✅ Respuesta unificada
        return response()->json([
            'tipo'       => $tipo,
            'valor'      => (float) $valor,
            'cotizacion' => (float) $cotizacion,
            'resultado'  => round($resultado, 2)
        ]);
    }

    public function promedioMensual(Request $request)
    {
        $tipo = $request->input('tipo', 'blue');
        $tipoValor = $request->input('tipo_valor', 'venta');
        $anio = $request->input('anio');
        $mes = $request->input('mes');
    
        $query = DB::table('cotizaciones')
            ->where('tipo', $tipo)
            ->where('tipo_valor', $tipoValor);
    
        if ($anio && $mes) {
            // ✅ Promedio de un mes en particular
            $promedio = $query->whereYear('fecha', $anio)
                              ->whereMonth('fecha', $mes)
                              ->avg('valor');
    
            return response()->json([
                'anio'     => (int) $anio,
                'mes'      => (int) $mes,
                'promedio' => round($promedio, 2),
            ]);
        } else {
            // ✅ Promedio agrupado de todos los meses
            $resultados = $query->selectRaw('YEAR(fecha) as anio, MONTH(fecha) as mes, AVG(valor) as promedio')
                ->groupBy(DB::raw('YEAR(fecha), MONTH(fecha)'))
                ->orderByDesc('anio')
                ->orderByDesc('mes')
                ->get();
    
            return response()->json($resultados);
        }
    }

}
