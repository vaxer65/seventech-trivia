<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('respuestas_equipos', function (Blueprint $table) {
            $table->boolean('bloqueada_por_salida')->default(false)->after('es_correcta');
        });

        Schema::table('equipos', function (Blueprint $table) {
            $table->unsignedInteger('salidas_pantalla')->default(0)->after('tiempo_acumulado_ms');
        });
    }

    public function down(): void
    {
        Schema::table('respuestas_equipos', function (Blueprint $table) {
            $table->dropColumn('bloqueada_por_salida');
        });

        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn('salidas_pantalla');
        });
    }
};
