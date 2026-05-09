<?php

namespace App\Notifications;

use App\Models\Proyecto;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PostMortemRequeridoNotification extends Notification
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
            'tipo' => 'post_mortem_requerido',
            'proyecto_id' => $this->proyecto->id,
            'cp_numero' => $this->proyecto->cp_numero,
            'titulo' => "Post-mortem pendiente · {$this->proyecto->cp_numero}",
            'mensaje' => 'El proyecto entró en cierre. Levanta el post-mortem para liberar el cierre formal (D12).',
            'url' => route('cierre.show', $this->proyecto),
        ];
    }
}
