<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'minuta_entrega_obligatoria',
                'value' => ['v' => false],
                'type' => 'boolean',
                'group' => 'proyectos',
                'label' => 'Minuta de Entrega CP→DN obligatoria',
                'description' => 'D10. Cuando es true bloquea el cambio a en_ejecucion hasta que la minuta esté firmada.',
            ],
            [
                'key' => 'bloqueo_cierre_dossier_incompleto',
                'value' => ['v' => true],
                'type' => 'boolean',
                'group' => 'libro_proyecto',
                'label' => 'Bloquear cierre si dossier incompleto',
                'description' => 'D11. Cuando es true impide emitir Carta Finiquito si el Libro de Proyecto está por debajo de 100%.',
            ],
            [
                'key' => 'bloqueo_cierre_post_mortem_pendiente',
                'value' => ['v' => false],
                'type' => 'boolean',
                'group' => 'libro_proyecto',
                'label' => 'Bloquear cierre si post-mortem pendiente',
                'description' => 'Cuando es true impide cerrar el proyecto sin post-mortem firmado.',
            ],
            [
                'key' => 'auth_dominios_corporativos',
                'value' => ['v' => ['gptservices.com', 'satechenergy.com']],
                'type' => 'array',
                'group' => 'auth',
                'label' => 'Dominios corporativos',
                'description' => 'Dominios cuyos usuarios se autoprovisionan vía Auth0 + API RH.',
            ],
            [
                'key' => 'concentracion_cliente_alerta_umbral',
                'value' => ['v' => 50],
                'type' => 'integer',
                'group' => 'ejecutivo',
                'label' => 'Umbral de alerta de concentración por cliente (%)',
                'description' => 'Porcentaje del pipeline a partir del cual se dispara una alerta visible en la vista ejecutiva.',
            ],
        ];

        foreach ($settings as $row) {
            SystemSetting::updateOrCreate(
                ['key' => $row['key']],
                $row,
            );
        }
    }
}
