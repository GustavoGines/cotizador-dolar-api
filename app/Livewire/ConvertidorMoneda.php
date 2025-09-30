<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\CotizacionService;

class ConvertidorMoneda extends Component
{
    public $valor;
    public $tipo = 'oficial';
    public $direccion = 'usd_a_pesos';

    public $resultado;
    public $cotizacion;

    // Filtros
    public $anio; // (string|int|null) año seleccionado en el selector
    public $mes;  // (string|int|null) mes seleccionado en el selector

    // Estado de promedios
    public $promedios = [];        // array normalizado
    public $anioActual;            // año que se está mostrando/paginando
    public $anioMin = 2020;
    public $anioMax;
    public $promediosVisibles = false;

    public function mount()
    {
        $this->anioMax    = (int) now()->year;
        $this->anioActual = $this->anioMax;
        $this->convertir();
    }

    public function updated($property)
    {
        if (in_array($property, ['valor', 'tipo', 'direccion'])) {
            $this->convertir();
        }
    
        if ($property === 'tipo' && $this->promediosVisibles) {
            $this->cargarPromedios();
        }
    
        // ⬇️ Si el selector de año cambia:
        if ($property === 'anio') {
            if ($this->anio) {
                // Si eligieron un año concreto, sincronizamos la paginación
                $this->anioActual = (int) $this->anio;
            } else {
                // Si eligieron "Todos los Años", NO tocamos $anioActual
                // (queda el que está mostrándose)
            }
            if ($this->promediosVisibles) {
                $this->cargarPromedios();
            }
        }
    
        if ($property === 'mes' && $this->promediosVisibles) {
            $this->cargarPromedios();
        }
    }

    public function convertir()
    {
        $this->validate([
            'valor'     => 'nullable|numeric|min:0.1',
            'tipo'      => 'required|string',
            'direccion' => 'required|string|in:usd_a_pesos,pesos_a_usd',
        ]);

        if ($this->valor === null || $this->valor === '') {
            $this->resultado = null;
            $this->cotizacion = null;
            return;
        }

        try {
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

    /**
     * Carga promedios respetando:
     * - Año mostrado ($anioActual). Si no existe, toma selector $anio o año actual.
     * - Mes del selector ($mes): si está presente, muestra SOLO ese mes; si no, muestra los 12.
     */
    public function cargarPromedios()
    {
        // Usá el año que ya está en pantalla; si no hubiera, cae al seleccionado; si tampoco, al año actual
        $this->anioActual = $this->anioActual ?: ($this->anio ? (int)$this->anio : $this->anioMax);
        $anio = $this->anioActual;
        $mes  = $this->mes ? (int)$this->mes : null;
    
        $svc  = app(CotizacionService::class);
        $rows = $svc->promedios($this->tipo, 'venta', $anio, $mes);
    
        if ($mes) {
            $prom = optional($rows->first())->promedio;
            $this->promedios = [[
                'anio'     => $anio,
                'mes'      => $mes,
                'promedio' => $prom !== null ? round((float)$prom, 2) : null,
            ]];
        } else {
            $map = [];
            foreach ($rows as $r) $map[(int)$r->mes] = round((float)$r->promedio, 2);
    
            $out = [];
            for ($m = 1; $m <= 12; $m++) {
                $out[] = ['anio'=>$anio, 'mes'=>$m, 'promedio'=>$map[$m] ?? null];
            }
            $this->promedios = $out;
        }
    
        $this->promediosVisibles = true;
    
        // 🚫 IMPORTANTE: NO sincronicemos $this->anio acá.
        // Dejalo para las flechas (prevYear/nextYear).
    }
    
    
    public function prevYear()
    {
        if ($this->anioActual > $this->anioMin) {
            $this->anioActual--;
            $this->anio = (string) $this->anioActual; // ⬅️ actualiza el selector
            $this->cargarPromedios();
        }
    }
    
    public function nextYear()
    {
        if ($this->anioActual < $this->anioMax) {
            $this->anioActual++;
            $this->anio = (string) $this->anioActual; // ⬅️ actualiza el selector
            $this->cargarPromedios();
        }
    }
    
    public function render()
    {
        return view('livewire.convertidor-monedas');
    }
}
