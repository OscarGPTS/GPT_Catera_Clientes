<?php

use App\Models\CuentaBancaria;
use App\Models\FinanzasAudit;
use App\Models\User;
use App\Services\Finanzas\EstadoCuentaService;
use Database\Seeders\ComercialCatalogosSeeder;
use Database\Seeders\RhRoleMappingSeeder;
use Database\Seeders\RolesPermissionsSeeder;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        SystemSettingsSeeder::class,
        RolesPermissionsSeeder::class,
        RhRoleMappingSeeder::class,
        ComercialCatalogosSeeder::class,
    ]);

    $this->cfo = User::factory()->create();
    $this->cfo->assignRole('cfo');

    $this->cuenta = CuentaBancaria::create([
        'banco' => 'bbva',
        'numero_cuenta_enmascarado' => '****1234',
        'moneda' => 'MXN',
    ]);
});

it('importar estado de cuenta parsea CSV y guarda movimientos', function () {
    Storage::fake('local');

    $csv = "FECHA,DESCRIPCION,CARGO,ABONO,SALDO\n01/05/2026,Pago A,,1000.00,1000\n02/05/2026,Pago B,500.00,,500\n";
    $archivo = UploadedFile::fake()->createWithContent('estado.csv', $csv);

    $this->actingAs($this->cfo)
        ->post(route('finanzas.estados.store', $this->cuenta), [
            'mes' => 5,
            'año' => 2026,
            'archivo' => $archivo,
        ])
        ->assertRedirect();

    $estado = $this->cuenta->estadosCuenta()->first();

    expect($estado)->not->toBeNull()
        ->and($estado->total_movimientos)->toBe(2)
        ->and($estado->parseado_at)->not->toBeNull()
        ->and($estado->movimientos()->count())->toBe(2);

    expect(FinanzasAudit::where('action', 'estado_cuenta_importado')->exists())->toBeTrue();
});

it('rechaza mes inválido', function () {
    $this->actingAs($this->cfo)
        ->from(route('finanzas.estados.index', $this->cuenta))
        ->post(route('finanzas.estados.store', $this->cuenta), [
            'mes' => 13,
            'año' => 2026,
            'archivo' => UploadedFile::fake()->createWithContent('x.csv', 'FECHA,DESCRIPCION,CARGO,ABONO\n'),
        ])
        ->assertSessionHasErrors(['mes']);
});

it('re-importar reemplaza movimientos previos', function () {
    Storage::fake('local');

    $service = app(EstadoCuentaService::class);
    $csv1 = "FECHA,DESCRIPCION,CARGO,ABONO,SALDO\n01/05/2026,A,,100,100\n";
    $service->importar($this->cuenta, 5, 2026, UploadedFile::fake()->createWithContent('x.csv', $csv1), $this->cfo->id);

    $csv2 = "FECHA,DESCRIPCION,CARGO,ABONO,SALDO\n01/05/2026,B,,200,200\n02/05/2026,C,,300,500\n";
    $estado = $service->importar($this->cuenta, 5, 2026, UploadedFile::fake()->createWithContent('y.csv', $csv2), $this->cfo->id);

    expect($estado->total_movimientos)->toBe(2)
        ->and($estado->movimientos()->where('descripcion', 'A')->exists())->toBeFalse()
        ->and($estado->movimientos()->where('descripcion', 'B')->exists())->toBeTrue();
});

it('rechaza banco sin parser implementado', function () {
    $cuentaSantander = CuentaBancaria::create([
        'banco' => 'santander',
        'numero_cuenta_enmascarado' => '****1111',
        'moneda' => 'MXN',
    ]);

    Storage::fake('local');

    expect(fn () => app(EstadoCuentaService::class)->importar(
        $cuentaSantander, 5, 2026,
        UploadedFile::fake()->createWithContent('x.csv', "FECHA\n01/05/2026"),
        $this->cfo->id,
    ))->toThrow(RuntimeException::class);
});
