<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $proxiesRaw = trim((string) env('TRUSTED_PROXIES', '127.0.0.1'));
        if ($proxiesRaw === '*') {
            $middleware->trustProxies(at: '*');
        } elseif ($proxiesRaw !== '') {
            $proxies = array_values(array_filter(array_map('trim', explode(',', $proxiesRaw)), fn ($ip) => $ip !== ''));
            if (!empty($proxies)) {
                $middleware->trustProxies(at: $proxies);
            }
        }
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $manejar419Admin = function (Request $request) {
            if (!$request->is('admin') && !$request->is('admin/*')) {
                return null;
            }
            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['error' => 'Tu sesión expiró. Recarga la página para continuar.'], 419);
            }
            return redirect('/admin')->withErrors([
                'password' => 'Tu sesión expiró por inactividad. Ingresa nuevamente.',
            ]);
        };

        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, Request $request) use ($manejar419Admin) {
            return $manejar419Admin($request);
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, Request $request) use ($manejar419Admin) {
            if ($e->getStatusCode() === 419) {
                return $manejar419Admin($request);
            }
            return null;
        });
    })->create();
