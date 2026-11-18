<?php

namespace App\Http\Middleware;

use App\Http\Controllers\AdminController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $adminPrefix = AdminController::adminPrefix();

        // Si cambió config('app.admin_route_prefix') en tiempo de ejecución (p. ej. en pruebas),
        // recargamos las rutas antes del despacho para aplicar el nuevo prefijo sin reiniciar la app.
        if (app()->bound('seventech.booted_admin_prefix') && app('seventech.booted_admin_prefix') !== $adminPrefix) {
            $router = app('router');
            $router->setRoutes(new RouteCollection());
            $router->middleware('web')->group(base_path('routes/web.php'));
            $router->getRoutes()->refreshNameLookups();
            $router->getRoutes()->refreshActionLookups();
        }

        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        if ($request->is($adminPrefix) || $request->is($adminPrefix . '/*')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        }

        return $response;
    }
}
