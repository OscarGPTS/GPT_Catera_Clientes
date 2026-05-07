<?php

use App\Services\Proyectos\SecuenciasService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('asigna CP secuenciales con formato CP-XXX/AA', function () {
    $svc = app(SecuenciasService::class);

    expect($svc->asignarCp(2026))->toBe('CP-001/26')
        ->and($svc->asignarCp(2026))->toBe('CP-002/26')
        ->and($svc->asignarCp(2026))->toBe('CP-003/26');
});

it('asigna DN secuenciales con formato DN-XXX/AA', function () {
    $svc = app(SecuenciasService::class);

    expect($svc->asignarDn(2025))->toBe('DN-001/25')
        ->and($svc->asignarDn(2025))->toBe('DN-002/25');
});

it('mantiene secuencias separadas por año', function () {
    $svc = app(SecuenciasService::class);

    expect($svc->asignarCp(2025))->toBe('CP-001/25')
        ->and($svc->asignarCp(2026))->toBe('CP-001/26')
        ->and($svc->asignarCp(2025))->toBe('CP-002/25');
});
