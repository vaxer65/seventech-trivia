<?php

namespace Tests\Feature;

use App\Http\Controllers\ConcursanteController;
use App\Models\ConfiguracionEvento;
use App\Models\Equipo;
use App\Models\Modulo;
use App\Models\Nivel;
use App\Models\Pregunta;
use App\Models\RespuestaEquipo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Bloque2AlgoritmoBancoTest extends TestCase
{
    use RefreshDatabase;

    private Modulo $modulo;
    private Nivel $nivel1;
    private ConfiguracionEvento $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulo = Modulo::create(['nombre' => 'Comunidad Tech', 'activo' => true]);
        $this->config = ConfiguracionEvento::create([
            'nombre_evento'          => 'Seven Tech Test Bloque 2',
            'max_integrantes_equipo' => 5,
            'max_equipos_permitidos' => 30,
            'tiempo_total_minutos'   => 15,
            'fin_evento_at'          => now()->addMinutes(15),
            'semilla_partida'        => 'semilla_bloque2_init',
            'estado_juego'           => 'en_curso',
            'modulo_activo_id'       => $this->modulo->id,
        ]);

        $this->nivel1 = Nivel::create([
            'nombre' => 'Etapa 1', 'orden_dificultad' => 1, 'cantidad_preguntas' => 5,
            'puntos_por_acierto' => 100, 'tiempo_limite_seg' => 25, 'es_desempate' => false,
        ]);
    }

    // 1. Las preguntas nuevas recién creadas entran en el sorteo SHA2+CRC32 y todos los equipos ven la misma secuencia
    public function test_preguntas_nuevas_entran_en_el_sorteo_y_todos_los_equipos_reciben_misma_secuencia(): void
    {
        $idsIniciales = [];
        foreach (range(1, 18) as $i) {
            $p = Pregunta::create([
                'modulo_id' => $this->modulo->id, 'nivel_id' => $this->nivel1->id,
                'enunciado' => "Inicial-{$i}", 'opcion_a' => 'OpA', 'opcion_b' => 'OpB',
                'opcion_c' => 'OpC', 'opcion_d' => 'OpD', 'respuesta_correcta' => 'b',
            ]);
            $idsIniciales[] = $p->id;
        }

        // El admin agrega 6 preguntas nuevas al módulo activo
        $idsNuevas = [];
        foreach (range(1, 6) as $i) {
            $p = Pregunta::create([
                'modulo_id' => $this->modulo->id, 'nivel_id' => $this->nivel1->id,
                'enunciado' => "Nueva-{$i}", 'opcion_a' => 'OpA', 'opcion_b' => 'OpB',
                'opcion_c' => 'OpC', 'opcion_d' => 'OpD', 'respuesta_correcta' => 'b',
            ]);
            $idsNuevas[] = $p->id;
        }

        $eq1 = Equipo::create(['nombre_equipo' => 'Alpha', 'token_sesion' => str_repeat('a', 60), 'pin_recuperacion' => '1111']);
        $eq2 = Equipo::create(['nombre_equipo' => 'Beta',  'token_sesion' => str_repeat('b', 60), 'pin_recuperacion' => '2222']);

        $aparicionesNuevas = 0;
        $totalPartidas = 15;

        for ($partida = 1; $partida <= $totalPartidas; $partida++) {
            $this->travel(2)->minutes();

            $this->withSession(['es_admin_seventech' => true])
                ->post('/admin/reiniciar')
                ->assertRedirect();

            $this->withSession(['es_admin_seventech' => true])
                ->post('/admin/configuracion', [
                    'estado_juego'           => 'en_curso',
                    'modulo_activo_id'       => $this->modulo->id,
                    'max_integrantes_equipo' => 5,
                    'max_equipos_permitidos' => 30,
                    'tiempo_total_minutos'   => 15,
                ])->assertRedirect();

            $secuencia1 = [];
            $this->withSession(['equipo_token' => $eq1->token_sesion]);
            for ($q = 0; $q < 5; $q++) {
                $pid = $this->getJson('/api/estado-juego')->assertOk()->json('pregunta.id');
                $secuencia1[] = $pid;
                $this->postJson('/api/enviar-respuesta', ['pregunta_id' => $pid, 'opcion_elegida' => 'a'])->assertOk();
            }

            $secuencia2 = [];
            $this->withSession(['equipo_token' => $eq2->token_sesion]);
            for ($q = 0; $q < 5; $q++) {
                $pid = $this->getJson('/api/estado-juego')->assertOk()->json('pregunta.id');
                $secuencia2[] = $pid;
                $this->postJson('/api/enviar-respuesta', ['pregunta_id' => $pid, 'opcion_elegida' => 'a'])->assertOk();
            }

            // En cada partida, ambos equipos reciben exactamente las mismas 5 preguntas en el mismo orden
            $this->assertSame($secuencia1, $secuencia2);

            foreach ($secuencia1 as $idSorteado) {
                if (in_array($idSorteado, $idsNuevas, true)) {
                    $aparicionesNuevas++;
                }
            }
        }

        // Con 6 preguntas nuevas sobre un total de 24 (25% del banco) y 75 extracciones (15 partidas x 5 preguntas),
        // el valor esperado es ~18.75 apariciones; verificamos que salgan con frecuencia real (> 8 veces)
        $this->assertGreaterThanOrEqual(8, $aparicionesNuevas);
    }

    // 2. Opciones barajadas deterministas al recargar (F5), distintas entre equipos, opacas en JSON y calificación exacta
    public function test_opciones_barajadas_son_deterministas_en_recarga_y_diferentes_entre_equipos(): void
    {
        Pregunta::create([
            'modulo_id'          => $this->modulo->id,
            'nivel_id'           => $this->nivel1->id,
            'enunciado'          => '¿Cuál es el texto correcto?',
            'opcion_a'           => 'Distractor Uno',
            'opcion_b'           => 'Respuesta Correcta Oficial',
            'opcion_c'           => 'Distractor Dos',
            'opcion_d'           => 'Distractor Tres',
            'respuesta_correcta' => 'b',
        ]);

        $equipos = [];
        for ($i = 1; $i <= 10; $i++) {
            $equipos[] = Equipo::create([
                'nombre_equipo'    => "Equipo-{$i}",
                'token_sesion'     => str_pad("tok_{$i}_", 60, 'x'),
                'pin_recuperacion' => '1234',
            ]);
        }

        // El Equipo 1 consulta y recarga 3 veces: el orden de opcion_a..d debe ser 100% idéntico (determinista)
        $this->withSession(['equipo_token' => $equipos[0]->token_sesion]);
        $primeraLectura = $this->getJson('/api/estado-juego')->assertOk()->json('pregunta');
        $segundaLectura = $this->getJson('/api/estado-juego')->assertOk()->json('pregunta');
        $terceraLectura = $this->getJson('/api/estado-juego')->assertOk()->json('pregunta');

        $this->assertSame($primeraLectura['opcion_a'], $segundaLectura['opcion_a']);
        $this->assertSame($primeraLectura['opcion_b'], $segundaLectura['opcion_b']);
        $this->assertSame($primeraLectura['opcion_c'], $segundaLectura['opcion_c']);
        $this->assertSame($primeraLectura['opcion_d'], $segundaLectura['opcion_d']);
        $this->assertSame($segundaLectura, $terceraLectura);

        // El JSON no debe exponer respuesta_correcta ni claves internas de mapeo
        $this->assertArrayNotHasKey('respuesta_correcta', $primeraLectura);
        $this->assertArrayNotHasKey('opciones_barajadas', $primeraLectura);

        // Entre los 10 equipos deben observarse distintos órdenes de visualización para la misma pregunta
        $ordenesVistos = [];
        foreach ($equipos as $eq) {
            $this->withSession(['equipo_token' => $eq->token_sesion]);
            $pJson = $this->getJson('/api/estado-juego')->assertOk()->json('pregunta');
            $firmaOrden = implode('|', [$pJson['opcion_a'], $pJson['opcion_b'], $pJson['opcion_c'], $pJson['opcion_d']]);
            $ordenesVistos[$firmaOrden] = true;

            // Encontrar en qué letra visual ('a'..'d') cayó 'Respuesta Correcta Oficial' para este equipo y enviarla
            $letraVisualCorrecta = null;
            foreach (['a', 'b', 'c', 'd'] as $letra) {
                if ($pJson["opcion_{$letra}"] === 'Respuesta Correcta Oficial') {
                    $letraVisualCorrecta = $letra;
                    break;
                }
            }
            $this->assertNotNull($letraVisualCorrecta);

            $this->postJson('/api/enviar-respuesta', [
                'pregunta_id'    => $pJson['id'],
                'opcion_elegida' => $letraVisualCorrecta,
            ])->assertOk()
              ->assertJsonPath('es_correcta', true)
              ->assertJsonPath('puntos_sumados', 100);

            // En la BD queda registrada la clave original 'b' como opción elegida
            $registro = RespuestaEquipo::where('equipo_id', $eq->id)->where('pregunta_id', $pJson['id'])->firstOrFail();
            $this->assertSame('b', $registro->opcion_elegida);
            $this->assertTrue((bool) $registro->es_correcta);
        }

        $this->assertGreaterThan(1, count($ordenesVistos));
    }

    // 3. Distribución matemática ~25% por cada letra visual A/B/C/D incluso si todas las preguntas tienen 'b' en la BD
    public function test_distribucion_uniforme_25_por_ciento_en_posiciones_visuales_a_b_c_d(): void
    {
        $conteoPosicionVisual = ['a' => 0, 'b' => 0, 'c' => 0, 'd' => 0];
        $letrasVisuales = ['a', 'b', 'c', 'd'];
        $totalMuestras = 2400;

        for ($i = 1; $i <= $totalMuestras; $i++) {
            $equipoId = ($i % 40) + 1;
            $preguntaId = (int) floor($i / 40) + 1;
            $semilla = 'partida_semilla_' . (($i % 12) + 1);

            // Supongamos que en la BD la respuesta correcta siempre es 'b'
            $perm = ConcursanteController::obtenerPermutacionOpciones($preguntaId, $equipoId, $semilla);
            $indiceDondeCayoB = array_search('b', $perm, true);
            $letraVisualMostrada = $letrasVisuales[$indiceDondeCayoB];

            $conteoPosicionVisual[$letraVisualMostrada]++;
        }

        // Cada una de las 4 letras visuales (a, b, c, d) debe recibir ~25% (600 de 2400, tolerancia ±3.5% -> [516, 684])
        foreach (['a', 'b', 'c', 'd'] as $letra) {
            $proporcion = $conteoPosicionVisual[$letra] / $totalMuestras;
            $this->assertGreaterThan(0.215, $proporcion, "La posición visual '{$letra}' tuvo proporción {$proporcion}");
            $this->assertLessThan(0.285, $proporcion, "La posición visual '{$letra}' tuvo proporción {$proporcion}");
        }
    }
}
