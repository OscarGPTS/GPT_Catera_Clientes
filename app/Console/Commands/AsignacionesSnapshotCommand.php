<?php

namespace App\Console\Commands;

use App\Services\Asignaciones\SnapshotAsignacionesService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AsignacionesSnapshotCommand extends Command
{
    protected $signature = 'asignaciones:snapshot
        {--mes=current : "current" o "YYYY-MM"}';

    protected $description = 'Genera el snapshot mensual de asignación por persona (D12).';

    public function handle(SnapshotAsignacionesService $svc): int
    {
        $value = (string) $this->option('mes');

        if ($value === 'current') {
            $año = now()->year;
            $mes = now()->month;
        } else {
            try {
                $date = Carbon::createFromFormat('Y-m', $value);
                $año = $date->year;
                $mes = $date->month;
            } catch (\Throwable $e) {
                $this->error("Formato inválido para --mes. Usa 'current' o 'YYYY-MM'.");

                return self::FAILURE;
            }
        }

        $count = $svc->generar($año, $mes);
        $this->info("Snapshot generado para {$count} usuarios ({$año}-".str_pad((string) $mes, 2, '0', STR_PAD_LEFT).').');

        return self::SUCCESS;
    }
}
