<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionEvento extends Model
{
    protected $table = 'configuracion_eventos';
    protected $guarded = [];

    public function moduloActivo()
    {
        return $this->belongsTo(Modulo::class, 'modulo_activo_id');
    }
}