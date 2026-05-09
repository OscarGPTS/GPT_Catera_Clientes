<?php

namespace App\Http\Controllers\Chat;

use App\Http\Controllers\Controller;
use App\Models\ChatCanal;
use App\Models\Proyecto;
use App\Services\Chat\ChatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(?int $canalId = null)
    {
        $userId = auth()->id();

        $canal = $canalId
            ? ChatCanal::whereHas('miembros', fn ($q) => $q->where('user_id', $userId))->find($canalId)
            : null;

        return view('chat.index', [
            'initialCanalId' => $canal?->id,
        ]);
    }

    public function show(int $canalId)
    {
        return $this->index($canalId);
    }

    public function paraProyecto(Proyecto $proyecto, ChatService $service, Request $request): RedirectResponse
    {
        $canal = $service->canalParaProyecto($proyecto);

        if (! $canal->miembros()->where('user_id', $request->user()->id)->exists()) {
            $canal->miembros()->attach($request->user()->id, ['rol_en_canal' => 'miembro', 'joined_at' => now()]);
        }

        return redirect()->route('chat.show', $canal->id);
    }
}
