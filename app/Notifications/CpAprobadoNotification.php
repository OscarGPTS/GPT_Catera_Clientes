<?php

namespace App\Notifications;

use App\Models\Proyecto;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CpAprobadoNotification extends Notification
{
    use Queueable;

    public function __construct(public Proyecto $proyecto) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'cp_aprobado',
            'proyecto_id' => $this->proyecto->id,
            'cp_numero' => $this->proyecto->cp_numero,
            'cliente' => $this->proyecto->cliente?->razon_social,
            'titulo' => "CP {$this->proyecto->cp_numero} aprobado por el comité",
            'mensaje' => 'Te asignaron como gerente de proyectos. Procede a cotizar.',
            'url' => route('oportunidades.show', $this->proyecto),
        ];
    }
}
