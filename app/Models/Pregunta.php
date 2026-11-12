<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pregunta extends Model
{
    protected $table = 'preguntas';
    protected $guarded = [];

    protected $casts = [
        'activa'   => 'boolean',
        'es_amena' => 'boolean',
    ];

    // BLINDAJE: Jamás se envía la respuesta correcta ni metadatos internos al frontend de concursantes
    protected $hidden = ['respuesta_correcta', 'es_amena'];

    public function modulo()
    {
        return $this->belongsTo(Modulo::class);
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }
}