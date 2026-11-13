<?php

namespace Tests\Feature;

use App\Http\Controllers\EspectadorController;
use App\Models\ConfiguracionEvento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Bloque6QrMovilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        ConfiguracionEvento::create([
            'nombre_evento'           => 'Seven Tech Trivia 2026',
            'estado_juego'            => 'registro',
            'tiempo_total_minutos'    => 15,
            'max_integrantes_equipo'  => 5,
            'max_equipos_permitidos'  => 30,
            'semilla_partida'         => 'semilla_bloque6_test',
        ]);
    }

    public function test_espectador_resuelve_qr_con_ip_lan_cuando_host_es_local(): void
    {
        $this->app->bind(EspectadorController::class, function () {
            return new class extends EspectadorController {
                protected function resolverIpLanLocal(): ?string
                {
                    return '192.168.100.42';
                }
            };
        });

        $respLan = $this->get('http://0.0.0.0:8000/espectador');
        $respLan->assertOk()
            ->assertViewHas('urlRegistroQr', 'http://192.168.100.42:8000/jugar')
            ->assertViewHas('mostrarAvisoLocalhost', false)
            ->assertSee('http://192.168.100.42:8000/jugar', false);
    }

    public function test_espectador_muestra_aviso_si_no_hay_ip_lan_en_localhost(): void
    {
        config(['app.url' => 'http://localhost']);
        $this->app->bind(EspectadorController::class, function () {
            return new class extends EspectadorController {
                protected function resolverIpLanLocal(): ?string
                {
                    return null;
                }
            };
        });

        $respLocal = $this->get('http://localhost:8000/espectador');
        $respLocal->assertOk()
            ->assertViewHas('urlRegistroQr', 'http://localhost:8000/jugar')
            ->assertViewHas('mostrarAvisoLocalhost', true);
    }

    public function test_espectador_usa_origen_real_en_produccion_sin_aviso_local(): void
    {
        $response = $this->get('https://trivia-seventech.softecsa.com/espectador');

        $response->assertOk()
            ->assertViewHas('urlRegistroQr', 'https://trivia-seventech.softecsa.com/jugar')
            ->assertViewHas('mostrarAvisoLocalhost', false)
            ->assertSee('https://trivia-seventech.softecsa.com/jugar', false)
            ->assertSee('id="aviso-qr-localhost" class="hidden ', false);
    }

    public function test_vista_jugar_soporta_registro_por_defecto_y_controles_tactiles_moviles(): void
    {
        $response = $this->get('/jugar');

        $response->assertOk()
            ->assertSee('activarRegistroDirectoSiAplica', false)
            ->assertSee("cambiarTab('nuevo')", false)
            ->assertSee('min-h-[48px]', false)
            ->assertSee('truncate max-w-[105px] sm:max-w-[180px]', false)
            ->assertSee('text-base', false);
    }

    public function test_vista_jugar_mantiene_disuasivos_y_escudo_anti_ia(): void
    {
        $response = $this->get('/jugar');

        $response->assertOk()
            ->assertSee('oncontextmenu="return false;"', false)
            ->assertSee('oncopy="return false;"', false)
            ->assertSee('id="cortina-anti-ia"', false)
            ->assertSee('/api/reportar-salida-pantalla', false);
    }
}
