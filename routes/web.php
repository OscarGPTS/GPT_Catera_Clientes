<?php

use App\Http\Controllers\Admin\RhMappingController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\SociosController;
use App\Http\Controllers\Admin\UsuariosController;
use App\Http\Controllers\Asignaciones\AsignacionesController;
use App\Http\Controllers\Auth\Auth0Controller;
use App\Http\Controllers\Auth\EmailPasswordController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Ejecutivo\EjecutivoController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/dashboard'))->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [EmailPasswordController::class, 'show'])->name('login');
    Route::post('/login', [EmailPasswordController::class, 'store'])->name('auth.email.store');

    Route::get('/auth/auth0/redirect', [Auth0Controller::class, 'redirect'])->name('auth.auth0.redirect');
    Route::get('/auth/auth0/callback', [Auth0Controller::class, 'callback'])->name('auth.auth0.callback');

    Route::get('/auth/{provider}/redirect', [SocialiteController::class, 'redirect'])
        ->whereIn('provider', ['google', 'microsoft', 'apple'])
        ->name('auth.social.redirect');
    Route::get('/auth/{provider}/callback', [SocialiteController::class, 'callback'])
        ->whereIn('provider', ['google', 'microsoft', 'apple'])
        ->name('auth.social.callback');
});

Route::post('/logout', [EmailPasswordController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/perfil/mi-asignacion', [AsignacionesController::class, 'mia'])->name('perfil.mi-asignacion');

    // Stubs de M2-M13 para que el sidebar tenga rutas reales mientras se construyen
    Route::get('/oportunidades', fn () => view('stub', ['mod' => 'M2 Oportunidades']))->name('oportunidades.index');
    Route::get('/oportunidades/nueva', fn () => view('stub', ['mod' => 'M2 Nueva oportunidad']))->name('oportunidades.create');
    Route::get('/clientes', fn () => view('stub', ['mod' => 'M2 Clientes']))->name('clientes.index');
    Route::get('/proyectos', fn () => view('stub', ['mod' => 'M2/M4 Proyectos']))->name('proyectos.index');
    Route::get('/proyectos/asignaciones', [AsignacionesController::class, 'index'])->name('proyectos.asignaciones');
    Route::get('/bitacoras', fn () => view('stub', ['mod' => 'M7 Bitácoras']))->name('bitacoras.index');
    Route::get('/suministros', fn () => view('stub', ['mod' => 'M5 Listado de Suministros']))->name('suministros.index');
    Route::middleware('finanzas')->group(function () {
        Route::get('/finanzas/cuentas', fn () => view('stub', ['mod' => 'M11 Cuentas — UI pendiente']))->name('finanzas.cuentas');
        Route::get('/finanzas/cierres', fn () => view('stub', ['mod' => 'M12 Cierres — UI pendiente']))->name('finanzas.cierres');
    });
    Route::get('/ejecutivo', EjecutivoController::class)
        ->middleware('role:direccion_general|socio|comite_socios|cfo|super_admin')
        ->name('ejecutivo.index');

    Route::middleware('role:super_admin|direccion_general')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/usuarios', [UsuariosController::class, 'index'])->name('usuarios.index');

        Route::get('/socios', [SociosController::class, 'index'])->name('socios.index');
        Route::patch('/socios/{user}/override', [SociosController::class, 'toggleOverride'])->name('socios.toggle');
        Route::post('/socios/allowlist', [SociosController::class, 'storeAllowlist'])->name('socios.allowlist.store');
        Route::delete('/socios/allowlist/{entry}', [SociosController::class, 'destroyAllowlist'])->name('socios.allowlist.destroy');

        Route::get('/rh-mapping', [RhMappingController::class, 'index'])->name('rh-mapping.index');
        Route::post('/rh-mapping', [RhMappingController::class, 'store'])->name('rh-mapping.store');
        Route::patch('/rh-mapping/{rhMapping}', [RhMappingController::class, 'update'])->name('rh-mapping.update');
        Route::delete('/rh-mapping/{rhMapping}', [RhMappingController::class, 'destroy'])->name('rh-mapping.destroy');
        Route::get('/rh-mapping/{rhMapping}/preview', [RhMappingController::class, 'preview'])->name('rh-mapping.preview');

        Route::get('/roles', [RolesController::class, 'index'])->name('roles.index');
    });
});
