<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\CotizacionService;

class ConvertidorMoneda extends Component
{
    public $valor;
    public $tipo = 'oficial';
    public $direccion = 'usd_a_pesos'; // usd_a_pesos | pesos_a_usd

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
            'valor'     => 'nullable|numeric|min:0.1',
            'tipo'      => 'required|string',
            'direccion' => 'required|string|in:usd_a_pesos,pesos_a_usd',
        ]);

        // Si está vacío, limpiamos resultado/cotización y salimos
        if ($this->valor === null || $this->valor === '') {
            $this->resultado = null;
            $this->cotizacion = null;
            return;
        }

        try {
            /** @var CotizacionService $svc */
            $svc  = app(CotizacionService::class);
            $data = $svc->convertir((float) $this->valor, (string) $this->tipo, (string) $this->direccion);

            $this->cotizacion = $data['cotizacion'];
            $this->resultado  = $data['resultado'];
        } catch (\Throwable $e) {
            $this->cotizacion = null;
            $this->resultado  = null;
            $this->addError('valor', 'No se pudo convertir: ' . $e->getMessage());
        }
    }

    public function cargarPromedios()
    {
        /** @var CotizacionService $svc */
        $svc  = app(CotizacionService::class);
        $rows = $svc->promedios($this->tipo, 'venta', $this->anio, $this->mes);

        // Normalizamos salida para la vista/gráfico
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
