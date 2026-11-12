<?php

namespace Tests\Feature;

use App\Models\ConfiguracionEvento;
use App\Models\Entrega;
use App\Models\Equipo;
use App\Models\HistorialEvento;
use App\Models\Integrante;
use App\Models\Modulo;
use App\Models\Nivel;
use App\Models\Pregunta;
use App\Models\RespuestaEquipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Bloque1ReinicioTest extends TestCase
{
    use RefreshDatabase;

    private Modulo $modulo;
    private Nivel $nivel1;
    private Nivel $nivel2;
    private ConfiguracionEvento $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulo = Modulo::create(['nombre' => 'Comunidad Tech', 'activo' => true]);
        $this->config = ConfiguracionEvento::create([
            'nombre_evento'          => 'Seven Tech Test Reinicio',
            'max_integrantes_equipo' => 5,
            'max_equipos_permitidos' => 30,
            'tiempo_total_minutos'   => 15,
            'fin_evento_at'          => now()->addMinutes(15),
            'pausado_at'             => now()->subMinutes(2),
            'semilla_partida'        => 'semilla_inicial_b1',
            'estado_juego'           => 'en_curso',
            'modulo_activo_id'       => $this->modulo->id,
        ]);

        $this->nivel1 = Nivel::create([
            'nombre' => 'Etapa 1', 'orden_dificultad' => 1, 'cantidad_preguntas' => 2,
            'puntos_por_acierto' => 100, 'tiempo_limite_seg' => 25, 'es_desempate' => false,
        ]);
        $this->nivel2 = Nivel::create([
            'nombre' => 'Etapa 2', 'orden_dificultad' => 2, 'cantidad_preguntas' => 2,
            'puntos_por_acierto' => 200, 'tiempo_limite_seg' => 25, 'es_desempate' => false,
        ]);

        foreach (range(1, 6) as $i) {
            Pregunta::create([
                'modulo_id' => $this->modulo->id, 'nivel_id' => $this->nivel1->id,
                'enunciado' => "P1-{$i}", 'opcion_a' => 'A', 'opcion_b' => 'B',
                'opcion_c' => 'C', 'opcion_d' => 'D', 'respuesta_correcta' => 'b',
            ]);
            Pregunta::create([
                'modulo_id' => $this->modulo->id, 'nivel_id' => $this->nivel2->id,
                'enunciado' => "P2-{$i}", 'opcion_a' => 'A', 'opcion_b' => 'B',
                'opcion_c' => 'C', 'opcion_d' => 'D', 'respuesta_correcta' => 'c',
            ]);
        }
    }

    private function poblarPartidaActivaConHistorial(): array
    {
        $eq1 = Equipo::create([
            'nombre_equipo'         => 'Titanes',
            'token_sesion'          => str_repeat('t', 60),
            'pin_recuperacion'      => '1234',
            'fase_actual'           => 2,
            'preguntas_contestadas' => 3,
            'aciertos_totales'      => 2,
            'puntaje_total'         => 300,
            'tiempo_acumulado_ms'   => 9500,
            'en_desempate'          => true,
            'finalizo_carrera'      => true,
        ]);
        Integrante::create(['equipo_id' => $eq1->id, 'nombre_completo' => 'Ana Torres']);
        Integrante::create(['equipo_id' => $eq1->id, 'nombre_completo' => 'Luis Mera']);

        $eq2 = Equipo::create([
            'nombre_equipo'         => 'Centinelas',
            'token_sesion'          => str_repeat('c', 60),
            'pin_recuperacion'      => '5678',
            'fase_actual'           => 1,
            'preguntas_contestadas' => 1,
            'aciertos_totales'      => 1,
            'puntaje_total'         => 100,
            'tiempo_acumulado_ms'   => 4200,
            'en_desempate'          => false,
            'finalizo_carrera'      => false,
        ]);
        Integrante::create(['equipo_id' => $eq2->id, 'nombre_completo' => 'Carlos Ruiz']);
        Integrante::create(['equipo_id' => $eq2->id, 'nombre_completo' => 'Elena Paz']);

        $pregunta = Pregunta::first();

        Entrega::insert([
            ['equipo_id' => $eq1->id, 'pregunta_id' => $pregunta->id, 'entregada_at' => now()->format('Y-m-d H:i:s.v')],
            ['equipo_id' => $eq2->id, 'pregunta_id' => $pregunta->id, 'entregada_at' => now()->format('Y-m-d H:i:s.v')],
        ]);

        RespuestaEquipo::create([
            'equipo_id'           => $eq1->id,
            'pregunta_id'         => $pregunta->id,
            'nivel_id'            => $pregunta->nivel_id,
            'opcion_elegida'      => 'b',
            'es_correcta'         => true,
            'puntos_ganados'      => 100,
            'tiempo_respuesta_ms' => 3100,
        ]);

        $historial = HistorialEvento::create([
            'nombre_edicion'       => 'Edición Histórica 2025',
            'modulo_jugado'        => 'Comunidad Tech',
            'total_equipos'        => 12,
            'podio_top3'           => [['posicion' => 1, 'nombre_equipo' => 'Campeones 2025', 'puntaje_total' => 1500]],
            'resultados_completos' => [['posicion' => 1, 'nombre_equipo' => 'Campeones 2025', 'puntaje_total' => 1500]],
        ]);

        return [$eq1, $eq2, $historial];
    }

    // 1. Reinicio total (borrar_equipos = true): cero absoluto en tablas de partida y Salón de la Fama intacto
    public function test_reinicio_total_deja_tablas_en_cero_y_conserva_salon_de_la_fama(): void
    {
        [, , $historial] = $this->poblarPartidaActivaConHistorial();
        $totalPreguntasAntes = Pregunta::count();
        $semillaAntes = $this->config->semilla_partida;

        $this->withSession(['es_admin_seventech' => true])
            ->post('/admin/reiniciar', ['borrar_equipos' => '1'])
            ->assertRedirect();

        $this->assertSame(0, Equipo::count());
        $this->assertSame(0, Integrante::count());
        $this->assertSame(0, RespuestaEquipo::count());
        $this->assertSame(0, Entrega::count());

        $configNueva = $this->config->fresh();
        $this->assertSame('registro', $configNueva->estado_juego);
        $this->assertNull($configNueva->fin_evento_at);
        $this->assertNull($configNueva->pausado_at);
        $this->assertNotEmpty($configNueva->semilla_partida);
        $this->assertNotSame($semillaAntes, $configNueva->semilla_partida);

        // El Salón de la Fama y el banco de preguntas siguen intactos
        $this->assertSame(1, HistorialEvento::count());
        $this->assertSame('Edición Histórica 2025', HistorialEvento::findOrFail($historial->id)->nombre_edicion);
        $this->assertSame($totalPreguntasAntes, Pregunta::count());
        $this->assertSame(2, Nivel::count());
        $this->assertSame(1, Modulo::count());
    }

    // 2. Reinicio conservando equipos (borrar_equipos = false): equipos e integrantes intactos, contadores y entregas en cero
    public function test_reinicio_conservando_equipos_resetea_contadores_y_conserva_integrantes_e_historial(): void
    {
        [$eq1, $eq2, $historial] = $this->poblarPartidaActivaConHistorial();
        $semillaAntes = $this->config->semilla_partida;

        $this->withSession(['es_admin_seventech' => true])
            ->post('/admin/reiniciar', ['borrar_equipos' => '0'])
            ->assertRedirect();

        $this->assertSame(2, Equipo::count());
        $this->assertSame(4, Integrante::count());
        $this->assertSame(0, RespuestaEquipo::count());
        $this->assertSame(0, Entrega::count());

        foreach ([$eq1->fresh(), $eq2->fresh()] as $eq) {
            $this->assertSame(1, (int) $eq->fase_actual);
            $this->assertSame(0, (int) $eq->preguntas_contestadas);
            $this->assertSame(0, (int) $eq->aciertos_totales);
            $this->assertSame(0, (int) $eq->puntaje_total);
            $this->assertSame(0, (int) $eq->tiempo_acumulado_ms);
            $this->assertFalse((bool) $eq->en_desempate);
            $this->assertFalse((bool) $eq->finalizo_carrera);
        }

        $configNueva = $this->config->fresh();
        $this->assertSame('registro', $configNueva->estado_juego);
        $this->assertNull($configNueva->fin_evento_at);
        $this->assertNull($configNueva->pausado_at);
        $this->assertNotSame($semillaAntes, $configNueva->semilla_partida);

        // El Salón de la Fama sigue intacto
        $this->assertSame(1, HistorialEvento::count());
        $this->assertNotNull(HistorialEvento::find($historial->id));
    }

    // 3. Respuesta tardía o petición de estado tras reinicio es rechazada limpiamente sin ensuciar la BD
    public function test_rechaza_respuesta_tardia_y_estado_tras_reinicio_sin_ensuciar_bd(): void
    {
        [$eq1] = $this->poblarPartidaActivaConHistorial();

        // Dejamos a eq1 limpio en la partida actual y le entregamos su primera pregunta oficial
        RespuestaEquipo::query()->delete();
        Entrega::query()->delete();
        $eq1->update([
            'fase_actual' => 1, 'preguntas_contestadas' => 0, 'aciertos_totales' => 0,
            'puntaje_total' => 0, 'tiempo_acumulado_ms' => 0, 'en_desempate' => false, 'finalizo_carrera' => false,
        ]);

        $this->withSession(['equipo_token' => $eq1->token_sesion]);
        $preguntaAntesId = $this->getJson('/api/estado-juego')->assertOk()->json('pregunta.id');
        $this->assertNotNull($preguntaAntesId);
        $this->assertSame(1, Entrega::count());

        // Caso A: El Admin reinicia conservando equipos (pasa a 'registro' y limpia entregas)
        $this->withSession(['es_admin_seventech' => true])
            ->post('/admin/reiniciar')
            ->assertRedirect();

        $this->assertSame(0, Entrega::count());
        $this->assertSame(0, RespuestaEquipo::count());

        // El celular envía tardíamente su respuesta a la pregunta de la partida anterior -> 409 sin ensuciar BD
        $this->withSession(['equipo_token' => $eq1->token_sesion])
            ->postJson('/api/enviar-respuesta', [
                'pregunta_id'    => $preguntaAntesId,
                'opcion_elegida' => 'b',
            ])->assertStatus(409)->assertJsonPath('resync', true);

        $this->assertSame(0, RespuestaEquipo::count());
        $this->assertSame(0, Entrega::count());
        $this->assertSame(0, $eq1->fresh()->puntaje_total);

        // El celular consulta /api/estado-juego estando en 'registro' -> no debe insertar ninguna entrega
        $this->withSession(['equipo_token' => $eq1->token_sesion])
            ->getJson('/api/estado-juego')
            ->assertOk()
            ->assertJsonPath('estado_juego', 'registro')
            ->assertJsonPath('pregunta', null);
        $this->assertSame(0, Entrega::count());

        // Caso B: El Admin arranca una nueva partida inmediatamente, pero el celular manda el POST viejo sin haber recibido entrega en la nueva
        $this->withSession(['es_admin_seventech' => true])
            ->post('/admin/configuracion', [
                'estado_juego'           => 'en_curso',
                'modulo_activo_id'       => $this->modulo->id,
                'max_integrantes_equipo' => 5,
                'max_equipos_permitidos' => 30,
                'tiempo_total_minutos'   => 15,
            ])->assertRedirect();

        $this->withSession(['equipo_token' => $eq1->token_sesion])
            ->postJson('/api/enviar-respuesta', [
                'pregunta_id'    => $preguntaAntesId,
                'opcion_elegida' => 'b',
            ])->assertStatus(409)->assertJsonPath('resync', true);

        $this->assertSame(0, RespuestaEquipo::count());
        $this->assertSame(0, Entrega::count());
        $this->assertSame(0, $eq1->fresh()->puntaje_total);

        // Caso C: El Admin reinicia borrando equipos (borrar_equipos = 1) y el celular con cookie vieja intenta responder
        $this->withSession(['es_admin_seventech' => true])
            ->post('/admin/reiniciar', ['borrar_equipos' => '1'])
            ->assertRedirect();

        $this->withSession(['equipo_token' => $eq1->token_sesion])
            ->postJson('/api/enviar-respuesta', [
                'pregunta_id'    => $preguntaAntesId,
                'opcion_elegida' => 'b',
            ])->assertStatus(401);

        $this->assertSame(0, Equipo::count());
        $this->assertSame(0, RespuestaEquipo::count());
        $this->assertSame(0, Entrega::count());
    }
}
