<?php

namespace App\Services\Asignaciones;

use App\Models\AsignacionPersona;
use App\Models\Proyecto;
use App\Models\User;

/**
 * D12. Genera snapshot mensual por usuario del Departamento de Proyectos.
 */
class SnapshotAsignacionesService
{
    private const ROLES_PROYECTOS = [
        'gerente_proyectos',
        'ingeniero_costos',
        'ingeniero_proyectos',
        'trainee_proyectos',
        'gerente_operaciones',
    ];

    public function generar(int $año, int $mes): int
    {
        $users = User::role(self::ROLES_PROYECTOS)->where('status', 'active')->get();
        $count = 0;

        foreach ($users as $user) {
            $cpAsignados = Proyecto::query()
                ->where(fn ($q) => $q
                    ->where('ingeniero_costos_id', $user->id)
                    ->orWhere('ingeniero_proyectos_id', $user->id)
                    ->orWhere('trainee_id', $user->id)
                    ->orWhere('gerente_proyectos_id', $user->id))
                ->whereNotNull('cp_numero')
                ->whereNull('dn_numero')
                ->whereYear('created_at', $año)
                ->whereMonth('created_at', $mes)
                ->count();

            $cpEjecutados = Proyecto::query()
                ->where(fn ($q) => $q
                    ->where('ingeniero_costos_id', $user->id)
                    ->orWhere('ingeniero_proyectos_id', $user->id)
                    ->orWhere('trainee_id', $user->id))
                ->whereNotNull('cp_numero')
                ->whereIn('estado', ['cotizado', 'presentado', 'adjudicado_pendiente', 'adjudicado_firmado'])
                ->count();

            $dnActivos = Proyecto::query()
                ->where(fn ($q) => $q
                    ->where('gerente_proyectos_id', $user->id)
                    ->orWhere('ingeniero_proyectos_id', $user->id)
                    ->orWhere('trainee_id', $user->id))
                ->whereNotNull('dn_numero')
                ->where('estado', 'en_ejecucion')
                ->count();

            $dnCerrados = Proyecto::query()
                ->where(fn ($q) => $q
                    ->where('gerente_proyectos_id', $user->id)
                    ->orWhere('ingeniero_proyectos_id', $user->id))
                ->whereNotNull('dn_numero')
                ->where('estado', 'cerrado')
                ->count();

            $dnCancelados = Proyecto::query()
                ->where(fn ($q) => $q->where('gerente_proyectos_id', $user->id))
                ->whereNotNull('dn_numero')
                ->whereIn('estado', ['cancelado', 'perdido'])
                ->count();

            AsignacionPersona::updateOrCreate(
                ['user_id' => $user->id, 'mes' => $mes, 'año' => $año],
                [
                    'cp_asignados' => $cpAsignados,
                    'cp_ejecutados' => $cpEjecutados,
                    'cp_remanentes' => max(0, $cpAsignados - $cpEjecutados),
                    'dn_activos' => $dnActivos,
                    'dn_cerrados' => $dnCerrados,
                    'dn_cancelados' => $dnCancelados,
                    'total_servicio' => $cpAsignados + $dnActivos,
                    'total_suministro' => 0,
                    'generado_at' => now(),
                ],
            );

            $count++;
        }

        return $count;
    }
}
