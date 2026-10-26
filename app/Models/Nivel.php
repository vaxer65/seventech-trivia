<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Nivel extends Model
{
    protected $table = 'niveles';
    protected $guarded = [];

    public function preguntas()
    {
        return $this->hasMany(Pregunta::class);
    }
}