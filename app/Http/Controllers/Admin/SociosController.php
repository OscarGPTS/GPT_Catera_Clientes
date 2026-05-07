<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocioAllowlist;
use App\Models\User;
use App\Services\Auth\SocioResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SociosController extends Controller
{
    public function index()
    {
        return view('admin.socios.index', [
            'users' => User::orderBy('name')->paginate(30),
            'allowlist' => SocioAllowlist::orderBy('email')->get(),
        ]);
    }

    public function toggleOverride(User $user, Request $request): RedirectResponse
    {
        $user->es_socio_override = $request->boolean('value');
        $user->save();
        SocioResolver::flushCacheFor($user);

        return back()->with('status', "Override actualizado para {$user->name}.");
    }

    public function storeAllowlist(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'unique:socios_allowlist,email'],
            'notes' => ['nullable', 'string'],
        ]);

        SocioAllowlist::create($data + ['added_by' => $request->user()->id, 'added_at' => now()]);

        return back()->with('status', 'Email agregado al allowlist.');
    }

    public function destroyAllowlist(SocioAllowlist $entry): RedirectResponse
    {
        $entry->delete();

        return back()->with('status', 'Email removido del allowlist.');
    }
}
