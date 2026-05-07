<?php

namespace App\Jobs;

use App\Services\Asignaciones\SnapshotAsignacionesService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as FoundationQueueable;

class GenerarSnapshotAsignacionesJob implements ShouldQueue
{
    use FoundationQueueable;

    public function __construct(
        public ?int $año = null,
        public ?int $mes = null,
    ) {}

    public function handle(SnapshotAsignacionesService $svc): void
    {
        $año = $this->año ?? now()->year;
        $mes = $this->mes ?? now()->month;

        $svc->generar($año, $mes);
    }
}
