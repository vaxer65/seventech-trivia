<?php

namespace Tests\Feature;

use App\Http\Controllers\AdminController;
use App\Models\ConfiguracionEvento;
use App\Models\Nivel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Bloque7CalculadoraTiempoTest extends TestCase
{
    use RefreshDatabase;

    private ConfiguracionEvento $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = ConfiguracionEvento::create([
            'nombre_evento'          => 'Seven Tech Calculadora Test',
            'estado_juego'           => 'registro',
            'tiempo_total_minutos'   => 10,
            'max_integrantes_equipo' => 5,
            'max_equipos_permitidos' => 30,
            'semilla_partida'        => 'semilla_b7_calc',
        ]);

        Nivel::create([
            'nombre'             => 'Etapa 1 - Fácil',
            'orden_dificultad'   => 1,
            'cantidad_preguntas' => 5,
            'puntos_por_acierto' => 100,
            'tiempo_limite_seg'  => 20,
            'es_desempate'       => false,
        ]);

        Nivel::create([
            'nombre'             => 'Etapa 2 - Media',
            'orden_dificultad'   => 2,
            'cantidad_preguntas' => 5,
            'puntos_por_acierto' => 200,
            'tiempo_limite_seg'  => 30,
            'es_desempate'       => false,
        ]);

        Nivel::create([
            'nombre'             => 'Muerte Súbita',
            'orden_dificultad'   => 3,
            'cantidad_preguntas' => 10,
            'puntos_por_acierto' => 500,
            'tiempo_limite_seg'  => 30,
            'es_desempate'       => true,
        ]);
    }

    public function test_admin_index_renderiza_estimacion_ssr_y_semaforo_verde_con_duracion_optima(): void
    {
        $response = $this->withSession(['es_admin_seventech' => true])->get('/admin');

        $response->assertOk()
            ->assertViewHas('estimacionDuracion', function (array $est) {
                return $est['semaforo'] === 'verde'
                    && $est['seg_promedio'] === 170
                    && $est['seg_maximo'] === 270
                    && $est['recomendado_min'] === 6;
            })
            ->assertSee('id="calculadora-duracion-torneo"', false)
            ->assertSee('✅ Duración óptima', false)
            ->assertSee('✨ Aplicar tiempo recomendado (6 min)', false)
            ->assertSee('Etapa 1 - Fácil (5 × 20s)', false)
            ->assertSee('Etapa 2 - Media (5 × 30s)', false);
    }

    public function test_admin_semaforo_cambia_a_rojo_y_ambar_con_tiempos_cortos(): void
    {
        // 2 min (120s) < 170s promedio -> Rojo
        $this->config->update(['tiempo_total_minutos' => 2]);
        $this->withSession(['es_admin_seventech' => true])
            ->get('/admin')
            ->assertOk()
            ->assertViewHas('estimacionDuracion', fn (array $e) => $e['semaforo'] === 'rojo')
            ->assertSee('⚠️ Tiempo insuficiente', false);

        // 4 min (240s >= 170s, pero < 6 min recomendado) -> Ámbar
        $this->config->update(['tiempo_total_minutos' => 4]);
        $this->withSession(['es_admin_seventech' => true])
            ->get('/admin')
            ->assertOk()
            ->assertViewHas('estimacionDuracion', fn (array $e) => $e['semaforo'] === 'ambar')
            ->assertSee('⚡ Tiempo ajustado', false);
    }

    public function test_formula_calculadora_y_autoguardado_soportan_torneos_extensos(): void
    {
        $niveles = Nivel::orderBy('orden_dificultad')->get();
        $resultado = AdminController::calcularEstimacionDuracion($niveles, 6);

        $this->assertSame(2, $resultado['transicion_seg']);
        $this->assertSame(170, $resultado['seg_promedio']);
        $this->assertSame(270, $resultado['seg_maximo']);
        $this->assertSame(64, $resultado['seg_colchon_desempate']);
        $this->assertSame(6, $resultado['recomendado_min']);
        $this->assertSame('verde', $resultado['semaforo']);

        // Torneos extensos (> 180 min, hasta 600 min) guardan correctamente vía JSON
        $this->withSession(['es_admin_seventech' => true])
            ->postJson('/admin/configuracion', [
                'estado_juego'           => 'registro',
                'modulo_activo_id'       => '',
                'max_integrantes_equipo' => 5,
                'max_equipos_permitidos' => 30,
                'tiempo_total_minutos'   => 250,
            ])
            ->assertOk();

        $this->assertSame(250, $this->config->fresh()->tiempo_total_minutos);
    }

    public function test_boton_aplicar_tiempo_recomendado_respeta_candado_en_curso(): void
    {
        // En registro -> botón habilitado
        $htmlRegistro = $this->withSession(['es_admin_seventech' => true])
            ->get('/admin')
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<button[^>]*id="btn-aplicar-tiempo-recomendado"[^>]*data-lockable="true"[^>]*>/s',
            $htmlRegistro
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<button[^>]*id="btn-aplicar-tiempo-recomendado"[^>]*\sdisabled(?:\s|>)/s',
            $htmlRegistro
        );

        // En curso -> botón deshabilitado por candado del Bloque 4
        $this->config->update(['estado_juego' => 'en_curso', 'fin_evento_at' => now()->addMinutes(10)]);
        $htmlEnCurso = $this->withSession(['es_admin_seventech' => true])
            ->get('/admin')
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<button[^>]*id="btn-aplicar-tiempo-recomendado"[^>]*\sdisabled(?:\s|>)/s',
            $htmlEnCurso
        );
    }
}
