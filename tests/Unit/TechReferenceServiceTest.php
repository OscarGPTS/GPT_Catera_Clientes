<?php

use App\Services\Proyectos\TechReferenceService;

it('valida el Tech Reference de Texmelucan', function () {
    $svc = new TechReferenceService;

    expect($svc->validar('250121-0-IGA-HTP x _HT 30"x 10" Texmelucan'))->toBeTrue();
});

it('rechaza formatos inválidos', function () {
    $svc = new TechReferenceService;

    expect($svc->validar('mal-formato'))->toBeFalse()
        ->and($svc->validar('250121-IGA-HTP x _algo'))->toBeFalse()
        ->and($svc->validar(''))->toBeFalse();
});

it('construye un Tech Reference válido a partir de campos', function () {
    $svc = new TechReferenceService;

    $tr = $svc->construir(
        fecha: '250121',
        consecutivo: 0,
        aliasCliente: 'iga',
        sublineaCodigo: 'htp',
        dimensiones: 'x',
        descripcion: 'HT 30"x 10" Texmelucan',
    );

    expect($tr)->toBe('250121-0-IGA-HTP x _HT 30"x 10" Texmelucan')
        ->and($svc->validar($tr))->toBeTrue();
});
