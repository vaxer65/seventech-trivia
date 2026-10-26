<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. CONFIGURACIÓN GLOBAL (Para escalar y modificar reglas sin tocar código)
        Schema::create('configuracion_eventos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_evento')->default('Seven Tech Trivia 2026');
            $table->integer('max_integrantes_equipo')->default(5); // Modificable
            $table->integer('max_equipos_permitidos')->default(30); // Modificable
            // Estados: 'registro', 'en_curso', 'desempate', 'pausado', 'finalizado'
            $table->string('estado_juego')->default('registro');
            $table->unsignedBigInteger('modulo_activo_id')->nullable();
            $table->timestamps();
        });

        // 2. MÓDULOS / CATEGORÍAS (Se pueden agregar infinitos módulos)
        Schema::create('modulos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Ej: Comunidad Tech, Redes, Desarrollo, IA
            $table->string('descripcion')->nullable();
            $table->string('icono')->default('cpu'); // Nombre del ícono
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // 3. NIVELES / FASES DE LA CARRERA
        Schema::create('niveles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Ej: Fase 1 - Calentamiento, Fase 2 - Pro...
            $table->integer('orden_dificultad'); // 1, 2, 3, 4 (4 = Muerte Súbita/Desempate)
            $table->integer('cantidad_preguntas')->default(5); // ¡Escalable! 5 por defecto
            $table->integer('puntos_por_acierto')->default(100);
            $table->integer('tiempo_limite_seg')->default(30); // Tiempo por pregunta
            $table->string('color_hex')->default('#00E5FF'); // Color visual en la pista
            $table->boolean('es_desempate')->default(false);
            $table->timestamps();
        });

        // 4. BANCO DE PREGUNTAS
        Schema::create('preguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modulo_id')->constrained('modulos')->onDelete('cascade');
            $table->foreignId('nivel_id')->constrained('niveles')->onDelete('cascade');
            $table->text('enunciado');
            $table->string('opcion_a');
            $table->string('opcion_b');
            $table->string('opcion_c');
            $table->string('opcion_d');
            // 'a', 'b', 'c' o 'd'. NUNCA se envía al frontend de concursantes/espectadores
            $table->char('respuesta_correcta', 1);
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });

        // 5. EQUIPOS CONCURSANTES
        Schema::create('equipos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_equipo')->unique(); // Irrepetible a nivel de BD
            $table->string('token_sesion', 64)->unique(); // Blindaje contra robo de sesión
            $table->string('color_carril')->default('#00E5FF'); // Color de su robot en la pista
            $table->integer('fase_actual')->default(1); // En qué nivel de la carrera van
            $table->integer('preguntas_contestadas')->default(0);
            $table->integer('aciertos_totales')->default(0);
            $table->integer('puntaje_total')->default(0);
            // Tiempo total en milisegundos (Clave para el desempate automático y posición)
            $table->bigInteger('tiempo_acumulado_ms')->default(0);
            $table->boolean('en_desempate')->default(false); // Si pasa a ronda de muerte súbita
            $table->boolean('finalizo_carrera')->default(false);
            $table->timestamps();
        });

        // 6. INTEGRANTES POR EQUIPO
        Schema::create('integrantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('cascade');
            $table->string('nombre_completo');
            $table->timestamps();
        });

        // 7. HISTORIAL DE RESPUESTAS (Anti-trampa)
        Schema::create('respuestas_equipos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_id')->constrained('equipos')->onDelete('cascade');
            $table->foreignId('pregunta_id')->constrained('preguntas')->onDelete('cascade');
            $table->foreignId('nivel_id')->constrained('niveles')->onDelete('cascade');
            $table->char('opcion_elegida', 1)->nullable(); // null si se le acabó el tiempo
            $table->boolean('es_correcta')->default(false);
            $table->integer('puntos_ganados')->default(0);
            $table->integer('tiempo_respuesta_ms')->default(0);
            $table->timestamps();

            // CANDADO DE SEGURIDAD: Un equipo jamás puede responder 2 veces la misma pregunta
            $table->unique(['equipo_id', 'pregunta_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('respuestas_equipos');
        Schema::dropIfExists('integrantes');
        Schema::dropIfExists('equipos');
        Schema::dropIfExists('preguntas');
        Schema::dropIfExists('niveles');
        Schema::dropIfExists('modulos');
        Schema::dropIfExists('configuracion_eventos');
    }
};