<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthOrchestrator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Auth0 corporativo (D6 modalidad A).
 *
 * TODO M1: Cuando AUTH0_DOMAIN esté configurado, activar auth0/login y
 * extraer email/sub del access token en lugar del stub actual.
 */
class Auth0Controller extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (! config('auth0.guards.default.domain')) {
            return back()->withErrors(['auth0' => 'Auth0 aún no está configurado. Usa email/password o un proveedor social.']);
        }

        // En el SDK real: redirigir a Auth0 universal login.
        return redirect('/auth0/login');
    }

    public function callback(Request $request, AuthOrchestrator $orchestrator): RedirectResponse
    {
        try {
            // TODO: reemplazar por el SDK real de Auth0 — getCredentials() del SDK.
            $email = $request->string('email')->toString();
            $sub = $request->string('sub')->toString();

            if (! $email || ! $sub) {
                return redirect()->route('login')->withErrors(['auth0' => 'Callback inválido.']);
            }

            $user = $orchestrator->loginOrProvision('auth0', $sub, $email, [
                'name' => $request->string('name')->toString() ?: null,
            ]);

            Auth::login($user, remember: true);

            return redirect()->intended('/dashboard');
        } catch (\Throwable $e) {
            Log::error('Auth0 callback failed', ['error' => $e->getMessage()]);

            return redirect()->route('login')->withErrors(['auth0' => $e->getMessage()]);
        }
    }
}
