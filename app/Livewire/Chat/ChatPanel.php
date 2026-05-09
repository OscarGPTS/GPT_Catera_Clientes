<?php

namespace App\Livewire\Chat;

use App\Models\ChatCanal;
use App\Services\Chat\ChatService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class ChatPanel extends Component
{
    public ?int $canalId = null;

    public string $mensaje = '';

    public function mount(?int $canalId = null): void
    {
        $this->canalId = $canalId ?? $this->canales()->first()?->id;
    }

    #[Computed]
    public function canales()
    {
        $userId = auth()->id();

        return ChatCanal::whereHas('miembros', fn ($q) => $q->where('user_id', $userId))
            ->orderBy('nombre')
            ->get()
            ->map(function (ChatCanal $c) use ($userId) {
                $c->no_leidos = $c->mensajesNoLeidosPara($userId);

                return $c;
            });
    }

    #[Computed]
    public function canal()
    {
        if (! $this->canalId) {
            return null;
        }

        return ChatCanal::with(['miembros:id,name,email'])->find($this->canalId);
    }

    #[Computed]
    public function mensajes()
    {
        if (! $this->canalId) {
            return collect();
        }

        return ChatCanal::find($this->canalId)
            ?->mensajes()
            ->with(['user:id,name', 'menciones.user:id,name'])
            ->whereNull('parent_message_id')
            ->orderBy('id')
            ->get()
            ?? collect();
    }

    public function seleccionar(int $canalId): void
    {
        $this->canalId = $canalId;

        $canal = ChatCanal::find($canalId);
        if ($canal) {
            app(ChatService::class)->marcarLeido($canal, auth()->id());
        }

        unset($this->canales, $this->canal, $this->mensajes);
    }

    public function enviar(): void
    {
        $this->validate([
            'mensaje' => ['required', 'string', 'min:1', 'max:4000'],
        ]);

        if (! $this->canalId) {
            return;
        }

        $canal = ChatCanal::find($this->canalId);
        if (! $canal) {
            return;
        }

        app(ChatService::class)->enviarMensaje($canal, auth()->id(), $this->mensaje);
        $this->mensaje = '';

        unset($this->canales, $this->mensajes);
    }

    #[On('echo:chat,MensajeEnviado')]
    public function refrescar(): void
    {
        unset($this->canales, $this->mensajes);
    }

    public function render()
    {
        return view('livewire.chat.chat-panel');
    }

    public function resaltarMenciones(string $contenido): string
    {
        $escaped = e($contenido);

        return preg_replace(
            '/@([a-zA-Z0-9._-]+)/u',
            '<span class="text-gpt-700 font-semibold">@$1</span>',
            $escaped,
        ) ?? $escaped;
    }
}
