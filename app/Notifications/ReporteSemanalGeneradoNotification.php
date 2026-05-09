<?php

namespace App\Notifications;

use App\Models\ReporteSemanal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReporteSemanalGeneradoNotification extends Notification
{
    use Queueable;

    public function __construct(public ReporteSemanal $reporte) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $proyecto = $this->reporte->proyecto;
        $semana = $this->reporte->semana_inicio?->format('Y-m-d').' → '.$this->reporte->semana_fin?->format('Y-m-d');

        return [
            'tipo' => 'reporte_semanal_generado',
            'proyecto_id' => $proyecto?->id,
            'reporte_id' => $this->reporte->id,
            'cp_numero' => $proyecto?->cp_numero,
            'titulo' => "Reporte semanal · {$proyecto?->cp_numero}",
            'mensaje' => "Reporte de la semana {$semana} listo para revisión y envío al cliente.",
            'url' => $proyecto ? route('reportes.show', [$proyecto, $this->reporte]) : null,
        ];
    }
}
