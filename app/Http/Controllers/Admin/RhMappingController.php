<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RhRoleMapping;
use App\Models\User;
use App\Services\Auth\RoleMapper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RhMappingController extends Controller
{
    public function index()
    {
        return view('admin.rh-mapping.index', [
            'rules' => RhRoleMapping::orderByDesc('prioridad')->orderBy('puesto_rh')->get(),
            'roles' => Role::orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'puesto_rh' => ['required', 'string'],
            'rol_sistema' => ['required', 'string'],
            'prioridad' => ['required', 'integer', 'min:0', 'max:100'],
            'departamento_filter' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean'],
        ]);

        RhRoleMapping::create($data + ['activo' => $request->boolean('activo', true)]);
        RoleMapper::flushCache();

        return back()->with('status', 'Regla agregada.');
    }

    public function update(RhRoleMapping $rhMapping, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'puesto_rh' => ['required', 'string'],
            'rol_sistema' => ['required', 'string'],
            'prioridad' => ['required', 'integer', 'min:0', 'max:100'],
            'departamento_filter' => ['nullable', 'string'],
            'activo' => ['nullable', 'boolean'],
        ]);

        $rhMapping->update($data + ['activo' => $request->boolean('activo', false)]);
        RoleMapper::flushCache();

        return back()->with('status', 'Regla actualizada.');
    }

    public function destroy(RhRoleMapping $rhMapping): RedirectResponse
    {
        $rhMapping->delete();
        RoleMapper::flushCache();

        return back()->with('status', 'Regla eliminada.');
    }

    public function preview(RhRoleMapping $rhMapping)
    {
        $regex = '/^'.str_replace('%', '.*', preg_quote($rhMapping->puesto_rh, '/')).'$/i';
        $regex = str_replace('\\.\\*', '.*', $regex);

        $count = User::all()->filter(fn ($u) => $u->puesto && preg_match($regex, $u->puesto))->count();

        return response()->json(['matched_users' => $count]);
    }
}
