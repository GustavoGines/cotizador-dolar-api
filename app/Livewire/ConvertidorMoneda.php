<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;
use App\Models\Cotizacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ConvertidorMoneda extends Component
{
    public $valorUsd;
    public $tipo = 'oficial';
    public $resultado;
    public $cotizacion;
    public $promedios = [];
    public $anio;
    public $mes;

    public function mount()
    {
        // Cargar la cotización inicial (dólar oficial)
        $this->actualizarCotizacion();
    }

    public function updated($property)
    {
        if (in_array($property, ['valorUsd', 'tipo'])) {
            $this->convertir();
        }
    }

    public function actualizarCotizacion()
    {
        $baseUrl = config('services.dolarapi.url');
        $response = Http::withOptions(['verify' => false])
            ->get("{$baseUrl}/{$this->tipo}");

        if ($response->ok()) {
            $data = $response->json();
            $this->cotizacion = $data['venta'] ?? null;
        } else {
            $this->cotizacion = null;
        }
    }

    public function convertir()
    {
        $this->validate([
            'valorUsd' => 'nullable|numeric|min:0.1',
            'tipo' => 'required|string',
        ]);

        $this->actualizarCotizacion();

        if (!$this->cotizacion || !$this->valorUsd) {
            $this->resultado = null;
            return;
        }

        $this->resultado = $this->valorUsd * $this->cotizacion;

        // ✅ Guardar histórico en la BD
        Cotizacion::create([
            'tipo'       => $this->tipo,
            'tipo_valor' => 'venta',
            'valor'      => $this->cotizacion,
            'fecha'      => Carbon::now(),
        ]);
    }

    public function cargarPromedios()
    {
        $query = DB::table('cotizaciones')
            ->selectRaw('YEAR(fecha) as anio, MONTH(fecha) as mes, AVG(valor) as promedio')
            ->where('tipo', $this->tipo)
            ->where('tipo_valor', 'venta')
            ->groupBy('anio', 'mes')
            ->orderByDesc('anio')
            ->orderByDesc('mes');

        if ($this->anio && $this->mes) {
            $query->whereYear('fecha', $this->anio)
                  ->whereMonth('fecha', $this->mes);
        }

        $this->promedios = $query->get();
        
    }

    public function render()
    {
        return view('livewire.convertidor-monedas');
    }
}
