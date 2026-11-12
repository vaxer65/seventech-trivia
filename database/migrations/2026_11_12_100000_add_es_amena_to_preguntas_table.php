<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('preguntas', function (Blueprint $table) {
            $table->boolean('es_amena')->default(false)->after('activa');
            $table->index(['nivel_id', 'activa', 'es_amena'], 'preguntas_nivel_activa_amena_idx');
        });
    }

    public function down(): void
    {
        Schema::table('preguntas', function (Blueprint $table) {
            $table->dropIndex('preguntas_nivel_activa_amena_idx');
            $table->dropColumn('es_amena');
        });
    }
};

