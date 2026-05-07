<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Email/password — D6 modalidad B. Solo usuarios pre-registrados (no auto-registro abierto).
 */
class EmailPasswordController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $provider = AuthProvider::where('provider', 'email_password')
            ->whereHas('user', fn ($q) => $q->whereRaw('LOWER(email) = ?', [strtolower($data['email'])]))
            ->first();

        if (! $provider || ! Hash::check($data['password'], (string) $provider->password_hash)) {
            throw ValidationException::withMessages([
                'email' => 'Credenciales inválidas o cuenta no autorizada.',
            ]);
        }

        $user = $provider->user;

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => 'Tu cuenta no está activa. Contacta al administrador.',
            ]);
        }

        $provider->forceFill(['last_used_at' => now()])->save();
        $user->forceFill(['last_login_at' => now()])->save();

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
