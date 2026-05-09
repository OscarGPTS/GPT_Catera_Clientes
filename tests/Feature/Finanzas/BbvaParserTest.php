<?php

use App\Services\Finanzas\Parsers\BbvaParser;

it('parsea CSV BBVA con coma como separador', function () {
    $csv = <<<'CSV'
FECHA,DESCRIPCION,CARGO,ABONO,SALDO
"01/01/2026","Pago de servicio","","12500.00","100000.00"
"02/01/2026","Pago a proveedor","5000.00","","95000.00"
"03/01/2026","Comisión bancaria","250.00","","94750.00"
CSV;
    $tmp = tempnam(sys_get_temp_dir(), 'bbva').'.csv';
    file_put_contents($tmp, $csv);

    $movs = (new BbvaParser)->parse($tmp);

    expect($movs)->toHaveCount(3)
        ->and($movs[0]['fecha'])->toBe('2026-01-01')
        ->and($movs[0]['monto'])->toBe(12500.0)
        ->and($movs[0]['tipo'])->toBe('ingreso')
        ->and($movs[1]['monto'])->toBe(5000.0)
        ->and($movs[1]['tipo'])->toBe('egreso');

    unlink($tmp);
});

it('parsea CSV BBVA con punto y coma', function () {
    $csv = "FECHA;DESCRIPCION;CARGO;ABONO;SALDO\n01/02/2026;Test;;1000.00;1000.00";
    $tmp = tempnam(sys_get_temp_dir(), 'bbva').'.csv';
    file_put_contents($tmp, $csv);

    $movs = (new BbvaParser)->parse($tmp);

    expect($movs)->toHaveCount(1)
        ->and($movs[0]['monto'])->toBe(1000.0);

    unlink($tmp);
});

it('rechaza archivo sin header FECHA', function () {
    $tmp = tempnam(sys_get_temp_dir(), 'bbva').'.csv';
    file_put_contents($tmp, 'sin,header,validos');

    expect(fn () => (new BbvaParser)->parse($tmp))->toThrow(RuntimeException::class);

    unlink($tmp);
});

it('rechaza archivo inexistente', function () {
    expect(fn () => (new BbvaParser)->parse('/no/existe.csv'))->toThrow(RuntimeException::class);
});

it('ignora montos cero/vacíos', function () {
    $csv = "FECHA,DESCRIPCION,CARGO,ABONO,SALDO\n01/01/2026,Salto vacío,,,100\n02/01/2026,Real,,500.00,600";
    $tmp = tempnam(sys_get_temp_dir(), 'bbva').'.csv';
    file_put_contents($tmp, $csv);

    $movs = (new BbvaParser)->parse($tmp);

    expect($movs)->toHaveCount(1);

    unlink($tmp);
});

it('limpia comas de millares y signo $', function () {
    $csv = "FECHA,DESCRIPCION,CARGO,ABONO,SALDO\n01/01/2026,Grande,\"\$1,250,500.00\",,";
    $tmp = tempnam(sys_get_temp_dir(), 'bbva').'.csv';
    file_put_contents($tmp, $csv);

    $movs = (new BbvaParser)->parse($tmp);

    expect($movs)->toHaveCount(1)
        ->and($movs[0]['monto'])->toBe(1_250_500.0);

    unlink($tmp);
});
