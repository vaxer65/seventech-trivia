<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pregunta extends Model
{
    protected $table = 'preguntas';
    protected $guarded = [];

    // BLINDAJE: Jamás se envía la respuesta correcta al frontend
    protected $hidden = ['respuesta_correcta'];

    public function modulo()
    {
        return $this->belongsTo(Modulo::class);
    }

    public function nivel()
    {
        return $this->belongsTo(Nivel::class);
    }
}