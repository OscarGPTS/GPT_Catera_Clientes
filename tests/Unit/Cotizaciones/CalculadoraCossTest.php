<?php

use App\Services\Cotizaciones\CalculadoraCoss;

it('aplica las fórmulas COSS en orden con factores redondos', function () {
    $svc = new CalculadoraCoss;

    $r = $svc->calcular(
        partidas: [
            ['costo_total' => 1000.00],
        ],
        factores: ['indirectos' => 0.10, 'admin' => 0.10, 'utilidad' => 0.30],
    );

    // costo_directo = 1000
    // indirectos    = 1000 * 0.10            = 100
    // admin         = (1000 + 100) * 0.10     = 110
    // base          = 1000 + 100 + 110        = 1210
    // utilidad      = 1210 * 0.30             = 363
    // precio_venta  = 1210 + 363              = 1573
    // margen_neto   = 363 / 1573              = 0.23076...

    expect(round($r->costoDirecto, 2))->toBe(1000.0)
        ->and(round($r->indirectos, 2))->toBe(100.0)
        ->and(round($r->admin, 2))->toBe(110.0)
        ->and(round($r->base, 2))->toBe(1210.0)
        ->and(round($r->utilidad, 2))->toBe(363.0)
        ->and(round($r->precioVenta, 2))->toBe(1573.0)
        ->and(round($r->margenNeto, 4))->toBe(0.2308);
});

it('suma costos cuando se proveen cantidad y costo_unitario', function () {
    $svc = new CalculadoraCoss;

    $r = $svc->calcular(
        partidas: [
            ['cantidad' => 2, 'costo_unitario' => 50],
            ['cantidad' => 4, 'costo_unitario' => 25],
        ],
        factores: ['indirectos' => 0, 'admin' => 0, 'utilidad' => 0],
    );

    expect($r->costoDirecto)->toBe(200.0)
        ->and($r->precioVenta)->toBe(200.0);
});

it('rechaza factores negativos', function () {
    $svc = new CalculadoraCoss;

    expect(fn () => $svc->calcular([['costo_total' => 100]], ['indirectos' => -0.1]))
        ->toThrow(InvalidArgumentException::class);
});

it('TODO Texmelucan: cd 61717.61 -> pv 96998.30 margen 41.59% con factores reales del Excel', function () {
    // TODO M3: Cuando esté disponible `FO-GPT-VTS-01-F`, alimentar este test con
    // las 19 partidas reales y los factores exactos. Hoy se valida la fórmula
    // declarada en el plan, no el fixture al centavo.
    $this->markTestSkipped('Requiere FO-GPT-VTS-01-F (fixture Texmelucan).');
})->skip();
