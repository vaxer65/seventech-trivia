<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Momento exacto (ms) en que el servidor entregó cada pregunta a cada equipo.
        // El tiempo de respuesta se calcula en el servidor a partir de este registro.
        Schema::create('entregas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('cascade');
            $table->foreignId('pregunta_id')->constrained('preguntas')->onDelete('cascade');
            $table->timestamp('entregada_at', 3);

            // Solo se registra la PRIMERA entrega: recargar la página no reinicia el cronómetro
            $table->unique(['equipo_id', 'pregunta_id']);
        });

        Schema::table('configuracion_eventos', function (Blueprint $table) {
            $table->timestamp('pausado_at')->nullable()->after('fin_evento_at');
        });

        Schema::table('respuestas_equipos', function (Blueprint $table) {
            $table->index(['equipo_id', 'nivel_id'], 'respuestas_equipo_nivel_idx');
        });
    }

    public function down(): void
    {
        Schema::table('respuestas_equipos', function (Blueprint $table) {
            $table->dropIndex('respuestas_equipo_nivel_idx');
        });
        Schema::table('configuracion_eventos', function (Blueprint $table) {
            $table->dropColumn('pausado_at');
        });
        Schema::dropIfExists('entregas');
    }
};

