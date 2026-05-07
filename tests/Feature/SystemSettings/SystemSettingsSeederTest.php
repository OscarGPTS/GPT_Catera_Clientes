<?php

use App\Models\SystemSetting;
use Database\Seeders\SystemSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds the five default system settings', function () {
    $this->seed(SystemSettingsSeeder::class);

    expect(SystemSetting::count())->toBe(5)
        ->and(SystemSetting::get('minuta_entrega_obligatoria'))->toBeFalse()
        ->and(SystemSetting::get('bloqueo_cierre_dossier_incompleto'))->toBeTrue()
        ->and(SystemSetting::get('bloqueo_cierre_post_mortem_pendiente'))->toBeFalse()
        ->and(SystemSetting::get('auth_dominios_corporativos'))
        ->toBe(['gptservices.com', 'satechenergy.com'])
        ->and(SystemSetting::get('concentracion_cliente_alerta_umbral'))->toBe(50);
});

it('returns the default when a key does not exist', function () {
    expect(SystemSetting::get('clave_inexistente', 'fallback'))->toBe('fallback');
});

it('writes a setting and reads back the typed value', function () {
    SystemSetting::set('mi_flag', true);

    expect(SystemSetting::get('mi_flag'))->toBeTrue();
});
