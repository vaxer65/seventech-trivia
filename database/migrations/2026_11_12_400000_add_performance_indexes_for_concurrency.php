<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->index(
                ['puntaje_total', 'aciertos_totales', 'tiempo_acumulado_ms'],
                'equipos_ranking_idx'
            );
        });

        Schema::table('preguntas', function (Blueprint $table) {
            $table->index(
                ['modulo_id', 'nivel_id', 'activa', 'es_amena'],
                'preguntas_modulo_nivel_activa_amena_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('preguntas', function (Blueprint $table) {
            $table->dropIndex('preguntas_modulo_nivel_activa_amena_idx');
        });

        Schema::table('equipos', function (Blueprint $table) {
            $table->dropIndex('equipos_ranking_idx');
        });
    }
};

