<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_eventos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_edicion'); // Ej: Seven Tech Trivia - Edición Noviembre 2026
            $table->string('modulo_jugado')->nullable();
            $table->integer('total_equipos')->default(0);
            // Guardamos en JSON el Top 3 y el resumen completo de equipos + integrantes
            $table->json('podio_top3')->nullable();
            $table->json('resultados_completos')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_eventos');
    }
};