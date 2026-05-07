<?php

namespace App\Http\Controllers\Asignaciones;

use App\Http\Controllers\Controller;
use App\Models\AsignacionPersona;
use App\Models\User;
use Illuminate\Http\Request;

class AsignacionesController extends Controller
{
    public function index(Request $request)
    {
        $año = (int) $request->input('año', now()->year);

        $rolesProyectos = ['gerente_proyectos', 'ingeniero_costos', 'ingeniero_proyectos', 'trainee_proyectos', 'gerente_operaciones'];

        $personas = User::role($rolesProyectos)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $snapshots = AsignacionPersona::whereIn('user_id', $personas->pluck('id'))
            ->where('año', $año)
            ->get()
            ->groupBy('user_id');

        return view('asignaciones.index', [
            'personas' => $personas,
            'snapshots' => $snapshots,
            'año' => $año,
            'meses' => range(1, 12),
        ]);
    }

    public function mia(Request $request)
    {
        $user = $request->user();
        $año = (int) $request->input('año', now()->year);

        $snapshots = AsignacionPersona::where('user_id', $user->id)
            ->where('año', $año)
            ->orderBy('mes')
            ->get();

        return view('asignaciones.mia', compact('user', 'año', 'snapshots'));
    }
}
