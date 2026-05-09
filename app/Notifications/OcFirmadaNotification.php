<?php

namespace App\Notifications;

use App\Models\Proyecto;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OcFirmadaNotification extends Notification
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
            'tipo' => 'oc_firmada',
            'proyecto_id' => $this->proyecto->id,
            'cp_numero' => $this->proyecto->cp_numero,
            'dn_numero' => $this->proyecto->dn_numero,
            'titulo' => "OC firmada · {$this->proyecto->dn_numero}",
            'mensaje' => "Cliente firmó OC para {$this->proyecto->cp_numero}. DN asignado: {$this->proyecto->dn_numero}. Levanta minuta de entrega CP→DN.",
            'url' => route('minutas.show', $this->proyecto),
        ];
    }
}
