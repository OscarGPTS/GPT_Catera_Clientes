<?php

namespace App\Services\Auth;

use App\Models\AuthProvider;
use App\Models\EmailAllowlist;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Rh\RhClientInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class AuthOrchestrator
{
    public function __construct(
        private readonly RhClientInterface $rh,
        private readonly RoleMapper $roleMapper,
        private readonly SocioResolver $socioResolver,
    ) {}

    /**
     * Punto de entrada único para los 3 flujos (Auth0, email/password, Socialite).
     *
     * @param  array<string, mixed>  $profileData  perfil del proveedor (name, avatar, etc.)
     */
    public function loginOrProvision(string $provider, ?string $providerUserId, string $email, array $profileData = []): User
    {
        return DB::transaction(function () use ($provider, $providerUserId, $email, $profileData) {
            $email = Str::lower(trim($email));

            $authProvider = $providerUserId
                ? AuthProvider::where('provider', $provider)->where('provider_user_id', $providerUserId)->first()
                : null;

            if ($authProvider) {
                $authProvider->update(['last_used_at' => now()]);
                $user = $authProvider->user;
                $this->touchLogin($user);

                return $user;
            }

            $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

            if ($user) {
                AuthProvider::create([
                    'user_id' => $user->id,
                    'provider' => $provider,
                    'provider_user_id' => $providerUserId,
                    'is_primary' => false,
                    'linked_at' => now(),
                    'last_used_at' => now(),
                ]);
                $this->touchLogin($user);

                return $user;
            }

            $user = $this->provisionNewUser($provider, $email, $profileData);

            AuthProvider::create([
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_user_id' => $providerUserId,
                'is_primary' => true,
                'linked_at' => now(),
                'last_used_at' => now(),
            ]);

            $this->touchLogin($user);

            return $user;
        });
    }

    private function provisionNewUser(string $provider, string $email, array $profileData): User
    {
        if ($this->isCorporateEmail($email)) {
            $rhUser = $this->rh->searchByEmail($email);

            if (! $rhUser) {
                Log::warning('Corporate email without RH match', ['email' => $email]);
                throw new RuntimeException("Tu correo {$email} no está registrado en RH. Contacta al administrador.");
            }

            $user = User::create([
                'name' => $rhUser->name,
                'email' => $email,
                'employee_id' => $rhUser->employeeId,
                'departamento' => $rhUser->departamento,
                'puesto' => $rhUser->puesto,
                'avatar_url' => $rhUser->avatarUrl ?? ($profileData['avatar'] ?? null),
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            $role = $this->roleMapper->resolveRoleForUser($user);
            $user->assignRole($role);
            $user->es_socio = $this->socioResolver->isSocio($user);
            $user->save();

            return $user;
        }

        // No corporativo: revisar email_allowlist
        $allowed = EmailAllowlist::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $allowed) {
            throw new RuntimeException("El correo {$email} no está autorizado.");
        }

        if ($allowed->allowed_providers && ! in_array($provider, $allowed->allowed_providers, true)) {
            throw new RuntimeException("El correo {$email} no está autorizado para entrar con {$provider}.");
        }

        $user = User::create([
            'name' => $profileData['name'] ?? $email,
            'email' => $email,
            'departamento' => $allowed->departamento_default,
            'avatar_url' => $profileData['avatar'] ?? null,
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        if ($allowed->role_default) {
            $user->assignRole($allowed->role_default);
        }

        return $user;
    }

    private function isCorporateEmail(string $email): bool
    {
        $domains = SystemSetting::get('auth_dominios_corporativos', ['gptservices.com', 'satechenergy.com']);

        $domain = Str::after($email, '@');

        return in_array(Str::lower($domain), array_map('strtolower', (array) $domains), true);
    }

    private function touchLogin(User $user): void
    {
        $user->forceFill(['last_login_at' => now()])->save();
    }
}
