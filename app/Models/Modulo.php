<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    protected $table = 'modulos';
    protected $guarded = [];

    public function preguntas()
    {
        return $this->hasMany(Pregunta::class);
    }
}