<?php

namespace App\Http\Controllers\Notificaciones;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificacionesController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $notificaciones = $user->notifications()->paginate(40)->withQueryString();

        return view('notificaciones.index', [
            'notificaciones' => $notificaciones,
        ]);
    }

    public function marcarLeida(Request $request, string $id): RedirectResponse
    {
        $user = $request->user();
        $notif = $user->notifications()->where('id', $id)->first();
        $notif?->markAsRead();

        return back()->with('status', 'Notificación marcada como leída.');
    }

    public function marcarTodoLeido(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Todas las notificaciones marcadas como leídas.');
    }
}
