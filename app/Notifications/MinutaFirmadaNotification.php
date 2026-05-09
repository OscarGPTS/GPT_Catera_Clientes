<?php

namespace App\Notifications;

use App\Models\MinutaEntrega;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MinutaFirmadaNotification extends Notification
{
    use Queueable;

    public function __construct(public MinutaEntrega $minuta) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $proyecto = $this->minuta->proyecto;

        return [
            'tipo' => 'minuta_firmada',
            'proyecto_id' => $proyecto?->id,
            'minuta_id' => $this->minuta->id,
            'cp_numero' => $proyecto?->cp_numero,
            'titulo' => "Minuta CP→DN firmada · {$proyecto?->cp_numero}",
            'mensaje' => 'Todos los participantes firmaron. Ya puedes iniciar ejecución (D10).',
            'url' => $proyecto ? route('oportunidades.show', $proyecto) : null,
        ];
    }
}
