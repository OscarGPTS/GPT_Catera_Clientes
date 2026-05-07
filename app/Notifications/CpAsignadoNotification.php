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
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Nuevo CP asignado: {$this->proyecto->cp_numero}")
            ->line("Te asignaron el CP {$this->proyecto->cp_numero} ({$this->proyecto->cliente?->razon_social}).")
            ->action('Ver CP', url('/proyectos/'.$this->proyecto->id));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'proyecto_id' => $this->proyecto->id,
            'cp_numero' => $this->proyecto->cp_numero,
            'cliente' => $this->proyecto->cliente?->razon_social,
            'tipo' => 'cp_asignado',
        ];
    }
}
