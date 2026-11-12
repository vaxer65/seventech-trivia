<?php

namespace Tests\Feature;

use App\Models\ConfiguracionEvento;
use App\Models\Entrega;
use App\Models\Equipo;
use App\Models\Modulo;
use App\Models\Nivel;
use App\Models\Pregunta;
use App\Models\RespuestaEquipo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Bloque1SeguridadTest extends TestCase
{
    use RefreshDatabase;

    private Modulo $modulo;
    private Nivel $nivel1;
    private Nivel $nivel3;
    private ConfiguracionEvento $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulo = Modulo::create(['nombre' => 'Tech', 'activo' => true]);
        $this->config = ConfiguracionEvento::create([
            'nombre_evento'          => 'Seven Tech Test',
            'max_integrantes_equipo' => 5,
            'max_equipos_permitidos' => 30,
            'tiempo_total_minutos'   => 15,
            'fin_evento_at'          => now()->addMinutes(15),
            'semilla_partida'        => 'semilla_fija_test',
            'estado_juego'           => 'en_curso',
            'modulo_activo_id'       => $this->modulo->id,
        ]);

        $this->nivel1 = Nivel::create([
            'nombre' => 'Nivel 1', 'orden_dificultad' => 1, 'cantidad_preguntas' => 2,
            'puntos_por_acierto' => 100, 'tiempo_limite_seg' => 25, 'es_desempate' => false,
        ]);
        $this->nivel3 = Nivel::create([
            'nombre' => 'Nivel 3', 'orden_dificultad' => 3, 'cantidad_preguntas' => 2,
            'puntos_por_acierto' => 300, 'tiempo_limite_seg' => 25, 'es_desempate' => false,
        ]);

        foreach (range(1, 4) as $i) {
            Pregunta::create([
                'modulo_id' => $this->modulo->id, 'nivel_id' => $this->nivel1->id,
                'enunciado' => "P1-{$i}", 'opcion_a' => 'A', 'opcion_b' => 'B',
                'opcion_c' => 'C', 'opcion_d' => 'D', 'respuesta_correcta' => 'b',
            ]);
            Pregunta::create([
                'modulo_id' => $this->modulo->id, 'nivel_id' => $this->nivel3->id,
                'enunciado' => "P3-{$i}", 'opcion_a' => 'A', 'opcion_b' => 'B',
                'opcion_c' => 'C', 'opcion_d' => 'D', 'respuesta_correcta' => 'b',
            ]);
        }
    }

    private function crearEquipoAutenticado(string $nombre = 'Alfa'): Equipo
    {
        $eq = Equipo::create([
            'nombre_equipo'    => $nombre,
            'token_sesion'     => substr(str_repeat('a', 50) . $nombre, 0, 60),
            'pin_recuperacion' => '4829',
        ]);
        $this->withSession(['equipo_token' => $eq->token_sesion]);
        return $eq;
    }

    private function letraVisualParaTexto(array $preguntaJson, string $textoBuscado = 'B'): string
    {
        foreach (['a', 'b', 'c', 'd'] as $letra) {
            if (($preguntaJson["opcion_{$letra}"] ?? null) === $textoBuscado) {
                return $letra;
            }
        }
        return 'b';
    }

    // T1: no se puede responder una pregunta que no sea la asignada al equipo
    public function test_t1_rechaza_pregunta_no_asignada_sin_sumar_puntos(): void
    {
        $equipo = $this->crearEquipoAutenticado();
        $asignadaId = $this->getJson('/api/estado-juego')->assertOk()->json('pregunta.id');

        $preguntaNivel3 = Pregunta::where('nivel_id', $this->nivel3->id)->first();
        $this->assertNotEquals($asignadaId, $preguntaNivel3->id);

        $this->postJson('/api/enviar-respuesta', [
            'pregunta_id'    => $preguntaNivel3->id,
            'opcion_elegida' => 'b',
        ])->assertStatus(409)->assertJsonPath('resync', true);

        $this->assertSame(0, $equipo->fresh()->puntaje_total);
        $this->assertSame(0, RespuestaEquipo::count());
    }

    // T2: responder sin haber recibido la pregunta (sin fila en entregas) devuelve 409
    public function test_t2_rechaza_respuesta_sin_entrega_previa(): void
    {
        $equipo = $this->crearEquipoAutenticado();

        // Obtenemos cuál sería la pregunta asignada y luego borramos el registro de entrega
        $preguntaId = $this->getJson('/api/estado-juego')->json('pregunta.id');
        Entrega::where('equipo_id', $equipo->id)->delete();

        $this->postJson('/api/enviar-respuesta', [
            'pregunta_id'    => $preguntaId,
            'opcion_elegida' => 'b',
        ])->assertStatus(409)->assertJsonPath('resync', true);

        $this->assertSame(0, $equipo->fresh()->puntaje_total);
    }

    // T2: el tiempo lo mide el servidor, recargar no reinicia el cronómetro y se aplica el límite
    public function test_t2_calcula_tiempo_en_servidor_y_aplica_limite(): void
    {
        $equipo = $this->crearEquipoAutenticado();

        $this->travelTo(now());
        $p1Json = $this->getJson('/api/estado-juego')->json('pregunta');
        $p1 = $p1Json['id'];
        $letraCorrectaP1 = $this->letraVisualParaTexto($p1Json, 'B');

        // Recargar a los 2 s no debe mover entregada_at
        $this->travel(2)->seconds();
        $this->getJson('/api/estado-juego')->assertJsonPath('pregunta.id', $p1);

        // Responder a los 3,4 s totales intentando mentir con tiempo_respuesta_ms=100
        $this->travel(1400)->milliseconds();
        $this->postJson('/api/enviar-respuesta', [
            'pregunta_id'         => $p1,
            'opcion_elegida'      => $letraCorrectaP1,
            'tiempo_respuesta_ms' => 100,
        ])->assertOk()->assertJsonPath('es_correcta', true);

        $equipo->refresh();
        $this->assertSame(100, $equipo->puntaje_total);
        $this->assertEqualsWithDelta(3400, $equipo->tiempo_acumulado_ms, 80);

        // Siguiente pregunta respondida fuera del límite (25 s + 1,5 s de gracia) -> 0 puntos
        $p2Json = $this->getJson('/api/estado-juego')->json('pregunta');
        $p2 = $p2Json['id'];
        $letraCorrectaP2 = $this->letraVisualParaTexto($p2Json, 'B');
        $this->travel(30)->seconds();

        $this->postJson('/api/enviar-respuesta', [
            'pregunta_id'    => $p2,
            'opcion_elegida' => $letraCorrectaP2,
        ])->assertOk()
          ->assertJsonPath('es_correcta', false)
          ->assertJsonPath('fuera_de_tiempo', true)
          ->assertJsonPath('puntos_sumados', 0);

        $equipo->refresh();
        $this->assertSame(100, $equipo->puntaje_total);
        $this->assertSame(2, $equipo->preguntas_contestadas);
    }

    // T3: no se puede responder en pausa ni cuando venció el reloj global
    public function test_t3_rechaza_respuestas_en_pausa_o_con_reloj_vencido(): void
    {
        $equipo = $this->crearEquipoAutenticado();
        $preguntaId = $this->getJson('/api/estado-juego')->json('pregunta.id');

        $this->config->update(['estado_juego' => 'pausado']);
        $this->postJson('/api/enviar-respuesta', [
            'pregunta_id' => $preguntaId, 'opcion_elegida' => 'b',
        ])->assertStatus(409);

        $this->config->update([
            'estado_juego'  => 'en_curso',
            'fin_evento_at' => now()->subSecond(),
        ]);
        $this->postJson('/api/enviar-respuesta', [
            'pregunta_id' => $preguntaId, 'opcion_elegida' => 'b',
        ])->assertStatus(409);

        $this->assertSame('finalizado', $this->config->fresh()->estado_juego);
        $this->assertSame(0, $equipo->fresh()->puntaje_total);
    }

    // T5: reenviar una pregunta ya contestada devuelve 200 sin duplicar puntaje ni dar 500
    public function test_t5_reenvio_de_pregunta_contestada_no_duplica_ni_da_500(): void
    {
        $equipo = $this->crearEquipoAutenticado();
        $p1Json = $this->getJson('/api/estado-juego')->json('pregunta');
        $p1 = $p1Json['id'];
        $letraCorrecta = $this->letraVisualParaTexto($p1Json, 'B');

        $this->postJson('/api/enviar-respuesta', ['pregunta_id' => $p1, 'opcion_elegida' => $letraCorrecta])
            ->assertOk()->assertJsonPath('puntos_sumados', 100);

        // El cliente reintenta enviar la misma pregunta (aunque el servidor ya avanzó a p2)
        $this->postJson('/api/enviar-respuesta', ['pregunta_id' => $p1, 'opcion_elegida' => $letraCorrecta])
            ->assertOk()
            ->assertJsonPath('duplicada', true)
            ->assertJsonPath('puntos_sumados', 0);

        $equipo->refresh();
        $this->assertSame(100, $equipo->puntaje_total);
        $this->assertSame(1, $equipo->preguntas_contestadas);
        $this->assertSame(1, RespuestaEquipo::where('equipo_id', $equipo->id)->count());
    }

    // S1 + R1: clave antigua rechazada, hash desde config y throttle en login admin y PIN
    public function test_s1_r1_login_admin_hash_y_rate_limit_pin_por_equipo(): void
    {
        config(['app.admin_password_hash' => Hash::make('ClaveNuevaSegura!')]);

        // La clave fija antigua ya no entra
        $this->post('/admin/login', ['password' => 'seventech2026'])
            ->assertSessionHasErrors('password');
        $this->assertFalse((bool) session('es_admin_seventech'));

        // La clave configurada sí entra
        $this->post('/admin/login', ['password' => 'ClaveNuevaSegura!'])
            ->assertRedirect(route('admin.index'));
        $this->assertTrue((bool) session('es_admin_seventech'));

        // PIN de 4 dígitos: 5 intentos fallidos por equipo; el 6° recibe 429 aunque cambie la sesión
        $this->crearEquipoAutenticado('Objetivo');
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/recuperar-sesion', [
                'nombre_equipo' => 'Objetivo', 'pin_recuperacion' => '0000',
            ])->assertStatus(422);
        }
        $this->postJson('/api/recuperar-sesion', [
            'nombre_equipo' => 'Objetivo', 'pin_recuperacion' => '4829',
        ])->assertStatus(429);

        // Otro equipo en la misma IP NO queda bloqueado por los intentos contra "Objetivo"
        $this->crearEquipoAutenticado('OtroEquipo');
        $this->postJson('/api/recuperar-sesion', [
            'nombre_equipo' => 'OtroEquipo', 'pin_recuperacion' => '4829',
        ])->assertOk();
    }

    // Semilla oculta en espectador, ruta duplicada eliminada, simulador oculto y seeder funcional
    public function test_semilla_oculta_ruta_duplicada_simulador_y_seeder(): void
    {
        $datos = $this->getJson('/api/carrera-en-vivo')->assertOk()->json('evento');
        $this->assertArrayNotHasKey('semilla_partida', $datos);

        $this->postJson('/api/responder', [])->assertNotFound();

        // En testing (no local), el simulador devuelve 404 incluso siendo admin
        $this->withSession(['es_admin_seventech' => true])
            ->post('/admin/simular-equipos')
            ->assertNotFound();

        // El seeder corre completo sin usar la contraseña fija antigua
        RespuestaEquipo::query()->delete();
        Pregunta::query()->delete();
        Nivel::query()->delete();
        ConfiguracionEvento::query()->delete();
        Modulo::query()->delete();

        $this->seed(DatabaseSeeder::class);
        $admin = \App\Models\User::where('email', 'admin@seventech.com')->firstOrFail();
        $this->assertFalse(Hash::check('seventech2026', $admin->password));
    }

    // Bloque 2 - S3: validación de configuración/niveles y compensación del reloj al pausar y reanudar
    public function test_s3_validacion_admin_y_reloj_global_congelado_en_pausa(): void
    {
        $this->withSession(['es_admin_seventech' => true]);

        // Estado inválido o límites fuera de rango deben ser rechazados con 302 (errores de validación)
        $this->post('/admin/configuracion', [
            'estado_juego'           => 'hackeado',
            'max_integrantes_equipo' => 0,
            'max_equipos_permitidos' => -5,
        ])->assertSessionHasErrors(['estado_juego', 'max_integrantes_equipo', 'max_equipos_permitidos']);

        $this->post("/admin/nivel/{$this->nivel1->id}", [
            'nombre'             => 'Nivel 1',
            'cantidad_preguntas' => 0,
            'puntos_por_acierto' => -10,
            'tiempo_limite_seg'  => 1,
        ])->assertSessionHasErrors(['cantidad_preguntas', 'puntos_por_acierto', 'tiempo_limite_seg']);

        // Pausar la carrera durante 60 segundos y reanudar debe desplazar fin_evento_at +60s
        $this->travelTo(now());
        $restantesAntes = $this->config->fresh()->segundos_globales_restantes;

        $this->post('/admin/configuracion', [
            'estado_juego'           => 'pausado',
            'modulo_activo_id'       => $this->modulo->id,
            'max_integrantes_equipo' => 5,
            'max_equipos_permitidos' => 30,
            'tiempo_total_minutos'   => 15,
        ])->assertSessionHasNoErrors();

        $this->assertNotNull($this->config->fresh()->pausado_at);

        // Pasan 60 segundos en pausa
        $this->travel(60)->seconds();

        // Se reanuda a en_curso
        $this->post('/admin/configuracion', [
            'estado_juego'           => 'en_curso',
            'modulo_activo_id'       => $this->modulo->id,
            'max_integrantes_equipo' => 5,
            'max_equipos_permitidos' => 30,
            'tiempo_total_minutos'   => 15,
        ])->assertSessionHasNoErrors();

        $configReanudada = $this->config->fresh();
        $this->assertNull($configReanudada->pausado_at);
        $this->assertEqualsWithDelta($restantesAntes, $configReanudada->segundos_globales_restantes, 2);
    }

    // Bloque 3 - S2, S4, C2, C5: middleware admin, mass assignment acotado, queries agrupadas y cupo transaccional
    public function test_bloque3_middleware_admin_mass_assignment_queries_y_cupo(): void
    {
        // S2: Todas las rutas POST de admin devuelven 403 sin sesión
        $rutasAdmin = [
            '/admin/configuracion',
            "/admin/nivel/{$this->nivel1->id}",
            '/admin/modulo',
            '/admin/pregunta',
            '/admin/desempate',
            '/admin/reiniciar',
            '/admin/pregunta/1/editar',
            '/admin/pregunta/1/eliminar',
            '/admin/modulo/1/eliminar',
            '/admin/equipo/1/eliminar',
            '/admin/historial/guardar',
            '/admin/historial/1/eliminar',
        ];
        foreach ($rutasAdmin as $ruta) {
            $this->post($ruta, [])->assertForbidden();
        }

        // S4: crearPregunta solo persiste los campos validados (ignora activa=false inyectado)
        $this->withSession(['es_admin_seventech' => true])
            ->post('/admin/pregunta', [
                'modulo_id'          => $this->modulo->id,
                'nivel_id'           => $this->nivel1->id,
                'enunciado'          => 'Pregunta segura',
                'opcion_a'           => '1',
                'opcion_b'           => '2',
                'opcion_c'           => '3',
                'opcion_d'           => '4',
                'respuesta_correcta' => 'a',
                'activa'             => false,
            ])->assertSessionHasNoErrors();

        $creada = Pregunta::where('enunciado', 'Pregunta segura')->firstOrFail();
        $this->assertTrue((bool) $creada->activa);

        // C2: obtenerEstadoYPregunta agrupa el conteo por nivel en una sola consulta (sin N+1)
        $this->crearEquipoAutenticado('Veloces');
        \Illuminate\Support\Facades\DB::enableQueryLog();
        $this->getJson('/api/estado-juego')->assertOk();
        $totalQueries = count(\Illuminate\Support\Facades\DB::getQueryLog());
        \Illuminate\Support\Facades\DB::disableQueryLog();
        $this->assertLessThanOrEqual(7, $totalQueries);

        // C5: cupo máximo respetado en registrarEquipo
        $this->config->update(['max_equipos_permitidos' => 1]);
        $this->postJson('/api/registrar-equipo', [
            'nombre_equipo' => 'Equipo Extra',
            'integrantes'   => ['Juan Perez', 'Maria Gomez'],
        ])->assertStatus(403);
    }

    // Pedido 1 (JUSTICIA): todos los equipos reciben la misma primera pregunta y la misma secuencia; reiniciar cambia el orden
    public function test_justicia_misma_secuencia_entre_equipos_y_cambio_al_reiniciar(): void
    {
        // Creamos más preguntas en nivel 1 para que el orden dependa de semilla_partida
        foreach (range(5, 16) as $i) {
            Pregunta::create([
                'modulo_id' => $this->modulo->id, 'nivel_id' => $this->nivel1->id,
                'enunciado' => "P1-extra-{$i}", 'opcion_a' => 'A', 'opcion_b' => 'B',
                'opcion_c' => 'C', 'opcion_d' => 'D', 'respuesta_correcta' => 'b',
            ]);
        }
        $this->nivel1->update(['cantidad_preguntas' => 5]);

        $equipoA = Equipo::create(['nombre_equipo' => 'EquipoA', 'token_sesion' => str_repeat('a', 60), 'pin_recuperacion' => '1111']);
        $equipoB = Equipo::create(['nombre_equipo' => 'EquipoB', 'token_sesion' => str_repeat('b', 60), 'pin_recuperacion' => '2222']);

        $secuenciaA = [];
        $this->withSession(['equipo_token' => $equipoA->token_sesion]);
        for ($i = 0; $i < 5; $i++) {
            $pid = $this->getJson('/api/estado-juego')->assertOk()->json('pregunta.id');
            $secuenciaA[] = $pid;
            $this->postJson('/api/enviar-respuesta', ['pregunta_id' => $pid, 'opcion_elegida' => 'b'])->assertOk();
        }

        $secuenciaB = [];
        $this->withSession(['equipo_token' => $equipoB->token_sesion]);
        for ($i = 0; $i < 5; $i++) {
            $pid = $this->getJson('/api/estado-juego')->assertOk()->json('pregunta.id');
            $secuenciaB[] = $pid;
            $this->postJson('/api/enviar-respuesta', ['pregunta_id' => $pid, 'opcion_elegida' => 'b'])->assertOk();
        }

        // Ambos equipos reciben exactamente la misma primera pregunta y la misma secuencia completa
        $this->assertSame($secuenciaA[0], $secuenciaB[0]);
        $this->assertSame($secuenciaA, $secuenciaB);

        // Reiniciar partida cambia semilla_partida y cambia la secuencia sorteada
        $semillaAnterior = $this->config->fresh()->semilla_partida;
        $this->withSession(['es_admin_seventech' => true])
            ->post('/admin/reiniciar')
            ->assertRedirect();

        $this->config->refresh();
        $this->assertNotSame($semillaAnterior, $this->config->semilla_partida);

        $this->config->update(['estado_juego' => 'en_curso', 'fin_evento_at' => now()->addMinutes(15)]);
        $secuenciaNueva = [];
        $this->withSession(['equipo_token' => $equipoA->token_sesion]);
        for ($i = 0; $i < 5; $i++) {
            $pid = $this->getJson('/api/estado-juego')->assertOk()->json('pregunta.id');
            $secuenciaNueva[] = $pid;
            $this->postJson('/api/enviar-respuesta', ['pregunta_id' => $pid, 'opcion_elegida' => 'b'])->assertOk();
        }

        $this->assertNotSame($secuenciaA, $secuenciaNueva);
    }
}

