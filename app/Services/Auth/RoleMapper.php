<?php

namespace App\Services\Auth;

use App\Models\RhRoleMapping;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class RoleMapper
{
    private const FALLBACK_ROLE = 'ingeniero_proyectos';

    private const CACHE_TTL = 1800; // 30 min

    public function resolveRoleForUser(User $user): string
    {
        if (! $user->puesto) {
            return self::FALLBACK_ROLE;
        }

        $rules = Cache::remember('rh_role_mapping:active', self::CACHE_TTL, function () {
            return RhRoleMapping::active()->orderByPrioridad()->get()->all();
        });

        foreach ($rules as $rule) {
            if ($this->matches($rule->puesto_rh, $user->puesto, $rule->departamento_filter, $user->departamento)) {
                return $rule->rol_sistema;
            }
        }

        return self::FALLBACK_ROLE;
    }

    private function matches(string $pattern, string $puesto, ?string $departamentoFilter, ?string $departamentoUsuario): bool
    {
        $regex = '/^'.str_replace('%', '.*', preg_quote($pattern, '/')).'$/i';
        $regex = str_replace('\\.\\*', '.*', $regex);

        if (! preg_match($regex, $puesto)) {
            return false;
        }

        if ($departamentoFilter && Str::lower($departamentoFilter) !== Str::lower((string) $departamentoUsuario)) {
            return false;
        }

        return true;
    }

    public static function flushCache(): void
    {
        Cache::forget('rh_role_mapping:active');
    }
}
