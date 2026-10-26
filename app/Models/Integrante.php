<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Integrante extends Model
{
    protected $table = 'integrantes';
    protected $guarded = [];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class);
    }
}