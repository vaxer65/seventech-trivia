<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialEvento extends Model
{
    protected $table = 'historial_eventos';
    protected $guarded = [];

    protected $casts = [
        'podio_top3'           => 'array',
        'resultados_completos' => 'array',
    ];
}