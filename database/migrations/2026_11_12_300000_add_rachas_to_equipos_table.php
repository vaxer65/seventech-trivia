<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->unsignedInteger('racha_actual')->default(0)->after('aciertos_totales');
            $table->unsignedInteger('racha_maxima')->default(0)->after('racha_actual');
        });
    }

    public function down(): void
    {
        Schema::table('equipos', function (Blueprint $table) {
            $table->dropColumn(['racha_actual', 'racha_maxima']);
        });
    }
};
