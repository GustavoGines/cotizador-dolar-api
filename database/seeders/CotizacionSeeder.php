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

        foreach ($tipos as $tipo) {
            foreach ($tipoValores as $tipoValor) {
                // Insertar 5 registros por cada mes (1 a 12)
                for ($mes = 1; $mes <= 12; $mes++) {
                    for ($i = 0; $i < 5; $i++) {
                        DB::table('cotizaciones')->insert([
                            'tipo' => $tipo,
                            'tipo_valor' => $tipoValor,
                            'valor' => rand(800, 1200) + (rand(0, 99) / 100), // valores entre 800 y 1200 con decimales
                            'fecha' => Carbon::create(2023, $mes, rand(1, 28)), // día aleatorio dentro del mes
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }
}
