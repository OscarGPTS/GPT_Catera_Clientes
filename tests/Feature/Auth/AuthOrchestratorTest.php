<?php

use App\Models\AuthProvider;
use App\Models\EmailAllowlist;
use App\Services\Auth\AuthOrchestrator;
use Database\Seeders\RhRoleMappingSeeder;
use Database\Seeders\RolesPermissionsSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        SystemSettingsSeeder::class,
        RolesPermissionsSeeder::class,
        RhRoleMappingSeeder::class,
    ]);
});

it('autoprovisiona usuario corporativo desde RH mock con rol mapeado', function () {
    $user = app(AuthOrchestrator::class)->loginOrProvision(
        provider: 'auth0',
        providerUserId: 'auth0|abc123',
        email: 'fbasave@gptservices.com',
    );

    expect($user->employee_id)->toBe('1001')
        ->and($user->puesto)->toBe('Gerente de Proyectos')
        ->and($user->departamento)->toBe('Proyectos')
        ->and($user->hasRole('gerente_proyectos'))->toBeTrue()
        ->and(AuthProvider::where('user_id', $user->id)->where('provider', 'auth0')->exists())->toBeTrue();
});

it('rechaza correo corporativo que no existe en RH', function () {
    expect(fn () => app(AuthOrchestrator::class)->loginOrProvision(
        provider: 'auth0',
        providerUserId: 'auth0|nonexistent',
        email: 'noexiste@gptservices.com',
    ))->toThrow(RuntimeException::class);
});

it('rechaza correo no corporativo que no está en allowlist', function () {
    expect(fn () => app(AuthOrchestrator::class)->loginOrProvision(
        provider: 'google',
        providerUserId: 'google|xyz',
        email: 'random@gmail.com',
    ))->toThrow(RuntimeException::class);
});

it('admite correo no corporativo en allowlist y aplica role_default', function () {
    EmailAllowlist::create([
        'email' => 'consultor@externo.com',
        'allowed_providers' => ['google'],
        'role_default' => 'auditor_externo',
    ]);

    $user = app(AuthOrchestrator::class)->loginOrProvision(
        provider: 'google',
        providerUserId: 'google|123',
        email: 'consultor@externo.com',
        profileData: ['name' => 'Consultor Externo'],
    );

    expect($user->name)->toBe('Consultor Externo')
        ->and($user->hasRole('auditor_externo'))->toBeTrue();
});

it('vincula proveedor adicional al mismo usuario al hacer login con segundo proveedor', function () {
    $first = app(AuthOrchestrator::class)->loginOrProvision(
        provider: 'auth0',
        providerUserId: 'auth0|fb',
        email: 'fbasave@gptservices.com',
    );

    $second = app(AuthOrchestrator::class)->loginOrProvision(
        provider: 'google',
        providerUserId: 'google|fb',
        email: 'fbasave@gptservices.com',
    );

    expect($first->id)->toBe($second->id)
        ->and($first->authProviders()->count())->toBe(2);
});

it('reutiliza la sesión cuando el proveedor ya existe', function () {
    $first = app(AuthOrchestrator::class)->loginOrProvision(
        provider: 'auth0',
        providerUserId: 'auth0|fb',
        email: 'fbasave@gptservices.com',
    );

    $second = app(AuthOrchestrator::class)->loginOrProvision(
        provider: 'auth0',
        providerUserId: 'auth0|fb',
        email: 'fbasave@gptservices.com',
    );

    expect($first->id)->toBe($second->id)
        ->and($first->authProviders()->count())->toBe(1);
});
