<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurarTrustedProxies();
        $this->configurarRateLimiting();
    }

    /**
     * Aplica proxies confiables desde config() (compatible con config:cache en VPS).
     */
    private function configurarTrustedProxies(): void
    {
        $proxiesConfig = trim((string) config('app.trusted_proxies', '127.0.0.1'));
        if ($proxiesConfig === '*') {
            TrustProxies::at('*');
            return;
        }
        if ($proxiesConfig === '') {
            TrustProxies::at([]);
            return;
        }
        $proxies = array_values(array_filter(array_map('trim', explode(',', $proxiesConfig)), fn ($ip) => $ip !== ''));
        TrustProxies::at($proxies);
    }

    /**
     * Todos los celulares comparten la misma IP del Wi-Fi, así que los límites se aplican
     * por equipo/sesión. La IP solo se usa como techo global alto para la red completa.
     */
    private function configurarRateLimiting(): void
    {
        $clavePorEquipo = function (Request $request): string {
            $token = $request->session()->get('equipo_token');
            return $token ? 'eq:' . hash('sha256', $token) : 'ses:' . $request->session()->getId();
        };

        // Consulta de estado: el celular consulta cada ~2 s (30/min) + reintentos (hasta 100+ equipos por IP Wi-Fi)
        RateLimiter::for('juego-estado', fn (Request $r) => [
            Limit::perMinute(90)->by('estado:' . $clavePorEquipo($r)),
            Limit::perMinute(10000)->by('estado-ip:' . $r->ip()),
        ]);

        // Envío de respuestas: como máximo una por pregunta, con margen para reintentos
        RateLimiter::for('juego-respuesta', fn (Request $r) => [
            Limit::perMinute(40)->by('resp:' . $clavePorEquipo($r)),
            Limit::perMinute(5000)->by('resp-ip:' . $r->ip()),
        ]);

        // Registro de equipos
        RateLimiter::for('registro', fn (Request $r) => [
            Limit::perMinute(10)->by('reg:' . $r->session()->getId()),
            Limit::perMinute(300)->by('reg-ip:' . $r->ip()),
        ]);

        // Recuperación por PIN: el límite fuerte va POR EQUIPO OBJETIVO (anti fuerza bruta del PIN de 4 dígitos)
        RateLimiter::for('pin', function (Request $r) {
            $equipo = mb_strtolower(trim((string) $r->input('nombre_equipo')), 'UTF-8');
            return [
                Limit::perMinute(5)->by('pin:' . $equipo),
                Limit::perHour(20)->by('pin-h:' . $equipo),
                Limit::perMinute(300)->by('pin-ip:' . $r->ip()),
            ];
        });

        // Login del panel admin
        RateLimiter::for('admin-login', fn (Request $r) => [
            Limit::perMinute(5)->by('adm:' . $r->session()->getId()),
            Limit::perMinute(20)->by('adm-ip:' . $r->ip()),
        ]);
    }
}
