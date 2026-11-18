<?php

namespace Tests\Feature;

use App\Models\ConfiguracionEvento;
use App\Models\Nivel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class Bloque13FpsUxMovilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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
            'semilla_partida'        => 'semilla_bloque13_test',
        ]);
    }

    public function test_route_cache_serializa_todas_las_rutas_sin_closures(): void
    {
        try {
            $exitCode = Artisan::call('route:cache');
            $this->assertSame(0, $exitCode, 'php artisan route:cache falló debido a rutas no serializables.');
        } finally {
            Artisan::call('route:clear');
        }
    }

    public function test_cyberpunk_head_incluye_gobernador_60fps_distancias_al_cuadrado_y_ergonomia_ios_android(): void
    {
        $resp = $this->get('/')->assertOk();

        $resp->assertSee('let intervaloFrameMs = 16.6;', false)
            ->assertSee('performance.now()', false)
            ->assertSee('duracionRender > 22', false)
            ->assertSee('cuadrosLentosSeguidos >= 15', false)
            ->assertSee('intervaloFrameMs = 28;', false)
            ->assertSee('maxDistSq', false)
            ->assertSee('@media (max-width: 767px)', false)
            ->assertSee('backdrop-filter: none !important;', false)
            ->assertSee('min-height: 100dvh;', false)
            ->assertSee('env(safe-area-inset-top)', false)
            ->assertSee('env(safe-area-inset-bottom)', false)
            ->assertSee('font-size: max(16px, 1rem);', false);
    }

    public function test_todas_las_vistas_incluyen_ui_feedback_sin_alert_ni_confirm_nativos(): void
    {
        foreach (['/', '/jugar', '/espectador', '/admin'] as $ruta) {
            $resp = $this->get($ruta)->assertOk();
            $resp->assertSee('id="toast-region"', false)
                ->assertSee('top: calc(1rem + env(safe-area-inset-top, 0px)); right: calc(1rem + env(safe-area-inset-right, 0px));', false)
                ->assertDontSee('--toast-bottom', false)
                ->assertSee('ui-toast-saliendo', false)
                ->assertSee('3500', false)
                ->assertSee('existentes.length >= 2', false)
                ->assertSee('dataset.toastMensaje', false)
                ->assertSee('id="modal-confirmacion-ui"', false)
                ->assertSee('ui-toast-visible', false)
                ->assertSee('ui-dialog-fallback', false)
                ->assertDontSee('window.alert(', false)
                ->assertDontSee('window.confirm(', false);
        }
    }
}

