<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion_eventos', function (Blueprint $table) {
            // Duración total de toda la trivia en minutos (Ej: 15 minutos en total)
            $table->integer('tiempo_total_minutos')->default(15)->after('max_equipos_permitidos');
            // Momento exacto en que termina la trivia cuando está en curso
            $table->timestamp('fin_evento_at')->nullable()->after('tiempo_total_minutos');
            // Semilla aleatoria para elegir 5 preguntas al azar entre 100, iguales para todos los equipos
            $table->string('semilla_partida', 32)->default('seventech_init')->after('fin_evento_at');
        });
    }

    public function down(): void
    {
        Schema::table('configuracion_eventos', function (Blueprint $table) {
            $table->dropColumn(['tiempo_total_minutos', 'fin_evento_at', 'semilla_partida']);
        });
    }
};