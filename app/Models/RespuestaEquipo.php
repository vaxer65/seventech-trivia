<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RespuestaEquipo extends Model
{
    protected $table = 'respuestas_equipos';
    protected $guarded = [];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }

    public function pregunta()
    {
        return $this->belongsTo(Pregunta::class);
    }
}