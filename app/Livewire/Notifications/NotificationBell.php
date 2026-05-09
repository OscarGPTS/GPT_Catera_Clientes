<?php

namespace App\Livewire\Notifications;

use App\Models\ChatMencion;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Attributes\Computed;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $abierto = false;

    #[Computed]
    public function menciones()
    {
        return ChatMencion::where('user_id', auth()->id())
            ->whereNull('leido_at')
            ->with(['mensaje.canal:id,nombre,tipo,contexto_id', 'mensaje.user:id,name'])
            ->latest()
            ->take(10)
            ->get();
    }

    #[Computed]
    public function notificaciones()
    {
        return auth()->user()
            ?->unreadNotifications()
            ?->latest()
            ?->take(15)
            ?->get()
            ?? collect();
    }

    #[Computed]
    public function totalNoLeidas(): int
    {
        $menciones = ChatMencion::where('user_id', auth()->id())->whereNull('leido_at')->count();
        $notifs = (int) (auth()->user()?->unreadNotifications()->count() ?? 0);

        return $menciones + $notifs;
    }

    public function toggle(): void
    {
        $this->abierto = ! $this->abierto;
    }

    public function marcarTodoLeido(): void
    {
        ChatMencion::where('user_id', auth()->id())
            ->whereNull('leido_at')
            ->update(['leido_at' => now()]);

        auth()->user()?->unreadNotifications->markAsRead();

        unset($this->menciones, $this->notificaciones, $this->totalNoLeidas);
    }

    public function marcarLeida(string $notificacionId): void
    {
        DatabaseNotification::where('id', $notificacionId)
            ->where('notifiable_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        unset($this->notificaciones, $this->totalNoLeidas);
    }

    public function render()
    {
        return view('livewire.notifications.notification-bell');
    }
}
