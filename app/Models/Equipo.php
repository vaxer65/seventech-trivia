<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    protected $table = 'equipos';
    protected $guarded = [];

    // Ocultamos el token de sesión en las vistas públicas del espectador
    protected $hidden = ['token_sesion'];

    protected $casts = [
        'salidas_pantalla' => 'integer',
        'racha_actual'     => 'integer',
        'racha_maxima'     => 'integer',
        'en_desempate'     => 'boolean',
        'finalizo_carrera' => 'boolean',
    ];

    public function integrantes()
    {
        return $this->hasMany(Integrante::class);
    }

    public function respuestas()
    {
        return $this->hasMany(RespuestaEquipo::class);
    }
}