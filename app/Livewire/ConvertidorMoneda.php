<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\CotizacionService;

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
        $this->convertir();
    }

    public function updated($property)
    {
        if (in_array($property, ['valorUsd', 'tipo'])) {
            $this->convertir();
        }
    }

    public function convertir()
    {
        $this->validate([
            'valorUsd' => 'nullable|numeric|min:0.1',
            'tipo'     => 'required|string',
        ]);

        if (!$this->valorUsd) {
            $this->resultado = null;
            return;
        }

        try {
            $svc = app(CotizacionService::class);
            $data = $svc->convertir((float) $this->valorUsd, $this->tipo);

            $this->cotizacion = $data['cotizacion'] ?? null;
            $this->resultado  = $data['resultado']  ?? null;
        } catch (\Throwable $e) {
            $this->cotizacion = null;
            $this->resultado  = null;
            $this->addError('valorUsd', 'No se pudo convertir: ' . $e->getMessage());
        }
    }

    public function cargarPromedios()
    {
        $svc = app(CotizacionService::class);
        $rows = $svc->promedios($this->tipo, 'venta', $this->anio, $this->mes);

        // Normalizamos para que siempre sean enteros y float
        $this->promedios = $rows->map(fn($r) => [
            'anio'     => (int) $r->anio,
            'mes'      => (int) $r->mes,
            'promedio' => round((float) $r->promedio, 2),
        ])->toArray();
    }

    public function render()
    {
        return view('livewire.convertidor-monedas');
    }
}
