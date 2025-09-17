<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cotizacion extends Model
{

    use HasFactory;

    protected $table = 'cotizaciones'; // 👈 forzamos el nombre real de la tabla
    
    protected $fillable = [
        'tipo',
        'tipo_valor',
        'valor',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];
}