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
        $valor = $request->query('valor');
        $tipo  = $request->query('tipo', 'oficial');

        if ($valor === null || !is_numeric($valor)) {
            return response()->json([
                'error' => 'Debe enviar un valor numérico en dólares.'
            ], 400);
        }

        try {
            $data = $this->svc->convertir((float) $valor, $tipo);
            return response()->json($data);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function promedioMensual(Request $request)
    {
        $tipo      = $request->input('tipo', 'blue');
        $tipoValor = $request->input('tipo_valor', 'venta');
        $anio      = $request->input('anio');
        $mes       = $request->input('mes');

        try {
            // El service ya devuelve con EXTRACT() para PostgreSQL
            $resultados = $this->svc->promedios($tipo, $tipoValor, $anio, $mes);

            return response()->json($resultados);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
