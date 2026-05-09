<?php

namespace App\Notifications;

use App\Models\Proyecto;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CpAsignadoNotification extends Notification
{
    use Queueable;

    public function __construct(public Proyecto $proyecto) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Nuevo CP asignado: {$this->proyecto->cp_numero}")
            ->line("Te asignaron el CP {$this->proyecto->cp_numero} ({$this->proyecto->cliente?->razon_social}).")
            ->action('Ver CP', route('oportunidades.show', $this->proyecto));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'cp_asignado',
            'proyecto_id' => $this->proyecto->id,
            'cp_numero' => $this->proyecto->cp_numero,
            'cliente' => $this->proyecto->cliente?->razon_social,
            'titulo' => "Nuevo CP asignado: {$this->proyecto->cp_numero}",
            'mensaje' => "Te asignaron el CP {$this->proyecto->cp_numero} ({$this->proyecto->cliente?->razon_social}).",
            'url' => route('oportunidades.show', $this->proyecto),
        ];
    }
}
