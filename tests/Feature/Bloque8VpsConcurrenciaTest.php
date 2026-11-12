<?php

namespace Tests\Feature;

use App\Models\ConfiguracionEvento;
use App\Models\Entrega;
use App\Models\Equipo;
use App\Models\Integrante;
use App\Models\Modulo;
use App\Models\Nivel;
use App\Models\Pregunta;
use App\Models\RespuestaEquipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Bloque8VpsConcurrenciaTest extends TestCase
{
    use RefreshDatabase;

    private function letraVisualDeTexto(array $preguntaJson, string $textoBuscado): string
    {
        foreach (['a', 'b', 'c', 'd'] as $letra) {
            if (($preguntaJson["opcion_{$letra}"] ?? null) === $textoBuscado) {
                return $letra;
            }
        }

        return 'a';
    }

    public function test_admin_login_rechaza_texto_plano_en_produccion_y_acepta_bcrypt(): void
    {
        try {
            app()->detectEnvironment(fn () => 'production');

            // Texto plano en ADMIN_PASSWORD_HASH debe ser rechazado tajantemente en producción
            config(['app.admin_password_hash' => 'ClaveEnTextoPlanoInsegura']);
            $this->withSession(['_token' => 'csrf_prod_1'])
                ->post('/admin/login', [
                    '_token'   => 'csrf_prod_1',
                    'password' => 'ClaveEnTextoPlanoInsegura',
                ])
                ->assertSessionHasErrors(['password' => 'Contraseña de administrador incorrecta.']);
            $this->assertFalse((bool) session('es_admin_seventech'));

            // Hash bcrypt válido sí permite autenticar en producción
            config(['app.admin_password_hash' => Hash::make('ClaveVpsUltraSegura2026!')]);
            $this->withSession(['_token' => 'csrf_prod_2'])
                ->post('/admin/login', [
                    '_token'   => 'csrf_prod_2',
                    'password' => 'ClaveVpsUltraSegura2026!',
                ])
                ->assertRedirect(route('admin.index'));
            $this->assertTrue((bool) session('es_admin_seventech'));
        } finally {
            app()->detectEnvironment(fn () => 'testing');
        }
    }

    public function test_simulacion_concurrencia_45_equipos_etapas_normales_y_desempate(): void
    {
        $modA = Modulo::create(['nombre' => 'Backend & PHP', 'activo' => true]);
        $modB = Modulo::create(['nombre' => 'Redes & Linux', 'activo' => true]);

        $nivel1 = Nivel::create([
            'nombre'             => 'Etapa 1',
            'orden_dificultad'   => 1,
            'cantidad_preguntas' => 3,
            'puntos_por_acierto' => 100,
            'tiempo_limite_seg'  => 25,
            'es_desempate'       => false,
        ]);
        $nivel2 = Nivel::create([
            'nombre'             => 'Etapa 2',
            'orden_dificultad'   => 2,
            'cantidad_preguntas' => 3,
            'puntos_por_acierto' => 200,
            'tiempo_limite_seg'  => 20,
            'es_desempate'       => false,
        ]);
        $nivel3 = Nivel::create([
            'nombre'             => 'Etapa 3',
            'orden_dificultad'   => 3,
            'cantidad_preguntas' => 3,
            'puntos_por_acierto' => 300,
            'tiempo_limite_seg'  => 15,
            'es_desempate'       => false,
        ]);
        $nivelDesempate = Nivel::create([
            'nombre'             => 'Muerte Súbita',
            'orden_dificultad'   => 4,
            'cantidad_preguntas' => 3,
            'puntos_por_acierto' => 500,
            'tiempo_limite_seg'  => 15,
            'es_desempate'       => true,
        ]);

        foreach ([$nivel1, $nivel2, $nivel3, $nivelDesempate] as $niv) {
            foreach (range(1, 6) as $i) {
                Pregunta::create([
                    'modulo_id'          => $i % 2 === 0 ? $modA->id : $modB->id,
                    'nivel_id'           => $niv->id,
                    'enunciado'          => "{$niv->nombre} Pregunta #{$i}",
                    'opcion_a'           => 'Incorrecta A',
                    'opcion_b'           => 'CORRECTA_B',
                    'opcion_c'           => 'Incorrecta C',
                    'opcion_d'           => 'Incorrecta D',
                    'respuesta_correcta' => 'b',
                    'es_amena'           => !$niv->es_desempate && $i === 1,
                ]);
            }
        }

        $config = ConfiguracionEvento::create([
            'nombre_evento'          => 'Torneo Carga 45 Equipos',
            'max_integrantes_equipo' => 5,
            'max_equipos_permitidos' => 60,
            'tiempo_total_minutos'   => 30,
            'fin_evento_at'          => now()->addMinutes(30),
            'semilla_partida'        => 'semilla_b8_45_equipos',
            'estado_juego'           => 'en_curso',
            'modulo_activo_id'       => null,
        ]);

        $equipos = [];
        $esperado = [];
        for ($n = 1; $n <= 45; $n++) {
            $eq = Equipo::create([
                'nombre_equipo'    => sprintf('Equipo-%02d', $n),
                'token_sesion'     => sprintf('tok_%02d_%s', $n, str_repeat('z', 53)),
                'pin_recuperacion' => str_pad((string) (1000 + $n), 4, '0', STR_PAD_LEFT),
            ]);
            Integrante::create(['equipo_id' => $eq->id, 'nombre_completo' => "Capitan {$n} Demo"]);
            Integrante::create(['equipo_id' => $eq->id, 'nombre_completo' => "Integrante {$n} Demo"]);
            $equipos[$n] = $eq;
            $esperado[$n] = [
                'puntaje'     => 0,
                'aciertos'    => 0,
                'contestadas' => 0,
                'racha'       => 0,
                'racha_max'   => 0,
            ];
        }

        // Ronda intercalada por turno (simulando 45 celulares activos en paralelo por cada pregunta 1..9)
        $puntosPorTurno = [1 => 100, 2 => 100, 3 => 100, 4 => 200, 5 => 200, 6 => 200, 7 => 300, 8 => 300, 9 => 300];
        for ($turno = 1; $turno <= 9; $turno++) {
            for ($n = 1; $n <= 45; $n++) {
                $eq = $equipos[$n];
                $resEstado = $this->withSession(['equipo_token' => $eq->token_sesion])
                    ->getJson('/api/estado-juego')
                    ->assertOk();

                $preg = $resEstado->json('pregunta');
                $this->assertNotNull($preg, "El equipo {$n} debió recibir pregunta en turno {$turno}");

                // Equipos 1..15: 100% aciertos; 16..30: fallan turnos 3,6,9; 31..45: fallan turno 4 y 8
                $acierta = match (true) {
                    $n <= 15 => true,
                    $n <= 30 => ($turno % 3 !== 0),
                    default  => !in_array($turno, [4, 8], true),
                };

                $letraElegida = $this->letraVisualDeTexto($preg, $acierta ? 'CORRECTA_B' : 'Incorrecta A');
                $resResp = $this->withSession(['equipo_token' => $eq->token_sesion])
                    ->postJson('/api/enviar-respuesta', [
                        'pregunta_id'    => $preg['id'],
                        'opcion_elegida' => $letraElegida,
                    ])
                    ->assertOk()
                    ->assertJsonPath('es_correcta', $acierta);

                $esperado[$n]['contestadas']++;
                if ($acierta) {
                    $esperado[$n]['puntaje'] += $puntosPorTurno[$turno];
                    $esperado[$n]['aciertos']++;
                    $esperado[$n]['racha']++;
                    $esperado[$n]['racha_max'] = max($esperado[$n]['racha_max'], $esperado[$n]['racha']);
                } else {
                    $esperado[$n]['racha'] = 0;
                }

                $resResp->assertJsonPath('racha_actual', $esperado[$n]['racha'])
                    ->assertJsonPath('racha_maxima', $esperado[$n]['racha_max']);
            }
        }

        // Integridad exacta tras las 405 respuestas y consulta de meta en estado-juego
        $this->assertSame(405, Entrega::count());
        $this->assertSame(405, RespuestaEquipo::count());

        for ($n = 1; $n <= 45; $n++) {
            // El celular consulta una vez más /api/estado-juego al terminar y el servidor marca finalizo_carrera = true
            $this->withSession(['equipo_token' => $equipos[$n]->token_sesion])
                ->getJson('/api/estado-juego')
                ->assertOk()
                ->assertJsonPath('estado_juego', 'carrera_completada');

            $eq = $equipos[$n]->fresh();
            $this->assertTrue((bool) $eq->finalizo_carrera);
            $this->assertSame($esperado[$n]['puntaje'], $eq->puntaje_total);
            $this->assertSame($esperado[$n]['aciertos'], $eq->aciertos_totales);
            $this->assertSame($esperado[$n]['contestadas'], $eq->preguntas_contestadas);
            $this->assertSame($esperado[$n]['racha'], $eq->racha_actual);
            $this->assertSame($esperado[$n]['racha_max'], $eq->racha_maxima);
            $this->assertGreaterThanOrEqual(0, $eq->tiempo_acumulado_ms);
        }

        // Activar Muerte Súbita para los 3 primeros equipos empatados en 1800 pts
        $this->withSession(['es_admin_seventech' => true])
            ->post('/admin/desempate', [
                'equipos_desempate' => [$equipos[1]->id, $equipos[2]->id, $equipos[3]->id],
            ])
            ->assertRedirect();

        $this->assertSame('desempate', $config->fresh()->estado_juego);

        // Equipo-01 acierta 2 (+1000), Equipo-02 acierta 1 (+500), Equipo-03 falla 2 (+0)
        $planDesempate = [
            1 => [true, true],
            2 => [true, false],
            3 => [false, false],
        ];
        foreach ([0, 1] as $ronda) {
            foreach ([1, 2, 3] as $n) {
                $eq = $equipos[$n];
                $preg = $this->withSession(['equipo_token' => $eq->token_sesion])
                    ->getJson('/api/estado-juego')
                    ->assertOk()
                    ->json('pregunta');

                $acierta = $planDesempate[$n][$ronda];
                $this->withSession(['equipo_token' => $eq->token_sesion])
                    ->postJson('/api/enviar-respuesta', [
                        'pregunta_id'    => $preg['id'],
                        'opcion_elegida' => $this->letraVisualDeTexto($preg, $acierta ? 'CORRECTA_B' : 'Incorrecta A'),
                    ])
                    ->assertOk();
            }
        }

        // Telemetría /api/carrera-en-vivo con 45 equipos y orden de podio consistente (puntaje DESC, aciertos DESC, tiempo ASC)
        $live = $this->getJson('/api/carrera-en-vivo')->assertOk();
        $equiposLive = $live->json('equipos');
        $this->assertCount(45, $equiposLive);
        $this->assertSame('Equipo-01', $equiposLive[0]['nombre_equipo']);
        $this->assertSame(2800, $equiposLive[0]['puntaje_total']);
        $this->assertSame('Equipo-02', $equiposLive[1]['nombre_equipo']);
        $this->assertSame(2300, $equiposLive[1]['puntaje_total']);
        $this->assertSame(1800, $equiposLive[2]['puntaje_total']);

        // Verifica ordenamiento global de los 45 equipos
        for ($i = 1; $i < count($equiposLive); $i++) {
            $prev = $equiposLive[$i - 1];
            $curr = $equiposLive[$i];
            $this->assertGreaterThanOrEqual($curr['puntaje_total'], $prev['puntaje_total']);
            if ($prev['puntaje_total'] === $curr['puntaje_total'] && $prev['aciertos_totales'] === $curr['aciertos_totales']) {
                $this->assertLessThanOrEqual($curr['tiempo_segundos'], $prev['tiempo_segundos']);
            }
        }
    }

    public function test_presupuesto_queries_estado_juego_y_carrera_en_vivo_con_45_equipos(): void
    {
        $modulo = Modulo::create(['nombre' => 'Cloud & DevOps', 'activo' => true]);
        $nivel1 = Nivel::create([
            'nombre'             => 'Etapa 1',
            'orden_dificultad'   => 1,
            'cantidad_preguntas' => 5,
            'puntos_por_acierto' => 100,
            'tiempo_limite_seg'  => 25,
            'es_desempate'       => false,
        ]);

        foreach (range(1, 8) as $i) {
            Pregunta::create([
                'modulo_id'          => $modulo->id,
                'nivel_id'           => $nivel1->id,
                'enunciado'          => "Query Budget #{$i}",
                'opcion_a'           => 'A',
                'opcion_b'           => 'B',
                'opcion_c'           => 'C',
                'opcion_d'           => 'D',
                'respuesta_correcta' => 'a',
                'es_amena'           => $i === 1,
            ]);
        }

        ConfiguracionEvento::create([
            'nombre_evento'          => 'Presupuesto Queries 45 Equipos',
            'max_integrantes_equipo' => 5,
            'max_equipos_permitidos' => 60,
            'tiempo_total_minutos'   => 20,
            'fin_evento_at'          => now()->addMinutes(20),
            'semilla_partida'        => 'semilla_budget_45',
            'estado_juego'           => 'en_curso',
            'modulo_activo_id'       => null,
        ]);

        $equipoObjetivo = null;
        for ($n = 1; $n <= 45; $n++) {
            $eq = Equipo::create([
                'nombre_equipo'    => sprintf('Budget-%02d', $n),
                'token_sesion'     => sprintf('bgt_%02d_%s', $n, str_repeat('k', 53)),
                'pin_recuperacion' => str_pad((string) (2000 + $n), 4, '0', STR_PAD_LEFT),
                'puntaje_total'    => $n * 100,
            ]);
            Integrante::create(['equipo_id' => $eq->id, 'nombre_completo' => "Capitan Budget {$n}"]);
            if ($n === 1) {
                $equipoObjetivo = $eq;
            }
        }

        // Presupuesto GET /api/estado-juego <= 7 queries
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->withSession(['equipo_token' => $equipoObjetivo->token_sesion])
            ->getJson('/api/estado-juego')
            ->assertOk();
        $queriesEstado = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(7, count($queriesEstado), 'GET /api/estado-juego excedió el presupuesto de 7 queries.');

        // Presupuesto GET /api/carrera-en-vivo <= 4 queries incluso con 45 equipos
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->getJson('/api/carrera-en-vivo')->assertOk();
        $queriesCarrera = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(4, count($queriesCarrera), 'GET /api/carrera-en-vivo excedió el presupuesto de 4 queries.');
    }
}

