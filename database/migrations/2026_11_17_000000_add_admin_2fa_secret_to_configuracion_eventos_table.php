<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracion_eventos', function (Blueprint $table) {
            $table->text('admin_2fa_secret')->nullable()->after('semilla_partida');
        });
    }

    public function down(): void
    {
        Schema::table('configuracion_eventos', function (Blueprint $table) {
            $table->dropColumn('admin_2fa_secret');
        });
    }
};

