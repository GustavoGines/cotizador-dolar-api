<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CotizacionService;

class CotizacionController extends Controller
{
    protected CotizacionService $svc;

    /**
     * Mapa fijo de nombres de meses en español (evita depender de locale del servidor)
     */
    private const MESES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
        5 => 'mayo',  6 => 'junio',   7 => 'julio', 8 => 'agosto',
        9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    public function __construct(CotizacionService $svc)
    {
        $this->svc = $svc;
    }

    /**
     * GET /api/cotizaciones/convertir?valor=123.45&tipo=oficial&direccion=usd_a_pesos|pesos_a_usd
     */
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
            // Toda la lógica está en el Service (incluye dirección y fallback)
            $data = $this->svc->convertir((float) $valor, (string) $tipo, (string) $direccion);
            return response()->json($data);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/cotizaciones/promedio-mensual?tipo=oficial[&tipo_valor=venta][&anio=2025][&mes=9][&flat=1]
     *
     * Por defecto: devuelve AGRUPADO por año → meses con nombre.
     * Si pasás flat=1, devuelve el formato plano (anio, mes numérico, promedio).
     */
    public function promedioMensual(Request $request)
    {
        $tipo      = $request->query('tipo', 'oficial');
        $tipoValor = $request->query('tipo_valor', 'venta');
        $anio      = $request->query('anio'); // null => todos
        $mes       = $request->query('mes');  // null => todos
        $flatOut   = (bool) $request->query('flat', false);

        try {
            $rows = $this->svc->promedios($tipo, $tipoValor, $anio, $mes);

            // Normalizamos a lista plana tipada
            $flat = $rows->map(fn($r) => [
                'anio'     => (int) $r->anio,
                'mes'      => (int) $r->mes,
                'promedio' => round((float) $r->promedio, 2),
            ]);

            // Si se pide explícitamente plano, devolvemos el formato anterior
            if ($flatOut) {
                return response()->json([
                    'tipo'        => $tipo,
                    'tipo_valor'  => $tipoValor,
                    'anio'        => $anio,
                    'mes'         => $mes,
                    'resultados'  => $flat->toArray(),
                ]);
            }

            // AGRUPADO por año → meses, con nombre de mes
            $groupedByYear = $flat
                ->groupBy('anio')
                ->map(function ($items, $anioKey) {
                    $meses = $items
                        ->sortBy('mes') // 1..12
                        ->values()
                        ->map(function ($r) {
                            return [
                                'mes'      => self::MESES[$r['mes']] ?? (string) $r['mes'],
                                'promedio' => $r['promedio'],
                            ];
                        });

                    return [
                        'anio'  => (int) $anioKey,
                        'meses' => $meses,
                    ];
                })
                ->sortByDesc('anio') // años descendente
                ->values()
                ->toArray();

            return response()->json([
                'tipo'        => $tipo,
                'tipo_valor'  => $tipoValor,
                'anio'        => $anio,
                'mes'         => $mes,
                'resultados'  => $groupedByYear,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
