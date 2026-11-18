<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class ConfiguracionEvento extends Model
{
    protected $table = 'configuracion_eventos';
    protected $guarded = [];

    protected $hidden = [
        'admin_2fa_secret',
    ];

    protected $casts = [
        'fin_evento_at' => 'datetime',
        'pausado_at'    => 'datetime',
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

    public function obtenerSecreto2fa(): ?string
    {
        $cifrado = trim((string) ($this->admin_2fa_secret ?? ''));
        if ($cifrado !== '') {
            try {
                $desencriptado = trim((string) Crypt::decryptString($cifrado));
                if ($desencriptado !== '') {
                    return $desencriptado;
                }
            } catch (\Throwable $e) {
                return null;
            }
        }

        $envSecret = trim((string) config('app.admin_2fa_secret', ''));
        return $envSecret !== '' ? $envSecret : null;
    }

    public function tiene2faActivo(): bool
    {
        return $this->obtenerSecreto2fa() !== null;
    }
}
