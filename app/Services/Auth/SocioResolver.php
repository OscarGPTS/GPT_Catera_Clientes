<?php

namespace App\Services\Auth;

use App\Models\SocioAllowlist;
use App\Models\User;
use App\Services\Rh\RhClientInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Cascada D2: API RH (puesto Socio/DG/CEO) → override manual → email allowlist (.env / tabla).
 */
class SocioResolver
{
    private const CACHE_TTL = 1800;

    private const PUESTOS_SOCIO = ['socio', 'director general', 'accionista', 'ceo'];

    public function __construct(
        private readonly RhClientInterface $rh,
    ) {}

    public function isSocio(User $user): bool
    {
        return Cache::remember("socio:{$user->id}", self::CACHE_TTL, function () use ($user) {
            // 1. API RH
            $rhUser = $this->rh->searchByEmail($user->email);
            if ($rhUser && $this->puestoEsSocio($rhUser->puesto ?? '')) {
                return true;
            }

            // 2. Override manual
            if ($user->es_socio_override !== null) {
                return (bool) $user->es_socio_override;
            }

            // 3. Email allowlist (tabla + env)
            return $this->emailEnAllowlist($user->email);
        });
    }

    public static function flushCacheFor(User $user): void
    {
        Cache::forget("socio:{$user->id}");
    }

    private function puestoEsSocio(string $puesto): bool
    {
        $lower = Str::lower($puesto);

        foreach (self::PUESTOS_SOCIO as $needle) {
            if (Str::contains($lower, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function emailEnAllowlist(string $email): bool
    {
        $envList = collect(explode(',', (string) config('gpt.socios_email_allowlist')))
            ->map(fn ($e) => Str::lower(trim($e)))
            ->filter()
            ->all();

        if (in_array(Str::lower($email), $envList, true)) {
            return true;
        }

        return SocioAllowlist::whereRaw('LOWER(email) = ?', [Str::lower($email)])->exists();
    }
}
