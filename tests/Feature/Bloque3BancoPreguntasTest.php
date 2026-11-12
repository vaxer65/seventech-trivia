<?php

namespace Tests\Feature;

use App\Models\ConfiguracionEvento;
use App\Models\Equipo;
use App\Models\Modulo;
use App\Models\Nivel;
use App\Models\Pregunta;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class Bloque3BancoPreguntasTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_crea_10_modulos_modo_global_y_360_preguntas_sin_duplicados_y_balanceadas(): void
    {
        $this->seed(DatabaseSeeder::class);

        // 1. 10 módulos temáticos activos
        $this->assertSame(10, Modulo::count());
        $this->assertSame(10, Modulo::where('activo', true)->count());

        // 2. Modo "Todos los Módulos" activo por defecto (modulo_activo_id = null)
        $config = ConfiguracionEvento::firstOrFail();
        $this->assertNull($config->modulo_activo_id);

        // 3. 360 preguntas en total (110 + 110 + 100 + 40)
        $this->assertSame(360, Pregunta::count());

        $niveles = Nivel::orderBy('orden_dificultad')->get()->keyBy('orden_dificultad');
        $this->assertSame(110, Pregunta::where('nivel_id', $niveles[1]->id)->count());
        $this->assertSame(110, Pregunta::where('nivel_id', $niveles[2]->id)->count());
        $this->assertSame(100, Pregunta::where('nivel_id', $niveles[3]->id)->count());
        $this->assertSame(40, Pregunta::where('nivel_id', $niveles[4]->id)->count());

        // 4. Preguntas amenas por etapa: 15 en Etapa 1, 15 en Etapa 2, 15 en Etapa 3, 0 en Muerte Súbita
        $this->assertSame(15, Pregunta::where('nivel_id', $niveles[1]->id)->where('es_amena', true)->count());
        $this->assertSame(15, Pregunta::where('nivel_id', $niveles[2]->id)->where('es_amena', true)->count());
        $this->assertSame(15, Pregunta::where('nivel_id', $niveles[3]->id)->where('es_amena', true)->count());
        $this->assertSame(0, Pregunta::where('nivel_id', $niveles[4]->id)->where('es_amena', true)->count());

        // Cada módulo tiene al menos 1 pregunta amena en cada etapa normal (1, 2 y 3)
        foreach (Modulo::all() as $modulo) {
            foreach ([1, 2, 3] as $orden) {
                $amenasModNivel = Pregunta::where('modulo_id', $modulo->id)
                    ->where('nivel_id', $niveles[$orden]->id)
                    ->where('es_amena', true)
                    ->count();
                $this->assertGreaterThanOrEqual(1, $amenasModNivel, "El módulo {$modulo->nombre} debe tener al menos 1 amena en etapa {$orden}");
            }
        }

        // 5. Balance exacto 25% de respuestas correctas (90 A, 90 B, 90 C, 90 D)
        foreach (['a', 'b', 'c', 'd'] as $letra) {
            $this->assertSame(90, Pregunta::where('respuesta_correcta', $letra)->count());
        }

        // 6. Cero enunciados duplicados
        $enunciados = Pregunta::pluck('enunciado')
            ->map(fn ($e) => mb_strtolower(trim((string) $e), 'UTF-8'))
            ->all();
        $this->assertCount(360, array_unique($enunciados));
    }

    public function test_cada_etapa_normal_entrega_exactamente_una_pregunta_amena_y_el_resto_tecnicas(): void
    {
        $this->seed(DatabaseSeeder::class);

        $config = ConfiguracionEvento::firstOrFail();
        $niveles = Nivel::where('es_desempate', false)->orderBy('orden_dificultad')->get();
        $nivelDesempate = Nivel::where('es_desempate', true)->firstOrFail();

        $equipoA = Equipo::create([
            'nombre_equipo'    => 'EquipoAlpha',
            'token_sesion'     => str_repeat('a', 60),
            'pin_recuperacion' => '1111',
        ]);
        $equipoB = Equipo::create([
            'nombre_equipo'    => 'EquipoBeta',
            'token_sesion'     => str_repeat('b', 60),
            'pin_recuperacion' => '2222',
        ]);

        foreach (['semilla_torneo_1', 'semilla_torneo_2', 'semilla_torneo_3'] as $idx => $semilla) {
            if ($idx > 0) {
                $this->travel(2)->minutes();
                $this->withSession(['es_admin_seventech' => true])
                    ->post('/admin/reiniciar')
                    ->assertRedirect();
            }

            $config->refresh()->update([
                'estado_juego'    => 'en_curso',
                'fin_evento_at'   => now()->addMinutes(15),
                'semilla_partida' => $semilla,
            ]);

            $secuenciaA = [];
            $this->withSession(['equipo_token' => $equipoA->token_sesion]);
            for ($i = 0; $i < 15; $i++) {
                $res = $this->getJson('/api/estado-juego')->assertOk()->json();
                $preguntaJson = $res['pregunta'];

                // es_amena y respuesta_correcta jamás se exponen al cliente
                $this->assertArrayNotHasKey('es_amena', $preguntaJson);
                $this->assertArrayNotHasKey('respuesta_correcta', $preguntaJson);

                $pid = (int) $preguntaJson['id'];
                $secuenciaA[] = $pid;

                $this->postJson('/api/enviar-respuesta', [
                    'pregunta_id'    => $pid,
                    'opcion_elegida' => 'a',
                ])->assertOk();
            }

            $secuenciaB = [];
            $this->withSession(['equipo_token' => $equipoB->token_sesion]);
            for ($i = 0; $i < 15; $i++) {
                $pid = (int) $this->getJson('/api/estado-juego')->assertOk()->json('pregunta.id');
                $secuenciaB[] = $pid;
                $this->postJson('/api/enviar-respuesta', [
                    'pregunta_id'    => $pid,
                    'opcion_elegida' => 'a',
                ])->assertOk();
            }

            // Ambos equipos reciben exactamente las mismas 15 preguntas en el mismo orden
            $this->assertSame($secuenciaA, $secuenciaB);

            // Verificar que en cada etapa normal (bloque de 5 preguntas) hay exactamente 1 amena y 4 técnicas
            $preguntasEntregadas = Pregunta::whereIn('id', $secuenciaA)->get()->keyBy('id');
            foreach ($niveles as $etapaIdx => $nivel) {
                $idsEtapa = array_slice($secuenciaA, $etapaIdx * 5, 5);
                $this->assertCount(5, $idsEtapa);

                $amenasEnEtapa = 0;
                foreach ($idsEtapa as $idPreg) {
                    $p = $preguntasEntregadas[$idPreg];
                    $this->assertSame($nivel->id, $p->nivel_id);
                    if ($p->es_amena) {
                        $amenasEnEtapa++;
                    }
                }

                $this->assertSame(
                    1,
                    $amenasEnEtapa,
                    "En {$nivel->nombre} con semilla {$semilla} debe entregarse exactamente 1 pregunta amena y 4 técnicas."
                );
            }
        }

        // En Muerte Súbita (desempate), las 3 preguntas deben ser 100% técnicas (es_amena = false)
        $this->travel(2)->minutes();
        $equipoA->update(['en_desempate' => true]);
        $config->update(['estado_juego' => 'desempate', 'semilla_partida' => 'semilla_desempate_pro']);

        $this->withSession(['equipo_token' => $equipoA->token_sesion]);
        for ($i = 0; $i < 3; $i++) {
            $pid = (int) $this->getJson('/api/estado-juego')->assertOk()->json('pregunta.id');
            $p = Pregunta::findOrFail($pid);
            $this->assertSame($nivelDesempate->id, $p->nivel_id);
            $this->assertFalse((bool) $p->es_amena);

            $this->postJson('/api/enviar-respuesta', [
                'pregunta_id'    => $pid,
                'opcion_elegida' => 'a',
            ])->assertOk();
        }
    }

    public function test_modo_todos_los_modulos_mezcla_preguntas_de_multiples_modulos_y_filtro_individual_funciona(): void
    {
        $this->seed(DatabaseSeeder::class);

        $config = ConfiguracionEvento::firstOrFail();
        $config->update([
            'estado_juego'     => 'en_curso',
            'fin_evento_at'    => now()->addMinutes(15),
            'semilla_partida'  => 'semilla_multimodulo_2026',
            'modulo_activo_id' => null,
        ]);

        $equipo = Equipo::create([
            'nombre_equipo'    => 'GlobalRunners',
            'token_sesion'     => str_repeat('g', 60),
            'pin_recuperacion' => '3333',
        ]);

        $idsModoGlobal = [];
        $this->withSession(['equipo_token' => $equipo->token_sesion]);
        for ($i = 0; $i < 15; $i++) {
            $pid = (int) $this->getJson('/api/estado-juego')->assertOk()->json('pregunta.id');
            $idsModoGlobal[] = $pid;
            $this->postJson('/api/enviar-respuesta', ['pregunta_id' => $pid, 'opcion_elegida' => 'a'])->assertOk();
        }

        $modulosSorteados = Pregunta::whereIn('id', $idsModoGlobal)->pluck('modulo_id')->unique();
        $this->assertGreaterThanOrEqual(3, $modulosSorteados->count(), 'El modo Todos los Módulos debe mezclar preguntas de múltiples módulos.');

        // Ahora el admin reinicia (vuelve a registro) y filtra por un módulo específico desde /admin/configuracion
        $moduloEspecifico = Modulo::where('nombre', 'Bases de Datos Relacionales & SQL')->firstOrFail();

        $this->travel(2)->minutes();
        $this->withSession(['es_admin_seventech' => true])->post('/admin/reiniciar')->assertRedirect();
        $this->withSession(['es_admin_seventech' => true])
            ->post('/admin/configuracion', [
                'estado_juego'           => 'en_curso',
                'modulo_activo_id'       => $moduloEspecifico->id,
                'max_integrantes_equipo' => 5,
                'max_equipos_permitidos' => 30,
                'tiempo_total_minutos'   => 15,
            ])->assertRedirect();

        $this->assertSame($moduloEspecifico->id, $config->fresh()->modulo_activo_id);

        $idsModuloUnico = [];
        $this->withSession(['equipo_token' => $equipo->token_sesion]);
        for ($i = 0; $i < 15; $i++) {
            $pid = (int) $this->getJson('/api/estado-juego')->assertOk()->json('pregunta.id');
            $idsModuloUnico[] = $pid;
            $this->postJson('/api/enviar-respuesta', ['pregunta_id' => $pid, 'opcion_elegida' => 'a'])->assertOk();
        }

        $modulosUnico = Pregunta::whereIn('id', $idsModuloUnico)->pluck('modulo_id')->unique();
        $this->assertCount(1, $modulosUnico);
        $this->assertSame($moduloEspecifico->id, $modulosUnico->first());

        // Volver a activar "Todos los Módulos" tras reiniciar (estado registro) enviando cadena vacía ""
        $this->withSession(['es_admin_seventech' => true])->post('/admin/reiniciar')->assertRedirect();
        $this->withSession(['es_admin_seventech' => true])
            ->post('/admin/configuracion', [
                'estado_juego'           => 'registro',
                'modulo_activo_id'       => '',
                'max_integrantes_equipo' => 5,
                'max_equipos_permitidos' => 30,
                'tiempo_total_minutos'   => 15,
            ])->assertRedirect();

        $this->assertNull($config->fresh()->modulo_activo_id);
    }

    public function test_importador_json_y_csv_crea_preguntas_omite_duplicados_y_exige_sesion_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $modulo = Modulo::firstOrFail();
        $nivel1 = Nivel::where('orden_dificultad', 1)->firstOrFail();
        $existente = Pregunta::firstOrFail();

        $jsonPayload = json_encode([
            [
                'modulo'             => $modulo->nombre,
                'orden_dificultad'   => 1,
                'enunciado'          => '¿Pregunta importada JSON única número 1?',
                'opcion_a'           => 'Opción 1A',
                'opcion_b'           => 'Opción 1B',
                'opcion_c'           => 'Opción 1C',
                'opcion_d'           => 'Opción 1D',
                'respuesta_correcta' => 'c',
                'es_amena'           => true,
            ],
            [
                'modulo_id'          => $modulo->id,
                'nivel_id'           => $nivel1->id,
                'enunciado'          => '¿Pregunta importada JSON única número 2?',
                'opcion_a'           => 'Opción 2A',
                'opcion_b'           => 'Opción 2B',
                'opcion_c'           => 'Opción 2C',
                'opcion_d'           => 'Opción 2D',
                'respuesta_correcta' => 'd',
                'es_amena'           => false,
            ],
            // Duplicada con una ya existente en BD (con mayúsculas/espacios distintos)
            [
                'modulo_id'          => $modulo->id,
                'nivel_id'           => $nivel1->id,
                'enunciado'          => '  ' . mb_strtoupper($existente->enunciado, 'UTF-8') . '  ',
                'opcion_a'           => 'A',
                'opcion_b'           => 'B',
                'opcion_c'           => 'C',
                'opcion_d'           => 'D',
                'respuesta_correcta' => 'a',
            ],
            // Duplicada dentro del mismo archivo JSON
            [
                'modulo_id'          => $modulo->id,
                'nivel_id'           => $nivel1->id,
                'enunciado'          => '¿pregunta importada json única número 1?',
                'opcion_a'           => 'A',
                'opcion_b'           => 'B',
                'opcion_c'           => 'C',
                'opcion_d'           => 'D',
                'respuesta_correcta' => 'a',
            ],
            // Inválida por respuesta_correcta fuera de a..d
            [
                'modulo_id'          => $modulo->id,
                'nivel_id'           => $nivel1->id,
                'enunciado'          => '¿Pregunta inválida?',
                'opcion_a'           => 'A',
                'opcion_b'           => 'B',
                'opcion_c'           => 'C',
                'opcion_d'           => 'D',
                'respuesta_correcta' => 'z',
            ],
        ]);

        $archivoJson = UploadedFile::fake()->createWithContent('nuevas.json', $jsonPayload);

        // Sin sesión de admin debe rechazar con 403
        $this->post('/admin/preguntas/importar', ['archivo' => $archivoJson])->assertForbidden();
        $this->assertSame(360, Pregunta::count());

        // Con sesión de admin importa las 2 válidas y omite las 3 duplicadas/inválidas
        $this->withSession(['es_admin_seventech' => true])
            ->post('/admin/preguntas/importar', ['archivo' => $archivoJson])
            ->assertRedirect()
            ->assertSessionHas('ok');

        $this->assertSame(362, Pregunta::count());
        $pJson1 = Pregunta::where('enunciado', '¿Pregunta importada JSON única número 1?')->firstOrFail();
        $this->assertTrue((bool) $pJson1->es_amena);
        $this->assertSame('c', $pJson1->getRawOriginal('respuesta_correcta'));

        // Ahora probamos importación por CSV
        $csvContent = implode("\n", [
            'modulo,orden_dificultad,enunciado,opcion_a,opcion_b,opcion_c,opcion_d,respuesta_correcta,es_amena',
            "\"{$modulo->nombre}\",2,\"¿Pregunta importada desde CSV número 1?\", Alfa, Beta, Gamma, Delta, b, 1",
            "\"{$modulo->nombre}\",3,\"¿Pregunta importada desde CSV número 2?\", Uno, Dos, Tres, Cuatro, a, 0",
            "\"{$modulo->nombre}\",1,\"¿Pregunta importada JSON única número 1?\", X, Y, Z, W, a, 0",
        ]);

        $archivoCsv = UploadedFile::fake()->createWithContent('lote.csv', $csvContent);

        $this->withSession(['es_admin_seventech' => true])
            ->post('/admin/preguntas/importar', ['archivo' => $archivoCsv])
            ->assertRedirect()
            ->assertSessionHas('ok');

        $this->assertSame(364, Pregunta::count());
        $pCsv1 = Pregunta::where('enunciado', '¿Pregunta importada desde CSV número 1?')->firstOrFail();
        $this->assertTrue((bool) $pCsv1->es_amena);
        $this->assertSame('b', $pCsv1->getRawOriginal('respuesta_correcta'));
    }
}
