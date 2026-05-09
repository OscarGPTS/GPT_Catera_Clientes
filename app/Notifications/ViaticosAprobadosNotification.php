<?php

namespace App\Notifications;

use App\Models\SolicitudViaticos;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ViaticosAprobadosNotification extends Notification
{
    use Queueable;

    public function __construct(public SolicitudViaticos $solicitud) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $total = (float) $this->solicitud->partidas->sum('monto_estimado');

        return [
            'tipo' => 'viaticos_aprobados',
            'proyecto_id' => $this->solicitud->proyecto_id,
            'solicitud_id' => $this->solicitud->id,
            'cp_numero' => $this->solicitud->proyecto?->cp_numero,
            'titulo' => "Viáticos aprobados · {$this->solicitud->proyecto?->cp_numero}",
            'mensaje' => 'Tu solicitud de viáticos fue aprobada por Dirección. Total: $'.number_format($total, 2).'.',
            'url' => route('viaticos.show', [$this->solicitud->proyecto, $this->solicitud]),
        ];
    }
}
