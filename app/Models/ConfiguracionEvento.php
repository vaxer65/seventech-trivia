<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ConfiguracionEvento extends Model
{
    protected $table = 'configuracion_eventos';
    protected $guarded = [];

    protected $casts = [
        'fin_evento_at' => 'datetime',
    ];

    protected $appends = ['segundos_globales_restantes'];

    public function moduloActivo()
    {
        return $this->belongsTo(Modulo::class, 'modulo_activo_id');
    }

    // Calcula en tiempo real cuántos segundos le quedan a toda la trivia
    public function getSegundosGlobalesRestantesAttribute(): ?int
    {
        if ($this->estado_juego !== 'en_curso' || !$this->fin_evento_at) {
            return null;
        }

        $restantes = Carbon::now()->diffInSeconds($this->fin_evento_at, false);
        return max(0, (int) $restantes);
    }
}