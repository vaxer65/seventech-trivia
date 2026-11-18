<?php

namespace Tests\Feature;

use App\Models\ConfiguracionEvento;
use App\Models\Nivel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class Bloque12SeguridadAdmin2faTest extends TestCase
{
    use RefreshDatabase;

    private string $adminPasswordPlain = 'ClaveAdminBloque12!';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.admin_password_hash' => Hash::make($this->adminPasswordPlain),
            'app.admin_route_prefix'  => 'admin',
            'app.admin_2fa_secret'    => '',
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
            'semilla_partida'        => 'semilla_bloque12_test',
        ]);
    }

    public function test_portada_oculta_acceso_admin_y_rutas_admin_incluyen_noindex(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('/jugar', false)
            ->assertSee('/espectador', false)
            ->assertDontSee('/admin', false)
            ->assertDontSee('ADMINISTRADOR', false);

        $this->get('/admin')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');

        $this->post('/admin/configuracion', [])
            ->assertForbidden()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    public function test_prefijo_privado_configurable_oculta_admin_y_responde_en_nueva_ruta(): void
    {
        config(['app.admin_route_prefix' => 'boveda-st26']);

        $this->get('/admin')->assertNotFound();

        $resp = $this->get('/boveda-st26');
        $resp->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertSee('action="/boveda-st26/login"', false);

        $this->post('/boveda-st26/login', ['password' => $this->adminPasswordPlain])
            ->assertRedirect('/boveda-st26');

        $this->get('/boveda-st26')
            ->assertOk()
            ->assertSee('action="/boveda-st26/configuracion"', false);
    }

    public function test_cabeceras_de_seguridad_globales_y_hsts_solo_en_https(): void
    {
        $httpResp = $this->get('http://localhost/')->assertOk();
        $httpResp->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeaderMissing('Strict-Transport-Security');

        $httpsResp = $this->get('https://trivia.tudominio.com/')->assertOk();
        $httpsResp->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_flujo_completo_google_authenticator_2fa_activar_bloquear_verificar_y_desactivar(): void
    {
        // 1. Login tradicional cuando 2FA está desactivado
        $this->post('/admin/login', ['password' => $this->adminPasswordPlain])
            ->assertRedirect('/admin');
        $this->assertTrue((bool) session('es_admin_seventech'));

        // 2. Generar QR SVG local temporal para activar 2FA
        $this->post('/admin/2fa/preparar')
            ->assertRedirect('/admin#seccion-seguridad-2fa');

        $secreto = (string) session('admin_2fa_setup_secret');
        $this->assertNotEmpty($secreto);

        $this->get('/admin')
            ->assertOk()
            ->assertSee('<svg', false)
            ->assertSee($secreto, false);

        // 3. Rechazar activación con OTP inválido
        $this->post('/admin/2fa/activar', ['codigo_2fa' => '000000'])
            ->assertSessionHasErrors('codigo_2fa');
        $this->assertNull(ConfiguracionEvento::first()->admin_2fa_secret);

        // 4. Confirmar activación con OTP TOTP válido
        $google2fa = new Google2FA();
        $otpValido = $google2fa->getCurrentOtp($secreto);

        $this->post('/admin/2fa/activar', ['codigo_2fa' => $otpValido])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/admin#seccion-seguridad-2fa');

        $config = ConfiguracionEvento::first()->fresh();
        $this->assertNotNull($config->admin_2fa_secret);
        $this->assertNotSame($secreto, $config->admin_2fa_secret); // Guardado cifrado en BD
        $this->assertSame($secreto, Crypt::decryptString($config->admin_2fa_secret));
        $this->assertTrue($config->tiene2faActivo());

        // Verificar que /api/carrera-en-vivo jamás exponga admin_2fa_secret
        $this->getJson('/api/carrera-en-vivo')
            ->assertOk()
            ->assertJsonMissingPath('evento.admin_2fa_secret');

        // 5. Cerrar sesión e intentar login: solo queda en paso pendiente, bloqueado en rutas protegidas
        $this->post('/admin/logout')->assertRedirect('/admin');
        $this->assertFalse((bool) session('es_admin_seventech'));

        $this->post('/admin/login', ['password' => $this->adminPasswordPlain])
            ->assertRedirect('/admin');

        $this->assertTrue((bool) session('admin_2fa_pendiente'));
        $this->assertFalse((bool) session('es_admin_seventech'));
        $this->assertFalse((bool) session('es_admin'));

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Verificación 2FA', false)
            ->assertSee('action="/admin/2fa/verificar"', false);

        // Con solo contraseña (sin validar 2FA) no puede ejecutar acciones admin
        $this->post('/admin/configuracion', ['estado_juego' => 'en_curso'])
            ->assertForbidden();

        // 6. Rechazar código 2FA incorrecto y luego aprobar con TOTP válido
        $this->post('/admin/2fa/verificar', ['codigo_2fa' => '123456'])
            ->assertSessionHasErrors('codigo_2fa');
        $this->assertFalse((bool) session('es_admin_seventech'));

        $otpLogin = $google2fa->getCurrentOtp($secreto);
        $this->post('/admin/2fa/verificar', ['codigo_2fa' => $otpLogin])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/admin');

        $this->assertFalse((bool) session('admin_2fa_pendiente'));
        $this->assertTrue((bool) session('es_admin_seventech'));
        $this->assertTrue((bool) session('es_admin'));

        $this->get('/admin')
            ->assertOk()
            ->assertSee('2FA Activo', false);

        // 7. Desactivar 2FA exige contraseña y TOTP vigente
        $this->post('/admin/2fa/desactivar', [
            'password'   => 'ClaveIncorrecta!',
            'codigo_2fa' => $google2fa->getCurrentOtp($secreto),
        ])->assertSessionHasErrors('password_2fa');
        $this->assertTrue(ConfiguracionEvento::first()->tiene2faActivo());

        $this->post('/admin/2fa/desactivar', [
            'password'   => $this->adminPasswordPlain,
            'codigo_2fa' => '999999',
        ])->assertSessionHasErrors('codigo_2fa');
        $this->assertTrue(ConfiguracionEvento::first()->tiene2faActivo());

        $this->post('/admin/2fa/desactivar', [
            'password'   => $this->adminPasswordPlain,
            'codigo_2fa' => $google2fa->getCurrentOtp($secreto),
        ])->assertSessionHasNoErrors()
            ->assertRedirect('/admin#seccion-seguridad-2fa');

        $this->assertNull(ConfiguracionEvento::first()->admin_2fa_secret);
        $this->assertFalse(ConfiguracionEvento::first()->tiene2faActivo());
    }
}

