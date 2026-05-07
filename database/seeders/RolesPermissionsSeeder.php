<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * D13. 22 roles del procedimiento PRO-GPT-PYT-01 con permisos independientes (sin herencia).
 */
class RolesPermissionsSeeder extends Seeder
{
    /** @var array<string, array<int, string>> */
    private array $matrix = [
        'super_admin' => ['*'],
        'direccion_general' => [
            'oportunidades.*', 'cotizaciones.*', 'proyectos.*', 'libro.*',
            'finanzas.read', 'cierres.read', 'cierres.approve',
            'asignaciones.read', 'admin.*', 'ejecutivo.read',
            'minutas.create', 'minutas.sign', 'viaticos.approve',
            'usuarios.invite', 'reportes.export',
        ],
        'socio' => [
            'ejecutivo.read', 'finanzas.read', 'cierres.read', 'reportes.export',
            'oportunidades.read', 'proyectos.read',
        ],
        'comite_socios' => [
            'ejecutivo.read', 'finanzas.read', 'cierres.read', 'cierres.approve',
            'oportunidades.read', 'proyectos.read', 'reportes.export',
        ],
        'director_dn' => [
            'oportunidades.*', 'cp.approve', 'cotizaciones.read',
            'minutas.create', 'minutas.sign', 'proyectos.read',
            'clientes.*', 'reportes.export',
        ],
        'comercial' => [
            'oportunidades.create', 'oportunidades.read', 'oportunidades.update',
            'cotizaciones.read', 'clientes.create', 'clientes.read', 'clientes.update',
        ],
        'gerente_proyectos' => [
            'cp.*', 'dn.*', 'proyectos.*', 'cotizaciones.*', 'libro.*',
            'kom.*', 'cronograma.*', 'bom.*', 'suministros.*',
            'bitacoras.read', 'reportes.export', 'asignaciones.read', 'asignaciones.write',
            'minutas.create', 'minutas.sign', 'viaticos.approve',
            'finiquitos.create', 'postmortem.create',
        ],
        'ingeniero_costos' => [
            'cp.read', 'cp.update', 'cotizaciones.create', 'cotizaciones.read', 'cotizaciones.update',
            'fichas.create', 'fichas.update', 'proyectos.read',
            'solicitudes_internas.create',
        ],
        'ingeniero_proyectos' => [
            'cp.read', 'cp.update', 'dn.read', 'dn.update',
            'cotizaciones.read', 'cotizaciones.update',
            'kom.create', 'kom.update', 'cronograma.update',
            'bom.update', 'suministros.update', 'bitacoras.create', 'bitacoras.update',
            'libro.update', 'reportes_semanales.create', 'reportes_semanales.update',
            'viaticos.request', 'finiquitos.draft',
        ],
        'trainee_proyectos' => [
            'cp.read', 'dn.read', 'cotizaciones.draft',
            'bitacoras.draft', 'libro.upload',
        ],
        'gerente_operaciones' => [
            'cp.read', 'dn.*', 'proyectos.read',
            'recursos.assign', 'desviaciones.manage',
            'kom.*', 'bom.*', 'suministros.*',
            'reportes.export', 'asignaciones.read',
        ],
        'serv_tecnicos' => ['proyectos.read', 'bitacoras.create', 'bom.update'],
        'soldadura' => ['proyectos.read', 'bitacoras.create'],
        'serv_generales' => ['viaticos.review', 'viaticos.approve', 'logistica.manage'],
        'qhse' => [
            'permisos.manage', 'incidentes.manage', 'libro.update',
            'libro.section_h.manage', 'finiquitos.review',
        ],
        'almacen' => ['inventario.manage', 'movimientos.manage', 'bom.update'],
        'manufactura' => ['opm.manage', 'fabricacion.manage'],
        'compras' => [
            'solicitudes_internas.attend', 'solicitudes_internas.read',
            'oc.manage', 'proveedores.manage', 'bom.read',
        ],
        'ingenieria_diseño' => [
            'solicitudes_internas.attend', 'solicitudes_internas.read',
            'libro.section_b.manage',
        ],
        'cfo' => [
            'finanzas.*', 'cuentas.*', 'cierres.*', 'estados_cuenta.*',
            'movimientos.*', 'reportes.export', 'ejecutivo.read',
        ],
        'analista_financiero' => [
            'finanzas.read', 'cuentas.read', 'cierres.read',
            'estados_cuenta.read', 'movimientos.update',
        ],
        'finanzas_general' => [
            'finanzas.read', 'movimientos.read',
        ],
        'cliente_externo' => ['proyectos.read_own'],
        'auditor_externo' => ['proyectos.read', 'cotizaciones.read', 'libro.read', 'finanzas.read_only'],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect($this->matrix)
            ->flatten()
            ->reject(fn ($p) => $p === '*')
            ->reject(fn ($p) => str_ends_with($p, '.*'))
            ->unique()
            ->values()
            ->all();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Seed extra permissions for known prefixes (so wildcards expand to something)
        $extraByPrefix = [
            'oportunidades' => ['create', 'read', 'update', 'delete', 'approve'],
            'cotizaciones' => ['create', 'read', 'update', 'delete', 'approve', 'draft'],
            'cp' => ['create', 'read', 'update', 'approve', 'delete'],
            'dn' => ['create', 'read', 'update', 'close', 'cancel'],
            'proyectos' => ['create', 'read', 'update', 'delete', 'read_own'],
            'libro' => ['create', 'read', 'update', 'upload'],
            'finanzas' => ['read', 'create', 'update', 'read_only'],
            'cuentas' => ['create', 'read', 'update', 'delete'],
            'cierres' => ['create', 'read', 'update', 'approve'],
            'estados_cuenta' => ['create', 'read', 'update', 'delete'],
            'movimientos' => ['read', 'create', 'update', 'delete'],
            'admin' => ['read', 'manage'],
            'kom' => ['create', 'read', 'update', 'delete'],
            'cronograma' => ['create', 'read', 'update', 'delete'],
            'bom' => ['create', 'read', 'update', 'delete'],
            'suministros' => ['create', 'read', 'update', 'delete'],
            'clientes' => ['create', 'read', 'update', 'delete'],
        ];

        foreach ($extraByPrefix as $prefix => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$prefix}.{$action}", 'guard_name' => 'web']);
            }
        }

        foreach ($this->matrix as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            if (in_array('*', $perms, true)) {
                $role->syncPermissions(Permission::all());

                continue;
            }

            $expanded = [];
            foreach ($perms as $perm) {
                if (str_ends_with($perm, '.*')) {
                    $prefix = rtrim($perm, '.*').'.';
                    $expanded = array_merge(
                        $expanded,
                        Permission::where('name', 'like', $prefix.'%')->pluck('name')->all(),
                    );
                } else {
                    $expanded[] = $perm;
                }
            }

            $role->syncPermissions(array_values(array_unique($expanded)));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
