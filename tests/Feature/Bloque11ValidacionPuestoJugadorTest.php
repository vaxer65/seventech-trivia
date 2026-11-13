<?php

namespace Tests\Feature;

use App\Models\ConfiguracionEvento;
use App\Models\Equipo;
use App\Models\Modulo;
use App\Models\Nivel;
use App\Models\Pregunta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class Bloque11ValidacionPuestoJugadorTest extends TestCase
{
    use RefreshDatabase;

    private Modulo $modulo;
    private Nivel $nivel1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulo = Modulo::create([
            'nombre'      => 'Ingeniería de Software',
            'descripcion' => 'Módulo de prueba Bloque 11',
        ]);

        $this->nivel1 = Nivel::create([
            'nombre'             => 'Etapa 1',
            'orden_dificultad'   => 1,
            'cantidad_preguntas' => 3,
            'puntos_por_acierto' => 100,
            'tiempo_limite_seg'  => 20,
            'es_desempate'       => false,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            Pregunta::create([
                'modulo_id'          => $this->modulo->id,
                'nivel_id'           => $this->nivel1->id,
                'enunciado'          => "Pregunta Bloque 11 #{$i}",
                'opcion_a'           => 'Opción A',
                'opcion_b'           => 'Opción B',
                'opcion_c'           => 'Opción C',
                'opcion_d'           => 'Opción D',
                'respuesta_correcta' => 'a',
                'activa'             => true,
                'es_amena'           => $i === 1,
            ]);
        }

        ConfiguracionEvento::create([
            'nombre_evento'          => 'Seven Tech Trivia 2026',
            'estado_juego'           => 'en_curso',
            'tiempo_total_minutos'   => 15,
            'max_integrantes_equipo' => 5,
            'max_equipos_permitidos' => 30,
            'semilla_partida'        => 'semilla_bloque11_test',
            'fin_evento_at'          => now()->addMinutes(15),
        ]);
    }

    public function test_vista_jugar_usa_validacion_inline_con_novalidate_toast_y_bloques_de_puesto(): void
    {
        $resp = $this->get('/jugar')->assertOk();

        $resp->assertSee('<form id="form-registro" novalidate', false)
            ->assertSee('<form id="form-recuperar" novalidate', false)
            ->assertDontSee('setCustomValidity', false)
            ->assertDontSee('reportValidity', false)
            ->assertSee('validarFormularioConcursante', false)
            ->assertSee('error-inline-campo', false)
            ->assertSee('data-campo-grupo', false)
            ->assertSee("'border-rose-400/80', 'ring-2', 'ring-rose-500/20'", false)
            ->assertSee('window.mostrarToast(mensaje, \'error\')', false)
            ->assertSee('id="header-puesto-equipo"', false)
            ->assertSee('id="final-puesto-equipo"', false)
            ->assertSee('id="tiempo-final-texto"', false);
    }

    public function test_estado_juego_calcula_posicion_actual_y_total_equipos_respetando_desempates_y_presupuesto_queries(): void
    {
        // #1: 300 pts, 3 aciertos, 4000ms
        $equipo1 = Equipo::create([
            'nombre_equipo'       => 'Primero Veloz',
            'token_sesion'        => Str::random(60),
            'pin_recuperacion'    => '1001',
            'puntaje_total'       => 300,
            'aciertos_totales'    => 3,
            'tiempo_acumulado_ms' => 4000,
            'fase_actual'         => 1,
        ]);

        // #2: 300 pts, 3 aciertos, 7500ms (mismo puntaje y aciertos, mayor tiempo)
        $equipo2 = Equipo::create([
            'nombre_equipo'       => 'Segundo Tiempo',
            'token_sesion'        => Str::random(60),
            'pin_recuperacion'    => '1002',
            'puntaje_total'       => 300,
            'aciertos_totales'    => 3,
            'tiempo_acumulado_ms' => 7500,
            'fase_actual'         => 1,
        ]);

        // #3: 300 pts, 2 aciertos, 3000ms (mismo puntaje, menos aciertos)
        $equipo3 = Equipo::create([
            'nombre_equipo'       => 'Tercero Menos Aciertos',
            'token_sesion'        => Str::random(60),
            'pin_recuperacion'    => '1003',
            'puntaje_total'       => 300,
            'aciertos_totales'    => 2,
            'tiempo_acumulado_ms' => 3000,
            'fase_actual'         => 1,
        ]);

        // #4: 100 pts, 1 acierto, 2000ms
        $equipo4 = Equipo::create([
            'nombre_equipo'       => 'Cuarto Puntaje',
            'token_sesion'        => Str::random(60),
            'pin_recuperacion'    => '1004',
            'puntaje_total'       => 100,
            'aciertos_totales'    => 1,
            'tiempo_acumulado_ms' => 2000,
            'fase_actual'         => 1,
        ]);

        // Verificar puestos exactos para cada equipo
        $this->withSession(['equipo_token' => $equipo1->token_sesion])
            ->getJson('/api/estado-juego')
            ->assertOk()
            ->assertJsonPath('posicion_actual', 1)
            ->assertJsonPath('total_equipos', 4);

        $this->withSession(['equipo_token' => $equipo2->token_sesion])
            ->getJson('/api/estado-juego')
            ->assertOk()
            ->assertJsonPath('posicion_actual', 2)
            ->assertJsonPath('total_equipos', 4);

        $this->withSession(['equipo_token' => $equipo3->token_sesion])
            ->getJson('/api/estado-juego')
            ->assertOk()
            ->assertJsonPath('posicion_actual', 3)
            ->assertJsonPath('total_equipos', 4);

        // Medir presupuesto estricto <= 7 queries en una llamada real de /api/estado-juego
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->withSession(['equipo_token' => $equipo4->token_sesion])
            ->getJson('/api/estado-juego')
            ->assertOk()
            ->assertJsonPath('posicion_actual', 4)
            ->assertJsonPath('total_equipos', 4);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(7, count($queries), 'GET /api/estado-juego superó el presupuesto de <= 7 queries.');

        // También en estado finalizado devuelve el puesto final y total
        ConfiguracionEvento::first()->update(['estado_juego' => 'finalizado']);

        $this->withSession(['equipo_token' => $equipo2->token_sesion])
            ->getJson('/api/estado-juego')
            ->assertOk()
            ->assertJsonPath('estado_juego', 'finalizado')
            ->assertJsonPath('posicion_actual', 2)
            ->assertJsonPath('total_equipos', 4);
    }
}

