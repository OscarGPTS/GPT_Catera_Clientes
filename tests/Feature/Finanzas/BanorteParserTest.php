<?php

use App\Services\Finanzas\Parsers\BanorteParser;

it('parsea CSV Banorte con NATURALEZA C/D', function () {
    $csv = <<<'CSV'
FECHA;CONCEPTO;MONTO;NATURALEZA;SALDO
2026-01-05;Transferencia recibida;125000.00;C;225000.00
2026-01-06;Pago a proveedor;25000.00;D;200000.00
CSV;
    $tmp = tempnam(sys_get_temp_dir(), 'banorte').'.csv';
    file_put_contents($tmp, $csv);

    $movs = (new BanorteParser)->parse($tmp);

    expect($movs)->toHaveCount(2)
        ->and($movs[0]['tipo'])->toBe('ingreso')
        ->and($movs[0]['monto'])->toBe(125000.0)
        ->and($movs[1]['tipo'])->toBe('egreso')
        ->and($movs[1]['monto'])->toBe(25000.0);

    unlink($tmp);
});

it('infiere tipo por signo cuando no hay NATURALEZA', function () {
    $csv = "FECHA;CONCEPTO;MONTO;SALDO\n2026-01-05;Test;-5000.00;100\n2026-01-06;Test2;3000.00;103";
    $tmp = tempnam(sys_get_temp_dir(), 'banorte').'.csv';
    file_put_contents($tmp, $csv);

    $movs = (new BanorteParser)->parse($tmp);

    expect($movs)->toHaveCount(2)
        ->and($movs[0]['tipo'])->toBe('egreso')
        ->and($movs[0]['monto'])->toBe(5000.0)
        ->and($movs[1]['tipo'])->toBe('ingreso');

    unlink($tmp);
});

it('rechaza archivo sin header CONCEPTO', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'banorte').'.csv';
    file_put_contents($tmp, 'no,hay,header');

    expect(fn () => (new BanorteParser)->parse($tmp))->toThrow(RuntimeException::class);

    unlink($tmp);
});
