<?php

namespace Tests\Feature;

use App\Models\ConfiguracionEvento;
use App\Models\Equipo;
use App\Models\Modulo;
use App\Models\Nivel;
use App\Models\Pregunta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class Bloque9PulidoUxEspectadorTest extends TestCase
{
    use RefreshDatabase;

    private Modulo $modulo;
    private Nivel $nivel1;
    private Nivel $nivel2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulo = Modulo::create([
            'nombre'      => 'Inteligencia Artificial',
            'descripcion' => 'Módulo de prueba Bloque 9',
        ]);

        $this->nivel1 = Nivel::create([
            'nombre'             => 'Etapa 1 - Base',
            'orden_dificultad'   => 1,
            'cantidad_preguntas' => 5,
            'puntos_por_acierto' => 100,
            'tiempo_limite_seg'  => 20,
            'es_desempate'       => false,
        ]);

        $this->nivel2 = Nivel::create([
            'nombre'             => 'Etapa 2 - Avanzada',
            'orden_dificultad'   => 2,
            'cantidad_preguntas' => 5,
            'puntos_por_acierto' => 150,
            'tiempo_limite_seg'  => 25,
            'es_desempate'       => false,
        ]);

        ConfiguracionEvento::create([
            'nombre_evento'          => 'Seven Tech Trivia 2026',
            'estado_juego'           => 'registro',
            'tiempo_total_minutos'   => 15,
            'max_integrantes_equipo' => 5,
            'max_equipos_permitidos' => 30,
            'semilla_partida'        => 'semilla_bloque9_test',
        ]);
    }

    public function test_rediseno_plateado_esmeralda_y_qr_limpio_en_espectador(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('brilloEsmeraldaSuave', false)
            ->assertSee('#0d1629', false)
            ->assertSee('canvas-fondo-interactivo', false);

        $resp = $this->get('https://trivia-seventech.softecsa.com/espectador');
        $resp->assertOk()
            ->assertViewHas('urlRegistroQr', 'https://trivia-seventech.softecsa.com/jugar')
            ->assertSee('https://trivia-seventech.softecsa.com/jugar', false)
            ->assertDontSee('/jugar?registro=1', false)
            ->assertSee('id="banner-eventos-vivo"', false)
            ->assertSee('formatearTiempoCompetencia', false)
            ->assertSee('lanzarConfetiPodio', false)
            ->assertSee('podium-lane-1', false)
            ->assertSee('llama-animada', false)
            ->assertDontSee('🏅', false);
    }

    public function test_vista_jugar_usa_validacion_nativa_modal_propio_y_escudo_reforzado(): void
    {
        $resp = $this->get('/jugar');

        $resp->assertOk()
            ->assertSee('novalidate', false)
            ->assertSee('validarFormularioConcursante', false)
            ->assertDontSee('id="alerta-error"', false)
            ->assertDontSee('error-reg-nombre_equipo', false)
            ->assertDontSee('error-rec-pin_recuperacion', false)
            ->assertDontSee('🚪', false)
            ->assertDontSee('confirm(', false)
            ->assertDontSee('alert(', false)
            ->assertSee('id="modal-confirmacion-ui"', false)
            ->assertSee('whitespace-nowrap', false)
            ->assertSee('id="feedback-respuesta"', false)
            ->assertSee("window.addEventListener('blur'", false)
            ->assertSee("window.addEventListener('pagehide'", false)
            ->assertSee("key === 'PrintScreen'", false);
    }

    public function test_admin_desactiva_autoguardado_global_y_descarga_e_importa_plantillas_oficiales(): void
    {
        // Invitados no pueden descargar plantillas
        $this->get('/admin/preguntas/plantilla/csv')->assertForbidden();
        $this->get('/admin/preguntas/plantilla/json')->assertForbidden();

        // Admin autenticado ve panel sin autoguardado en Sección 1, sin Notyf y sin botones Actualizar Etapa
        $respPanel = $this->withSession(['es_admin_seventech' => true])->get('/admin');
        $respPanel->assertOk()
            ->assertSee('id="aviso-cambios-config"', false)
            ->assertSee('marcarConfigSinAplicar', false)
            ->assertSee('/admin/preguntas/plantilla/csv', false)
            ->assertSee('/admin/preguntas/plantilla/json', false)
            ->assertSee('modulo, nivel, enunciado, opcion_a, opcion_b, opcion_c, opcion_d, respuesta_correcta, es_amena', false)
            ->assertDontSee('guardarConfigDebounced', false)
            ->assertDontSee('Actualizar Etapa', false)
            ->assertDontSee('notyf', false)
            ->assertDontSee('onsubmit="return confirm', false);

        // Descargar plantilla CSV e importarla directamente
        $respCsv = $this->withSession(['es_admin_seventech' => true])->get('/admin/preguntas/plantilla/csv');
        $respCsv->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="plantilla_preguntas.csv"');

        $archivoCsv = UploadedFile::fake()->createWithContent('plantilla.csv', $respCsv->getContent());
        $this->withSession(['es_admin_seventech' => true])
            ->post('/admin/preguntas/importar', ['archivo' => $archivoCsv])
            ->assertRedirect()
            ->assertSessionHas('ok');
        $this->assertSame(2, Pregunta::count());

        // Limpiar y probar descarga JSON e importación directa
        Pregunta::query()->delete();
        $respJson = $this->withSession(['es_admin_seventech' => true])->get('/admin/preguntas/plantilla/json');
        $respJson->assertOk()
            ->assertHeader('Content-Disposition', 'attachment; filename="plantilla_preguntas.json"');

        $archivoJson = UploadedFile::fake()->createWithContent('plantilla.json', $respJson->getContent());
        $this->withSession(['es_admin_seventech' => true])
            ->post('/admin/preguntas/importar', ['archivo' => $archivoJson])
            ->assertRedirect()
            ->assertSessionHas('ok');
        $this->assertSame(2, Pregunta::count());
    }

    public function test_importacion_masiva_rechaza_archivos_malformados_sin_inserciones_parciales(): void
    {
        $this->withSession(['es_admin_seventech' => true]);

        // 1. JSON con sintaxis corrupta
        $jsonCorrupto = UploadedFile::fake()->createWithContent('malo.json', '{"preguntas": [corrupto}');
        $this->post('/admin/preguntas/importar', ['archivo' => $jsonCorrupto])
            ->assertRedirect()
            ->assertSessionHasErrors('archivo');

        // 2. JSON sin columnas requeridas
        $jsonIncompleto = UploadedFile::fake()->createWithContent('incompleto.json', json_encode([
            ['titulo' => 'Faltan opciones y respuesta correcta'],
        ]));
        $this->postJson('/admin/preguntas/importar', ['archivo' => $jsonIncompleto])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['archivo']);

        // 3. CSV sin cabeceras requeridas
        $csvInvalido = UploadedFile::fake()->createWithContent('invalido.csv', "modulo,enunciado\nInteligencia Artificial,Solo dos columnas");
        $this->post('/admin/preguntas/importar', ['archivo' => $csvInvalido])
            ->assertRedirect()
            ->assertSessionHasErrors('archivo');

        // 4. CSV con cabeceras válidas pero todas las filas con datos inválidos (módulo inexistente / respuesta inválida)
        $csvFilasInvalidas = UploadedFile::fake()->createWithContent(
            'filas_malas.csv',
            "modulo,nivel,enunciado,opcion_a,opcion_b,opcion_c,opcion_d,respuesta_correcta,es_amena\n" .
            "ModuloInexistente,1,¿Pregunta?,A,B,C,D,z,0"
        );
        $this->post('/admin/preguntas/importar', ['archivo' => $csvFilasInvalidas])
            ->assertRedirect()
            ->assertSessionHasErrors('archivo');

        $this->assertSame(0, Pregunta::count());
    }

    public function test_espectador_api_prioriza_puntaje_luego_aciertos_y_luego_menor_tiempo(): void
    {
        $eqLento = Equipo::create([
            'nombre_equipo'      => 'Equipo Lento',
            'token_sesion'       => str_repeat('a', 60),
            'pin_recuperacion'   => '1111',
            'puntaje_total'      => 300,
            'aciertos_totales'   => 3,
            'tiempo_acumulado_ms'=> 84350,
            'racha_actual'       => 3,
            'racha_maxima'       => 3,
        ]);

        $eqRapido = Equipo::create([
            'nombre_equipo'      => 'Equipo Rápido',
            'token_sesion'       => str_repeat('b', 60),
            'pin_recuperacion'   => '2222',
            'puntaje_total'      => 300,
            'aciertos_totales'   => 3,
            'tiempo_acumulado_ms'=> 45100,
            'racha_actual'       => 4,
            'racha_maxima'       => 4,
        ]);

        $eqMasAciertos = Equipo::create([
            'nombre_equipo'      => 'Equipo Más Aciertos',
            'token_sesion'       => str_repeat('c', 60),
            'pin_recuperacion'   => '3333',
            'puntaje_total'      => 300,
            'aciertos_totales'   => 4,
            'tiempo_acumulado_ms'=> 95000,
            'racha_actual'       => 2,
            'racha_maxima'       => 2,
        ]);

        $resp = $this->getJson('/api/carrera-en-vivo')->assertOk();
        $equipos = $resp->json('equipos');

        $this->assertSame($eqMasAciertos->id, $equipos[0]['id']);
        $this->assertSame($eqRapido->id, $equipos[1]['id']);
        $this->assertSame($eqLento->id, $equipos[2]['id']);
    }
}
