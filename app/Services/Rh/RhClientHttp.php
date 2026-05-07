<?php

namespace App\Services\Rh;

use App\DTOs\RhUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cliente HTTP real contra services.satechenergy.com/api/rh
 * con cache de 30 min y circuit breaker simple.
 *
 * TODO M1: validar contrato con el equipo de RH cuando RH_API_TOKEN esté disponible.
 */
class RhClientHttp implements RhClientInterface
{
    private const CACHE_TTL = 1800; // 30 min

    private const BREAKER_KEY = 'rh_client:circuit_breaker';

    private const BREAKER_FAILURES = 3;

    private const BREAKER_COOLDOWN = 300; // 5 min

    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $token,
    ) {}

    public function searchByEmail(string $email): ?RhUser
    {
        return Cache::remember(
            'rh:by_email:'.strtolower($email),
            self::CACHE_TTL,
            fn () => $this->safeCall(fn () => $this->doSearchByEmail($email)),
        );
    }

    public function getById(string $employeeId): ?RhUser
    {
        return Cache::remember(
            "rh:by_id:{$employeeId}",
            self::CACHE_TTL,
            fn () => $this->safeCall(fn () => $this->doGetById($employeeId)),
        );
    }

    public function listAll(): Collection
    {
        return Cache::remember(
            'rh:list_all',
            self::CACHE_TTL,
            fn () => $this->safeCall(fn () => $this->doListAll()) ?? collect(),
        );
    }

    private function doSearchByEmail(string $email): ?RhUser
    {
        $response = $this->client()->post('/buscar-por-email', ['email' => $email]);

        if ($response->status() === 404) {
            return null;
        }

        $response->throw();

        return RhUser::fromArray($response->json());
    }

    private function doGetById(string $employeeId): ?RhUser
    {
        $response = $this->client()->get("/{$employeeId}");

        if ($response->status() === 404) {
            return null;
        }

        $response->throw();

        return RhUser::fromArray($response->json());
    }

    private function doListAll(): Collection
    {
        $response = $this->client()->get('/users');
        $response->throw();

        return collect($response->json('data', []))->map(fn ($row) => RhUser::fromArray($row));
    }

    private function client()
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout(10)
            ->retry(2, 250)
            ->acceptJson()
            ->when($this->token, fn ($http) => $http->withToken($this->token));
    }

    private function safeCall(callable $fn): mixed
    {
        if ($this->breakerOpen()) {
            return null;
        }

        try {
            return $fn();
        } catch (Throwable $e) {
            $this->recordFailure();
            Log::warning('RH API call failed', ['exception' => $e->getMessage()]);

            return null;
        }
    }

    private function breakerOpen(): bool
    {
        return (int) Cache::get(self::BREAKER_KEY, 0) >= self::BREAKER_FAILURES;
    }

    private function recordFailure(): void
    {
        Cache::increment(self::BREAKER_KEY);
        Cache::put(self::BREAKER_KEY, (int) Cache::get(self::BREAKER_KEY, 1), self::BREAKER_COOLDOWN);
    }
}
