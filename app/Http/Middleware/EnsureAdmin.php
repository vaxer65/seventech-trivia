<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->get('es_admin_seventech')) {
            abort(403);
        }

        return $next($request);
    }
}

