<?php

use App\Models\CierreMensual;
use App\Models\CuentaBancaria;
use App\Models\EstadoCuenta;
use App\Models\MovimientoBancario;
use Database\Seeders\ComercialCatalogosSeeder;
use Database\Seeders\FinanzasSeeder;
use Database\Seeders\ProyectosTestSeeder;
use Database\Seeders\RhRoleMappingSeeder;
use Database\Seeders\RolesPermissionsSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Database\Seeders\TestUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        SystemSettingsSeeder::class,
        RolesPermissionsSeeder::class,
        RhRoleMappingSeeder::class,
        ComercialCatalogosSeeder::class,
        TestUsersSeeder::class,
        ProyectosTestSeeder::class,
        FinanzasSeeder::class,
    ]);
});

it('crea 2 cuentas (BBVA + Banorte)', function () {
    expect(CuentaBancaria::count())->toBe(2)
        ->and(CuentaBancaria::where('banco', 'bbva')->exists())->toBeTrue()
        ->and(CuentaBancaria::where('banco', 'banorte')->exists())->toBeTrue();
});

it('crea 1 estado de cuenta con 8 movimientos', function () {
    expect(EstadoCuenta::count())->toBe(1)
        ->and(MovimientoBancario::count())->toBe(8);
});

it('al menos un movimiento queda conciliado automáticamente por match de CP', function () {
    expect(MovimientoBancario::conciliado()->count())->toBeGreaterThan(0);
});

it('es idempotente — correr dos veces no agrega más cuentas', function () {
    $this->seed(FinanzasSeeder::class);
    expect(CuentaBancaria::count())->toBe(2);
});

it('genera 1 cierre gerencial aprobado + 1 SAT borrador del mes anterior', function () {
    expect(CierreMensual::count())->toBe(2);

    $gerencial = CierreMensual::where('tipo', 'gerencial_avance')->first();
    expect($gerencial)->not->toBeNull()
        ->and($gerencial->status)->toBe('aprobado')
        ->and($gerencial->secciones)->toHaveCount(3);

    $sat = CierreMensual::where('tipo', 'contable_sat')->first();
    expect($sat)->not->toBeNull()
        ->and($sat->status)->toBe('borrador');
});
