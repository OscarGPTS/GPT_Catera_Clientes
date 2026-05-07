<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AuthOrchestrator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

/**
 * Google / Microsoft / Apple — D6 modalidad C. Usuarios externos pre-registrados en email_allowlist.
 *
 * TODO M1: registrar credenciales OAuth en config/services.php y .env antes de exponer rutas en producción.
 */
class SocialiteController extends Controller
{
    private const PROVIDERS = ['google', 'microsoft', 'apple'];

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider, AuthOrchestrator $orchestrator): RedirectResponse
    {
        abort_unless(in_array($provider, self::PROVIDERS, true), 404);

        try {
            $socialUser = Socialite::driver($provider)->user();

            $user = $orchestrator->loginOrProvision(
                provider: $provider,
                providerUserId: (string) $socialUser->getId(),
                email: (string) $socialUser->getEmail(),
                profileData: [
                    'name' => $socialUser->getName(),
                    'avatar' => $socialUser->getAvatar(),
                ],
            );

            Auth::login($user, remember: true);

            return redirect()->intended('/dashboard');
        } catch (\Throwable $e) {
            Log::error('Socialite callback failed', ['provider' => $provider, 'error' => $e->getMessage()]);

            return redirect()->route('login')->withErrors([$provider => $e->getMessage()]);
        }
    }
}
