<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CotizacionSeeder extends Seeder
{
    public function run()
    {
        $tipos = ['oficial', 'blue', 'mep', 'ccl'];
        $tipoValores = ['compra', 'venta'];

        // Rango de fechas: enero 2023 hasta septiembre 2025
        $inicio = Carbon::create(2023, 1, 1);
        $fin = Carbon::create(2025, 9, 30);

        // Iterar mes a mes
        $fecha = $inicio->copy();
        while ($fecha->lte($fin)) {
            foreach ($tipos as $tipo) {
                foreach ($tipoValores as $tipoValor) {
                    // 5 registros por cada mes y combinación
                    for ($i = 0; $i < 5; $i++) {
                        DB::table('cotizaciones')->insert([
                            'tipo' => $tipo,
                            'tipo_valor' => $tipoValor,
                            'valor' => rand(800, 1200) + (rand(0, 99) / 100), // valores entre 800 y 1200 con decimales
                            'fecha' => Carbon::create(
                                $fecha->year,
                                $fecha->month,
                                rand(1, $fecha->daysInMonth) // día aleatorio válido
                            ),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
            // Avanzar al mes siguiente
            $fecha->addMonth();
        }
    }
}
