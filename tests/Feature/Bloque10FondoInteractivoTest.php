<?php

namespace Tests\Feature;

use App\Models\ConfiguracionEvento;
use App\Models\Modulo;
use App\Models\Nivel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Bloque10FondoInteractivoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Modulo::create([
            'nombre'      => 'Inteligencia Artificial',
            'descripcion' => 'Módulo de prueba Bloque 10',
        ]);

        Nivel::create([
            'nombre'             => 'Etapa 1',
            'orden_dificultad'   => 1,
            'cantidad_preguntas' => 5,
            'puntos_por_acierto' => 100,
            'tiempo_limite_seg'  => 20,
            'es_desempate'       => false,
        ]);

        ConfiguracionEvento::create([
            'nombre_evento'          => 'Seven Tech Trivia 2026',
            'estado_juego'           => 'registro',
            'tiempo_total_minutos'   => 15,
            'max_integrantes_equipo' => 5,
            'max_equipos_permitidos' => 30,
            'semilla_partida'        => 'semilla_bloque10_test',
        ]);
    }

    public function test_todas_las_vistas_renderizan_canvas_fondo_oscuro_y_bordes_esmeralda(): void
    {
        $rutasPublicas = ['/', '/jugar', '/espectador'];

        foreach ($rutasPublicas as $ruta) {
            $resp = $this->get($ruta)->assertOk();
            $resp->assertSee('<canvas id="canvas-fondo-interactivo" aria-hidden="true"></canvas>', false)
                ->assertSee('radial-gradient(circle at 50% 20%, #0d1629 0%, #060913 70%, #04060d 100%)', false)
                ->assertSee('brilloEsmeraldaSuave', false)
                ->assertSee('relative z-10', false)
                ->assertDontSee('#dce3ec', false);
        }

        // Vista /admin (invitado y autenticado)
        $this->get('/admin')
            ->assertOk()
            ->assertSee('<canvas id="canvas-fondo-interactivo" aria-hidden="true"></canvas>', false)
            ->assertSee('relative z-10', false);

        $this->withSession(['es_admin_seventech' => true])
            ->get('/admin')
            ->assertOk()
            ->assertSee('<canvas id="canvas-fondo-interactivo" aria-hidden="true"></canvas>', false)
            ->assertSee('brilloEsmeraldaSuave', false)
            ->assertSee('relative z-10', false);
    }

    public function test_motor_canvas_incluye_optimizaciones_dpr_visibilidad_y_reduced_motion(): void
    {
        $resp = $this->get('/')->assertOk();

        $resp->assertSee('Math.min(window.devicePixelRatio || 1, 1.5)', false)
            ->assertSee('document.hidden', false)
            ->assertSee('prefers-reduced-motion: reduce', false)
            ->assertSee("window.addEventListener('mousemove'", false)
            ->assertSee('const cantidadNodos = esMovil ? 24 : 55;', false)
            ->assertSee('const cantidadParticulas = esMovil ? 18 : 35;', false)
            ->assertSee('const intervaloFrameMs = 28;', false)
            ->assertSee('backdrop-filter: blur(8px);', false)
            ->assertDontSee('shadowBlur', false)
            ->assertSee('text-slate-200', false)
            ->assertSee('text-slate-300', false);

        $this->get('/espectador')
            ->assertOk()
            ->assertSee('grid-cols-2 sm:grid-cols-4 gap-2', false)
            ->assertSee('CALENTAMIENTO', false)
            ->assertSee('VEL. CRUCERO', false);

        $this->get('/jugar')
            ->assertOk()
            ->assertSee('Seven Tech Community • Protección Anti-IA Activa', false)
            ->assertSee('flex flex-col gap-3', false)
            ->assertSee('text-slate-300', false);
    }
}

