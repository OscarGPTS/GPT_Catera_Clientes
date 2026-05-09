<?php

namespace App\Services\Finanzas;

use App\Models\CierreMensual;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Maneja transiciones del cierre mensual:
 *   borrador → aprobado (CFO) → cerrado (Comité Socios / DG, irreversible).
 */
class CierreApprovalService
{
    public function __construct(private readonly FinanzasAuditService $audit) {}

    public function aprobar(CierreMensual $cierre, int $userId): CierreMensual
    {
        if ($cierre->status !== 'borrador') {
            throw new RuntimeException("El cierre debe estar en 'borrador' para aprobar. Estado actual: {$cierre->status}.");
        }

        return DB::transaction(function () use ($cierre, $userId) {
            $cierre->update([
                'status' => 'aprobado',
                'aprobado_por_id' => $userId,
                'aprobado_at' => now(),
            ]);

            $this->audit->registrar('cierre_aprobado', $cierre, [
                'tipo' => $cierre->tipo,
                'periodo' => $cierre->periodoLabel(),
                'total' => $cierre->totalGeneral(),
            ], $userId);

            return $cierre->fresh();
        });
    }

    public function cerrar(CierreMensual $cierre, int $userId): CierreMensual
    {
        if ($cierre->status !== 'aprobado') {
            throw new RuntimeException("El cierre debe estar 'aprobado' para cerrar. Estado actual: {$cierre->status}.");
        }

        return DB::transaction(function () use ($cierre, $userId) {
            $cierre->update(['status' => 'cerrado']);

            $this->audit->registrar('cierre_cerrado', $cierre, [
                'tipo' => $cierre->tipo,
                'periodo' => $cierre->periodoLabel(),
            ], $userId);

            return $cierre->fresh();
        });
    }

    public function regresarBorrador(CierreMensual $cierre, int $userId, string $razon): CierreMensual
    {
        if ($cierre->status === 'cerrado') {
            throw new RuntimeException('No se puede modificar un cierre ya cerrado.');
        }

        if ($cierre->status === 'borrador') {
            return $cierre;
        }

        return DB::transaction(function () use ($cierre, $userId, $razon) {
            $cierre->update([
                'status' => 'borrador',
                'aprobado_por_id' => null,
                'aprobado_at' => null,
            ]);

            $this->audit->registrar('cierre_regresado_borrador', $cierre, ['razon' => $razon], $userId);

            return $cierre->fresh();
        });
    }
}
