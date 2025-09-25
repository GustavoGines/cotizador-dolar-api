<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CotizacionService;

class CotizacionController extends Controller
{
    protected CotizacionService $svc;

    public function __construct(CotizacionService $svc)
    {
        $this->svc = $svc;
    }

    public function convertir(Request $request)
    {
        $valor     = $request->query('valor');
        $tipo      = $request->query('tipo', 'oficial');
        $direccion = $request->query('direccion', 'usd_a_pesos');
    
        if ($valor === null || !is_numeric($valor)) {
            return response()->json([
                'error' => 'Debe enviar un valor numérico válido.'
            ], 400);
        }
    
        try {
            $cotizacion = $this->svc->cotizacion($tipo);
    
            $resultado = $direccion === 'usd_a_pesos'
                ? round($valor * $cotizacion, 2)
                : round($valor / $cotizacion, 2);
    
            return response()->json([
                'tipo'       => $tipo,
                'direccion'  => $direccion,
                'valor'      => (float) $valor,
                'cotizacion' => $cotizacion,
                'resultado'  => $resultado,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function promedioMensual(Request $request)
    {
        $tipo      = $request->query('tipo', 'oficial');
        $tipoValor = $request->query('tipo_valor', 'venta');
        $anio      = $request->query('anio');
        $mes       = $request->query('mes');
    
        try {
            $rows = $this->svc->promedios($tipo, $tipoValor, $anio, $mes);
    
            // Normalizamos formato
            $resultados = $rows->map(fn($r) => [
                'anio'     => (int) $r->anio,
                'mes'      => (int) $r->mes,
                'promedio' => round((float) $r->promedio, 2),
            ])->toArray();
    
            return response()->json([
                'tipo'      => $tipo,
                'tipo_valor'=> $tipoValor,
                'anio'      => $anio,
                'mes'       => $mes,
                'resultados'=> $resultados,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
