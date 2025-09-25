<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Cotizacion;
use Carbon\Carbon;

class CotizacionService
{
    /**
     * Conversión centralizada: usa la cotización actual y aplica la dirección.
     */
    public function convertir(
        float $valor,
        string $tipo = 'oficial',
        string $direccion = 'usd_a_pesos'
    ): array {
        // Normalizamos dirección por seguridad
        $direccion = in_array($direccion, ['usd_a_pesos', 'pesos_a_usd'])
            ? $direccion
            : 'usd_a_pesos';

        $cotizacion = $this->cotizacion($tipo);

        $resultado = $direccion === 'usd_a_pesos'
            ? round($valor * $cotizacion, 2)
            : round($valor / $cotizacion, 2);

        return [
            'tipo'       => $tipo,
            'direccion'  => $direccion,
            'valor'      => (float) $valor,
            'cotizacion' => (float) $cotizacion,
            'resultado'  => $resultado,
        ];
    }

    /**
     * Obtiene la cotización actual desde la API y persiste valor del día.
     * Si falla la API, intenta devolver el último valor guardado.
     */
    public function cotizacion(string $tipo = 'oficial'): float
    {
        $baseUrl = rtrim(config('services.dolarapi.url'), '/');
        $url = "{$baseUrl}/{$tipo}";

        $cotizacion = null;

        // 1) Intento de API
        try {
            $resp = Http::withOptions([
                    'verify' => app()->environment('local') ? false : true,
                ])
                ->timeout(8)
                ->retry(2, 200)
                ->get($url);

            if ($resp->ok()) {
                $data = $resp->json();
                $cotizacion = $data['venta'] ?? $data['promedio'] ?? null;
            }
        } catch (\Throwable $e) {
            // Silenciamos aquí; probamos fallback abajo
        }

        // 2) Si la API no devolvió valor, usamos el último guardado
        if (!$cotizacion) {
            $ultimo = Cotizacion::where('tipo', $tipo)
                ->where('tipo_valor', 'venta')
                ->orderByDesc('fecha')
                ->value('valor');

            if (!$ultimo) {
                throw new \RuntimeException('Cotización no disponible.');
            }

            return (float) $ultimo;
        }

        // 3) Persistimos valor del día (idempotente por fecha)
        Cotizacion::updateOrCreate(
            [
                'tipo'       => $tipo,
                'tipo_valor' => 'venta',
                'fecha'      => Carbon::today(config('app.timezone')),
            ],
            ['valor' => (float) $cotizacion]
        );

        return (float) $cotizacion;
    }

    /**
     * Promedios por año/mes (filtrables).
     */
    public function promedios(
        string $tipo = 'oficial',
        string $tipoValor = 'venta',
        ?int $anio = null,
        ?int $mes = null
    ) {
        $q = DB::table('cotizaciones')
            ->where('tipo', $tipo)
            ->where('tipo_valor', $tipoValor);

        if ($anio) {
            $q->whereRaw('EXTRACT(YEAR FROM fecha) = ?', [$anio]);
        }
        if ($mes) {
            $q->whereRaw('EXTRACT(MONTH FROM fecha) = ?', [$mes]);
        }

        return $q->selectRaw(
                'EXTRACT(YEAR FROM fecha) as anio,
                 EXTRACT(MONTH FROM fecha) as mes,
                 AVG(valor) as promedio'
            )
            ->groupByRaw('EXTRACT(YEAR FROM fecha), EXTRACT(MONTH FROM fecha)')
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->get();
    }
}
