<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Models\Cotizacion;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CotizacionService
{
    public function convertir(float $valor, string $tipo = 'oficial'): array
    {
        // 👉 Base URL tomada de config/services.php (que a su vez usa DOLAR_API_BASE_URL)
        $baseUrl = rtrim(config('services.dolarapi.url'), '/');
        $url = "{$baseUrl}/{$tipo}";

        $resp = Http::withOptions([
                'verify' => app()->environment('local') ? false : true
            ])
            ->timeout(8)
            ->retry(2, 200)
            ->get($url);

        if ($resp->failed()) {
            throw new \RuntimeException('No se pudo obtener la cotización.');
        }

        $data = $resp->json();
        $cotizacion = $data['venta'] ?? $data['promedio'] ?? null;

        if (!$cotizacion) {
            throw new \RuntimeException('Cotización no disponible.');
        }

        // ✅ Guarda o actualiza una sola vez por día (evita duplicados)
        Cotizacion::updateOrCreate(
            [
                'tipo'       => $tipo,
                'tipo_valor' => 'venta',
                'fecha'      => Carbon::today(),
            ],
            ['valor' => $cotizacion]
        );

        return [
            'tipo'       => $tipo,
            'valor'      => $valor,
            'cotizacion' => (float) $cotizacion,
            'resultado'  => round($valor * $cotizacion, 2),
        ];
    }

    public function promedios(
        string $tipo = 'oficial',
        string $tipoValor = 'venta',
        ?int $anio = null,
        ?int $mes = null
    ) {
        $q = DB::table('cotizaciones')
            ->where('tipo', $tipo)
            ->where('tipo_valor', $tipoValor);

        if ($anio) $q->whereYear('fecha', $anio);
        if ($mes)  $q->whereMonth('fecha', $mes);

        return $q->selectRaw('YEAR(fecha) as anio, MONTH(fecha) as mes, AVG(valor) as promedio')
            ->groupBy('anio', 'mes')
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->get();
    }
}
