<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entrega extends Model
{
    protected $table = 'entregas';
    protected $guarded = [];
    public $timestamps = false;

    // Precisión de milisegundos al guardar/leer entregada_at
    protected $dateFormat = 'Y-m-d H:i:s.v';

    protected $casts = [
        'entregada_at' => 'datetime',
    ];
}

