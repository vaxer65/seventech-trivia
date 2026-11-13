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

class FlujoCompletoE2ETest extends TestCase
{
    use RefreshDatabase;

    private function elegirOpcionVisualCorrecta(array $preguntaJson): string
    {
        $modelo = Pregunta::findOrFail($preguntaJson['id']);
        $textoCorrecto = $modelo->{'opcion_' . $modelo->respuesta_correcta};

        $letraVisual = collect(['a', 'b', 'c', 'd'])
            ->first(fn ($letra) => ($preguntaJson['opcion_' . $letra] ?? null) === $textoCorrecto);

        $this->assertNotNull($letraVisual, "No se encontró el texto correcto en las opciones barajadas de pregunta #{$modelo->id}.");

        return $letraVisual;
    }

    public function test_flujo_completo_e2e_torneo_seeder_360_etapas_anticheat_desempate_y_admin(): void
    {
        // 1. Sembrar banco completo oficial (10 módulos + 360 preguntas + 4 niveles) en seventech_trivia_test
        $this->seed(DatabaseSeeder::class);

        $this->assertEquals(10, Modulo::count());
        $this->assertEquals(360, Pregunta::count());
        $this->assertEquals(4, Nivel::count());

        // 2. Verificar vistas públicas principales
        $this->get('/')->assertOk()->assertSee('Seven Tech');
        $this->get('/espectador')->assertOk()->assertSee('TELEMETRÍA DE CARRERA EN TIEMPO REAL');
        $this->get('/jugar')->assertOk()->assertSee('Seven Tech Community');

        // 3. Admin descarga plantillas e importa preguntas nuevas vía JSON y CSV con deduplicación
        $adminSession = ['es_admin_seventech' => true];

        $this->withSession($adminSession)
            ->get('/admin/preguntas/plantilla/csv')
            ->assertOk();

        $this->withSession($adminSession)
            ->get('/admin/preguntas/plantilla/json')
            ->assertOk();

        $moduloBackend = Modulo::where('nombre', 'like', '%Backend%')->firstOrFail();
        $nivel1 = Nivel::where('orden_dificultad', 1)->where('es_desempate', false)->firstOrFail();

        $jsonImport = UploadedFile::fake()->createWithContent(
            'extra_e2e.json',
            json_encode([
                [
                    'modulo'             => $moduloBackend->nombre,
                    'nivel'              => $nivel1->orden_dificultad,
                    'enunciado'          => '¿Qué comando de Artisan limpia todas las cachés compiladas en Laravel?',
                    'opcion_a'           => 'php artisan optimize:clear',
                    'opcion_b'           => 'php artisan cache:boot',
                    'opcion_c'           => 'php artisan route:seed',
                    'opcion_d'           => 'php artisan db:drop',
                    'respuesta_correcta' => 'a',
                    'es_amena'           => false,
                ],
            ], JSON_UNESCAPED_UNICODE)
        );

        $this->withSession($adminSession)
            ->post('/admin/preguntas/importar', ['archivo' => $jsonImport])
            ->assertSessionHasNoErrors();

        $csvImport = UploadedFile::fake()->createWithContent(
            'extra_e2e.csv',
            "modulo,nivel,enunciado,opcion_a,opcion_b,opcion_c,opcion_d,respuesta_correcta,es_amena\n" .
            "\"{$moduloBackend->nombre}\",{$nivel1->orden_dificultad},\"¿Qué puerto estándar utiliza HTTPS sobre TLS?\",80,22,443,3306,c,0\n"
        );

        $this->withSession($adminSession)
            ->post('/admin/preguntas/importar', ['archivo' => $csvImport])
            ->assertSessionHasNoErrors();

        $this->assertEquals(362, Pregunta::count());

        // 4. Registro de 2 equipos desde /api/registrar-equipo
        $this->postJson('/api/registrar-equipo', [
            'nombre_equipo' => 'Escuderia Alpha',
            'integrantes'   => ['Ana Pérez', 'Luis Gómez'],
        ])->assertOk()->assertJsonPath('equipo.nombre_equipo', 'Escuderia Alpha');

        $this->postJson('/api/registrar-equipo', [
            'nombre_equipo' => 'Escuderia Beta',
            'integrantes'   => ['Carlos Ruiz', 'Sofía Díaz'],
        ])->assertOk()->assertJsonPath('equipo.nombre_equipo', 'Escuderia Beta');

        $equipoA = Equipo::where('nombre_equipo', 'Escuderia Alpha')->firstOrFail();
        $equipoB = Equipo::where('nombre_equipo', 'Escuderia Beta')->firstOrFail();

        // 5. Admin inicia el torneo ("en_curso") con configuración manual
        $config = ConfiguracionEvento::firstOrFail();
        $this->withSession($adminSession)
            ->post('/admin/configuracion', [
                'estado_juego'           => 'en_curso',
                'modulo_activo_id'       => '',
                'max_integrantes_equipo' => $config->max_integrantes_equipo,
                'max_equipos_permitidos' => $config->max_equipos_permitidos,
                'tiempo_total_minutos'   => 15,
            ])
            ->assertSessionHasNoErrors();

        // 6. Equipos juegan las 3 etapas normales (5 + 5 + 5 = 15 preguntas cada uno)
        $nivelesNormales = Nivel::where('es_desempate', false)->orderBy('orden_dificultad')->get();
        $totalPreguntasNormales = (int) $nivelesNormales->sum('cantidad_preguntas');
        $this->assertEquals(15, $totalPreguntasNormales);

        for ($i = 0; $i < $totalPreguntasNormales; $i++) {
            $estadoA = $this->withSession(['equipo_token' => $equipoA->token_sesion])
                ->getJson('/api/estado-juego')
                ->assertOk()
                ->assertJsonPath('autenticado', true)
                ->assertJsonPath('estado_juego', 'en_curso');

            $pregDataA = $estadoA->json('pregunta');
            $opcionVisualA = $this->elegirOpcionVisualCorrecta($pregDataA);

            $estadoB = $this->withSession(['equipo_token' => $equipoB->token_sesion])
                ->getJson('/api/estado-juego')
                ->assertOk()
                ->assertJsonPath('autenticado', true);
            $pregB = $estadoB->json('pregunta');

            // En la pregunta #1 probamos el escudo anti-IA / salida de pantalla sobre Equipo B
            if ($i === 0) {
                $this->withSession(['equipo_token' => $equipoB->token_sesion])
                    ->postJson('/api/reportar-salida-pantalla', [
                        'pregunta_id' => $pregB['id'],
                    ])
                    ->assertOk()
                    ->assertJsonPath('bloqueada', true);
            } else {
                $opcionVisualB = $this->elegirOpcionVisualCorrecta($pregB);

                $this->withSession(['equipo_token' => $equipoB->token_sesion])
                    ->postJson('/api/enviar-respuesta', [
                        'pregunta_id'    => $pregB['id'],
                        'opcion_elegida' => $opcionVisualB,
                    ])
                    ->assertOk()
                    ->assertJsonPath('es_correcta', true);
            }

            $this->withSession(['equipo_token' => $equipoA->token_sesion])
                ->postJson('/api/enviar-respuesta', [
                    'pregunta_id'    => $pregDataA['id'],
                    'opcion_elegida' => $opcionVisualA,
                ])
                ->assertOk()
                ->assertJsonPath('es_correcta', true);
        }

        // Equipo A ya completó las 3 etapas normales
        $this->withSession(['equipo_token' => $equipoA->token_sesion])
            ->getJson('/api/estado-juego')
            ->assertOk()
            ->assertJsonPath('equipo.finalizo_carrera', true)
            ->assertJsonPath('pregunta', null);

        // Confirmar registro anti-trampa y mayor puntaje de Escuderia Alpha
        $equipoA->refresh();
        $equipoB->refresh();
        $this->assertGreaterThanOrEqual(1, (int) $equipoB->salidas_pantalla);
        $this->assertGreaterThan($equipoB->puntaje_total, $equipoA->puntaje_total);

        // 7. Admin activa Muerte Súbita (Desempate) para ambos equipos
        $this->withSession($adminSession)
            ->post('/admin/desempate', [
                'equipos_desempate' => [$equipoA->id, $equipoB->id],
            ])
            ->assertSessionHasNoErrors();

        $nivelDesempate = Nivel::where('es_desempate', true)->firstOrFail();
        for ($d = 0; $d < $nivelDesempate->cantidad_preguntas; $d++) {
            $estadoDesA = $this->withSession(['equipo_token' => $equipoA->token_sesion])
                ->getJson('/api/estado-juego')
                ->assertOk()
                ->assertJsonPath('estado_juego', 'desempate');
            $this->assertTrue((bool) $estadoDesA->json('nivel.es_desempate'));

            $pregDesA = $estadoDesA->json('pregunta');
            $visDesA = $this->elegirOpcionVisualCorrecta($pregDesA);

            $this->withSession(['equipo_token' => $equipoA->token_sesion])
                ->postJson('/api/enviar-respuesta', [
                    'pregunta_id'    => $pregDesA['id'],
                    'opcion_elegida' => $visDesA,
                ])
                ->assertOk()
                ->assertJsonPath('es_correcta', true);
        }

        // 8. Admin finaliza torneo y valida ranking en /api/carrera-en-vivo
        $this->withSession($adminSession)
            ->post('/admin/configuracion', [
                'estado_juego'           => 'finalizado',
                'max_integrantes_equipo' => $config->max_integrantes_equipo,
                'max_equipos_permitidos' => $config->max_equipos_permitidos,
            ])
            ->assertSessionHasNoErrors();

        $carrera = $this->getJson('/api/carrera-en-vivo')->assertOk();
        $carrera->assertJsonPath('evento.estado_juego', 'finalizado');
        $equiposLive = collect($carrera->json('equipos'));
        $this->assertCount(2, $equiposLive);
        $this->assertSame('Escuderia Alpha', $equiposLive->first()['nombre_equipo']);
    }
}
