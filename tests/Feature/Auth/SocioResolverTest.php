<?php

use App\Models\SocioAllowlist;
use App\Models\User;
use App\Services\Auth\SocioResolver;
use App\Services\Rh\RhClientInterface;
use App\Services\Rh\RhClientMock;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(RhClientInterface::class, RhClientMock::class);
});

it('marca como socio al usuario con puesto Director General en RH', function () {
    $user = User::factory()->create(['email' => 'gguterrez@gptservices.com']);

    expect(app(SocioResolver::class)->isSocio($user))->toBeTrue();
});

it('respeta es_socio_override cuando RH no aplica', function () {
    $user = User::factory()->create([
        'email' => 'random@gptservices.com',
        'es_socio_override' => true,
    ]);

    expect(app(SocioResolver::class)->isSocio($user))->toBeTrue();
});

it('admite socio vía allowlist cuando no hay match en RH ni override', function () {
    $user = User::factory()->create(['email' => 'amigo@externo.com']);
    SocioAllowlist::create(['email' => 'amigo@externo.com']);

    expect(app(SocioResolver::class)->isSocio($user))->toBeTrue();
});

it('devuelve false cuando ningún criterio aplica', function () {
    $user = User::factory()->create([
        'email' => 'sin_privilegio@externo.com',
        'es_socio_override' => null,
    ]);

    expect(app(SocioResolver::class)->isSocio($user))->toBeFalse();
});
