<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sección 6.8 del plan: rutas /finanzas/* solo accesibles por
 * cfo, direccion_general, socio, comite_socios, analista_financiero.
 */
class EnsureFinanzasAccess
{
    private const ROLES = ['cfo', 'direccion_general', 'socio', 'comite_socios', 'analista_financiero'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasAnyRole(self::ROLES)) {
            abort(403, 'No tienes acceso al módulo de Finanzas.');
        }

        return $next($request);
    }
}
