<?php

namespace App\Livewire\Notifications;

use App\Models\ChatMencion;
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
            ->take(20)
            ->get();
    }

    #[Computed]
    public function totalNoLeidas(): int
    {
        return ChatMencion::where('user_id', auth()->id())->whereNull('leido_at')->count();
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

        unset($this->menciones, $this->totalNoLeidas);
    }

    public function render()
    {
        return view('livewire.notifications.notification-bell');
    }
}
