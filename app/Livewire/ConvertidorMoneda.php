<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class ConvertidorMoneda extends Component
{
    public $valorUsd;
    public $tipo = 'oficial';
    public $resultado;
    public $cotizacion;

    public function mount()
    {
        // Cargar la cotización inicial (dólar oficial)
        $this->actualizarCotizacion();
    }

    public function updated($property)
    {
        // Cada vez que cambia "valorUsd" o "tipo", actualizamos
        if (in_array($property, ['valorUsd', 'tipo'])) {
            $this->convertir();
        }
    }

    public function actualizarCotizacion()
    {
        $baseUrl = config('services.dolarapi.url');
        $response = Http::withOptions([
            'verify' => false,
        ])->get("{$baseUrl}/{$this->tipo}");


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
    }

    public function render()
    {
        return view('livewire.convertidor-monedas');
    }
}
