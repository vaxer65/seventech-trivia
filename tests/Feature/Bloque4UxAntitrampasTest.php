<?php

namespace Tests\Feature;

use App\Models\ConfiguracionEvento;
use App\Models\Equipo;
use App\Models\Modulo;
use App\Models\Nivel;
use App\Models\Pregunta;
use App\Models\RespuestaEquipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class Bloque4UxAntitrampasTest extends TestCase
{
    use RefreshDatabase;

    private Modulo $modulo1;
    private Modulo $modulo2;
    private Nivel $nivel1;
    private ConfiguracionEvento $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulo1 = Modulo::create(['nombre' => 'Módulo Uno', 'activo' => true]);
        $this->modulo2 = Modulo::create(['nombre' => 'Módulo Dos', 'activo' => true]);

        $this->config = ConfiguracionEvento::create([
            'nombre_evento'          => 'Seven Tech Test Bloque 4',
            'max_integrantes_equipo' => 5,
            'max_equipos_permitidos' => 30,
            'tiempo_total_minutos'   => 15,
            'fin_evento_at'          => now()->addMinutes(15),
            'semilla_partida'        => 'semilla_b4_test',
            'estado_juego'           => 'en_curso',
            'modulo_activo_id'       => $this->modulo1->id,
        ]);

        $this->nivel1 = Nivel::create([
            'nombre'             => 'Etapa 1',
            'orden_dificultad'   => 1,
            'cantidad_preguntas' => 3,
            'puntos_por_acierto' => 100,
            'tiempo_limite_seg'  => 25,
            'es_desempate'       => false,
        ]);

        foreach (range(1, 5) as $i) {
            Pregunta::create([
                'modulo_id'          => $this->modulo1->id,
                'nivel_id'           => $this->nivel1->id,
                'enunciado'          => "Pregunta B4 #{$i}",
                'opcion_a'           => 'Opción A',
                'opcion_b'           => 'Opción B',
                'opcion_c'           => 'Opción C',
                'opcion_d'           => 'Opción D',
                'respuesta_correcta' => 'b',
                'es_amena'           => $i === 1,
            ]);
        }
    }

    public function test_reportar_salida_pantalla_anula_pregunta_en_servidor_suma_incidente_y_avanza_tras_recargar(): void
    {
        $equipo = Equipo::create([
            'nombre_equipo'    => 'TabSwitchers',
            'token_sesion'     => str_repeat('t', 60),
            'pin_recuperacion' => '1234',
        ]);

        $this->withSession(['equipo_token' => $equipo->token_sesion]);

        // 1. El equipo recibe la primera pregunta
        $p1Id = (int) $this->getJson('/api/estado-juego')->assertOk()->json('pregunta.id');
        $this->assertGreaterThan(0, $p1Id);

        // 2. Sale de la pestaña/app -> el servidor la anula con 0 puntos
        $this->postJson('/api/reportar-salida-pantalla', ['pregunta_id' => $p1Id])
            ->assertOk()
            ->assertJsonPath('bloqueada', true)
            ->assertJsonPath('duplicada', false)
            ->assertJsonPath('puntos_sumados', 0)
            ->assertJsonPath('salidas_pantalla', 1);

        $equipo->refresh();
        $this->assertSame(1, $equipo->salidas_pantalla);
        $this->assertSame(1, $equipo->preguntas_contestadas);
        $this->assertSame(0, $equipo->puntaje_total);
        $this->assertSame(0, $equipo->aciertos_totales);

        $respuesta = RespuestaEquipo::where('equipo_id', $equipo->id)
            ->where('pregunta_id', $p1Id)
            ->firstOrFail();
        $this->assertTrue((bool) $respuesta->bloqueada_por_salida);
        $this->assertFalse((bool) $respuesta->es_correcta);
        $this->assertSame(0, $respuesta->puntos_ganados);
        $this->assertNull($respuesta->opcion_elegida);

        // 3. Reintentar reportar la misma pregunta no duplica la penalización
        $this->postJson('/api/reportar-salida-pantalla', ['pregunta_id' => $p1Id])
            ->assertOk()
            ->assertJsonPath('bloqueada', true)
            ->assertJsonPath('duplicada', true)
            ->assertJsonPath('salidas_pantalla', 1);
        $this->assertSame(1, $equipo->fresh()->salidas_pantalla);

        // 4. Intentar responder después de la anulación ya no suma puntos ni cambia estado
        $this->postJson('/api/enviar-respuesta', [
            'pregunta_id'    => $p1Id,
            'opcion_elegida' => 'b',
        ])->assertOk()->assertJsonPath('duplicada', true);
        $this->assertSame(0, $equipo->fresh()->puntaje_total);

        // 5. Si recarga (F5), /api/estado-juego ya entrega la siguiente pregunta
        $p2Id = (int) $this->getJson('/api/estado-juego')->assertOk()->json('pregunta.id');
        $this->assertNotSame($p1Id, $p2Id);
    }

    public function test_admin_bloquea_edicion_de_modulo_duracion_etapas_y_preguntas_cuando_partida_esta_en_curso_pausada_o_desempate(): void
    {
        $pregunta = Pregunta::firstOrFail();
        $archivoJson = UploadedFile::fake()->createWithContent('lote.json', json_encode([
            [
                'modulo_id'          => $this->modulo1->id,
                'nivel_id'           => $this->nivel1->id,
                'enunciado'          => 'Pregunta bloqueada',
                'opcion_a'           => 'A',
                'opcion_b'           => 'B',
                'opcion_c'           => 'C',
                'opcion_d'           => 'D',
                'respuesta_correcta' => 'a',
            ],
        ]));

        $this->withSession(['es_admin_seventech' => true]);

        foreach (['en_curso', 'pausado', 'desempate'] as $estadoBloqueado) {
            $this->config->update(['estado_juego' => $estadoBloqueado]);

            // Cambiar módulo activo o duración está prohibido -> 409
            $this->postJson('/admin/configuracion', [
                'estado_juego'           => $estadoBloqueado,
                'modulo_activo_id'       => $this->modulo2->id,
                'max_integrantes_equipo' => 5,
                'max_equipos_permitidos' => 30,
                'tiempo_total_minutos'   => 15,
            ])->assertStatus(409);

            $this->postJson('/admin/configuracion', [
                'estado_juego'           => $estadoBloqueado,
                'modulo_activo_id'       => $this->modulo1->id,
                'max_integrantes_equipo' => 5,
                'max_equipos_permitidos' => 30,
                'tiempo_total_minutos'   => 30,
            ])->assertStatus(409);

            // Editar etapas, módulos y preguntas está prohibido -> 409
            $this->postJson("/admin/nivel/{$this->nivel1->id}", [
                'nombre'             => 'Hack',
                'cantidad_preguntas' => 5,
                'puntos_por_acierto' => 999,
                'tiempo_limite_seg'  => 20,
            ])->assertStatus(409);

            $this->postJson('/admin/modulo', ['nombre' => 'Nuevo'])->assertStatus(409);
            $this->postJson("/admin/modulo/{$this->modulo2->id}/eliminar")->assertStatus(409);
            $this->postJson('/admin/pregunta', [
                'modulo_id'          => $this->modulo1->id,
                'nivel_id'           => $this->nivel1->id,
                'enunciado'          => 'No debe crearse',
                'opcion_a'           => 'A',
                'opcion_b'           => 'B',
                'opcion_c'           => 'C',
                'opcion_d'           => 'D',
                'respuesta_correcta' => 'a',
            ])->assertStatus(409);
            $this->postJson("/admin/pregunta/{$pregunta->id}/editar", [
                'enunciado'          => 'Editada ilegal',
                'opcion_a'           => 'A',
                'opcion_b'           => 'B',
                'opcion_c'           => 'C',
                'opcion_d'           => 'D',
                'respuesta_correcta' => 'a',
            ])->assertStatus(409);
            $this->postJson("/admin/pregunta/{$pregunta->id}/eliminar")->assertStatus(409);
            $this->postJson('/admin/preguntas/importar', ['archivo' => $archivoJson])->assertStatus(409);
        }

        // Sí permite cambiar estado_juego y max_equipos_permitidos sin enviar inputs disabled (conserva modulo_activo_id)
        $this->config->update(['estado_juego' => 'en_curso']);
        $this->postJson('/admin/configuracion', [
            'estado_juego'           => 'pausado',
            'max_integrantes_equipo' => 4,
            'max_equipos_permitidos' => 45,
        ])->assertOk()->assertJsonPath('ok', true)->assertJsonPath('bloqueado', true);

        $configFresh = $this->config->fresh();
        $this->assertSame('pausado', $configFresh->estado_juego);
        $this->assertSame($this->modulo1->id, $configFresh->modulo_activo_id);
        $this->assertSame(15, $configFresh->tiempo_total_minutos);
        $this->assertSame(45, $configFresh->max_equipos_permitidos);

        // Y permite reiniciar limpiando también salidas_pantalla si se conservan equipos
        $equipo = Equipo::create([
            'nombre_equipo'    => 'Persistente',
            'token_sesion'     => str_repeat('p', 60),
            'pin_recuperacion' => '9999',
            'salidas_pantalla' => 3,
        ]);
        $this->post('/admin/reiniciar', ['borrar_equipos' => '0'])->assertRedirect();
        $this->assertSame(0, $equipo->fresh()->salidas_pantalla);
    }

    public function test_admin_permite_editar_y_responde_json_para_autoguardado_en_registro_y_finalizado(): void
    {
        $this->withSession(['es_admin_seventech' => true]);

        foreach (['registro', 'finalizado'] as $estadoEditable) {
            $this->config->update(['estado_juego' => $estadoEditable]);

            $this->postJson('/admin/configuracion', [
                'estado_juego'           => $estadoEditable,
                'modulo_activo_id'       => '',
                'max_integrantes_equipo' => 5,
                'max_equipos_permitidos' => 35,
                'tiempo_total_minutos'   => 20,
            ])->assertOk()
              ->assertJsonPath('ok', true)
              ->assertJsonPath('bloqueado', false);

            $this->assertNull($this->config->fresh()->modulo_activo_id);
            $this->assertSame(20, $this->config->fresh()->tiempo_total_minutos);

            $this->postJson("/admin/nivel/{$this->nivel1->id}", [
                'nombre'             => 'Etapa Renovada',
                'cantidad_preguntas' => 6,
                'puntos_por_acierto' => 150,
                'tiempo_limite_seg'  => 30,
            ])->assertOk()->assertJsonPath('ok', true);

            $this->assertSame('Etapa Renovada', $this->nivel1->fresh()->nombre);
        }

        // Validación fallida en autoguardado JSON devuelve 422
        $this->postJson("/admin/nivel/{$this->nivel1->id}", [
            'nombre'             => '',
            'cantidad_preguntas' => 0,
            'puntos_por_acierto' => 150,
            'tiempo_limite_seg'  => 30,
        ])->assertStatus(422);
    }

    public function test_panel_admin_muestra_contador_de_salidas_de_pantalla_por_equipo(): void
    {
        Equipo::create([
            'nombre_equipo'    => 'Vigilados',
            'token_sesion'     => str_repeat('v', 60),
            'pin_recuperacion' => '7777',
            'salidas_pantalla' => 2,
        ]);

        $this->withSession(['es_admin_seventech' => true])
            ->get('/admin')
            ->assertOk()
            ->assertSee('Vigilados')
            ->assertSee('2 salidas de app');
    }
}
