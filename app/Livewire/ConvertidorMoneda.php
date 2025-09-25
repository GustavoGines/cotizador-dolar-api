<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\CotizacionService;

class ConvertidorMoneda extends Component
{
    public $valor;
    public $tipo = 'oficial';
    public $direccion = 'usd_a_pesos'; // valor por defecto
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
        if (in_array($property, ['valor', 'tipo', 'direccion'])) {
            $this->convertir();
        }
    }

    public function convertir()
    {
        $this->validate([
            'valor' => 'nullable|numeric|min:0.1',
            'tipo'     => 'required|string',
            'direccion' => 'required|string|in:usd_a_pesos,pesos_a_usd',
        ]);

        if (!$this->valor) {
            $this->resultado = null;
            return;
        }

        try {
            $svc = app(CotizacionService::class);
            $cotizacion = $svc->cotizacion($this->tipo);

            $this->cotizacion = $cotizacion;

            if ($this->direccion === 'usd_a_pesos') {
                $this->resultado = round($this->valor * $cotizacion, 2);
            } else {
                $this->resultado = round($this->valor / $cotizacion, 2);
            }
        } catch (\Throwable $e) {
            $this->cotizacion = null;
            $this->resultado  = null;
            $this->addError('valor', 'No se pudo convertir: ' . $e->getMessage());
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
