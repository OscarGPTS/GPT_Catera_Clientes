<?php

use App\Jobs\GenerarSnapshotAsignacionesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// D12. Snapshot mensual de asignaciones — día 1 de cada mes 00:30
Schedule::job(new GenerarSnapshotAsignacionesJob)->monthlyOn(1, '00:30');
