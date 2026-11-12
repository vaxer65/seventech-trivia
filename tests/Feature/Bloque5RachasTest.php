<?php

namespace Tests\Feature;

use App\Models\ConfiguracionEvento;
use App\Models\Equipo;
use App\Models\Modulo;
use App\Models\Nivel;
use App\Models\Pregunta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Bloque5RachasTest extends TestCase
{
    use RefreshDatabase;

    private Modulo $modulo;
    private Nivel $nivel1;
    private ConfiguracionEvento $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulo = Modulo::create(['nombre' => 'Tech', 'activo' => true]);

        $this->config = ConfiguracionEvento::create([
            'nombre_evento'          => 'Seven Tech Test Rachas',
            'max_integrantes_equipo' => 5,
            'max_equipos_permitidos' => 30,
            'tiempo_total_minutos'   => 15,
            'fin_evento_at'          => now()->addMinutes(15),
            'semilla_partida'        => 'semilla_b5_rachas',
            'estado_juego'           => 'en_curso',
            'modulo_activo_id'       => $this->modulo->id,
        ]);

        $this->nivel1 = Nivel::create([
            'nombre'             => 'Etapa 1',
            'orden_dificultad'   => 1,
            'cantidad_preguntas' => 10,
            'puntos_por_acierto' => 100,
            'tiempo_limite_seg'  => 25,
            'es_desempate'       => false,
        ]);

        foreach (range(1, 12) as $i) {
            Pregunta::create([
                'modulo_id'          => $this->modulo->id,
                'nivel_id'           => $this->nivel1->id,
                'enunciado'          => "Pregunta Racha #{$i}",
                'opcion_a'           => 'Incorrecta A',
                'opcion_b'           => 'Correcta B',
                'opcion_c'           => 'Incorrecta C',
                'opcion_d'           => 'Incorrecta D',
                'respuesta_correcta' => 'b',
                'es_amena'           => $i === 1,
            ]);
        }
    }

    private function letraVisual(array $preguntaJson, string $texto): string
    {
        foreach (['a', 'b', 'c', 'd'] as $letra) {
            if (($preguntaJson["opcion_{$letra}"] ?? null) === $texto) {
                return $letra;
            }
        }
        return 'a';
    }

    public function test_racha_incrementa_con_aciertos_consecutivos_y_se_rompe_con_fallo_manteniendo_maxima(): void
    {
        $equipo = Equipo::create([
            'nombre_equipo'    => 'StreakMasters',
            'token_sesion'     => str_repeat('r', 60),
            'pin_recuperacion' => '1111',
        ]);
        $this->withSession(['equipo_token' => $equipo->token_sesion]);

        // 4 aciertos consecutivos -> racha_actual = 4, racha_maxima = 4
        for ($i = 1; $i <= 4; $i++) {
            $p = $this->getJson('/api/estado-juego')->assertOk()->json('pregunta');
            $letraCorrecta = $this->letraVisual($p, 'Correcta B');

            $this->postJson('/api/enviar-respuesta', [
                'pregunta_id'    => $p['id'],
                'opcion_elegida' => $letraCorrecta,
            ])->assertOk()
              ->assertJsonPath('es_correcta', true)
              ->assertJsonPath('racha_actual', $i)
              ->assertJsonPath('racha_maxima', $i)
              ->assertJsonPath('racha_perdida', false);
        }

        // GET /api/estado-juego expone la racha actual y máxima del equipo
        $this->getJson('/api/estado-juego')
            ->assertOk()
            ->assertJsonPath('equipo.racha_actual', 4)
            ->assertJsonPath('equipo.racha_maxima', 4);

        // 5ta pregunta respondida mal -> racha_actual = 0, racha_maxima = 4, racha_perdida = true
        $p5 = $this->getJson('/api/estado-juego')->assertOk()->json('pregunta');
        $letraIncorrecta = $this->letraVisual($p5, 'Incorrecta A');

        $this->postJson('/api/enviar-respuesta', [
            'pregunta_id'    => $p5['id'],
            'opcion_elegida' => $letraIncorrecta,
        ])->assertOk()
          ->assertJsonPath('es_correcta', false)
          ->assertJsonPath('racha_actual', 0)
          ->assertJsonPath('racha_maxima', 4)
          ->assertJsonPath('racha_perdida', true);

        $equipo->refresh();
        $this->assertSame(0, $equipo->racha_actual);
        $this->assertSame(4, $equipo->racha_maxima);
    }

    public function test_racha_se_rompe_por_tiempo_agotado_y_por_salida_de_pantalla(): void
    {
        $equipo = Equipo::create([
            'nombre_equipo'    => 'TimeoutTest',
            'token_sesion'     => str_repeat('t', 60),
            'pin_recuperacion' => '2222',
        ]);
        $this->withSession(['equipo_token' => $equipo->token_sesion]);

        // Construir racha de 3
        for ($i = 1; $i <= 3; $i++) {
            $p = $this->getJson('/api/estado-juego')->assertOk()->json('pregunta');
            $this->postJson('/api/enviar-respuesta', [
                'pregunta_id'    => $p['id'],
                'opcion_elegida' => $this->letraVisual($p, 'Correcta B'),
            ])->assertOk();
        }

        // Romper por tiempo agotado (opcion_elegida = null)
        $p4 = $this->getJson('/api/estado-juego')->assertOk()->json('pregunta');
        $this->postJson('/api/enviar-respuesta', [
            'pregunta_id'    => $p4['id'],
            'opcion_elegida' => null,
        ])->assertOk()
          ->assertJsonPath('es_correcta', false)
          ->assertJsonPath('racha_actual', 0)
          ->assertJsonPath('racha_maxima', 3)
          ->assertJsonPath('racha_perdida', true);

        // Volver a construir racha de 4
        for ($i = 1; $i <= 4; $i++) {
            $p = $this->getJson('/api/estado-juego')->assertOk()->json('pregunta');
            $this->postJson('/api/enviar-respuesta', [
                'pregunta_id'    => $p['id'],
                'opcion_elegida' => $this->letraVisual($p, 'Correcta B'),
            ])->assertOk();
        }
        $this->assertSame(4, $equipo->fresh()->racha_actual);

        // Romper por salida de pantalla
        $p9 = $this->getJson('/api/estado-juego')->assertOk()->json('pregunta');
        $this->postJson('/api/reportar-salida-pantalla', ['pregunta_id' => $p9['id']])
            ->assertOk()
            ->assertJsonPath('bloqueada', true)
            ->assertJsonPath('racha_actual', 0)
            ->assertJsonPath('racha_maxima', 4)
            ->assertJsonPath('racha_perdida', true);

        $equipo->refresh();
        $this->assertSame(0, $equipo->racha_actual);
        $this->assertSame(4, $equipo->racha_maxima);
    }

    public function test_espectador_expone_racha_actual_y_maxima_reales_y_reiniciar_las_pone_en_cero(): void
    {
        $equipo = Equipo::create([
            'nombre_equipo'    => 'EspectadorVisibles',
            'token_sesion'     => str_repeat('e', 60),
            'pin_recuperacion' => '3333',
            'aciertos_totales' => 5, // Antes causaba falsa racha aunque racha_actual fuera 0
            'racha_actual'     => 0,
            'racha_maxima'     => 4,
        ]);

        $this->getJson('/api/carrera-en-vivo')
            ->assertOk()
            ->assertJsonPath('equipos.0.racha_actual', 0)
            ->assertJsonPath('equipos.0.racha_maxima', 4);

        $equipo->update(['racha_actual' => 3]);
        $this->getJson('/api/carrera-en-vivo')
            ->assertOk()
            ->assertJsonPath('equipos.0.racha_actual', 3)
            ->assertJsonPath('equipos.0.racha_maxima', 4);

        // Al reiniciar conservando equipos, racha_actual y racha_maxima vuelven a 0
        $this->withSession(['es_admin_seventech' => true])
            ->post('/admin/reiniciar', ['borrar_equipos' => '0'])
            ->assertRedirect();

        $equipo->refresh();
        $this->assertSame(0, $equipo->racha_actual);
        $this->assertSame(0, $equipo->racha_maxima);
    }

    public function test_admin_token_mismatch_419_redirige_amigablemente_a_admin(): void
    {
        Route::post('/admin/_test_419', function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        })->middleware('web');

        // Petición web normal -> redirige a /admin con error amigable en password
        $this->post('/admin/_test_419')
            ->assertRedirect('/admin')
            ->assertSessionHasErrors('password');

        // Petición JSON -> responde 419 con JSON de error
        $this->postJson('/admin/_test_419')
            ->assertStatus(419)
            ->assertJsonStructure(['error']);
    }
}
