<?php

use App\Http\Controllers\Adjudicacion\AdjudicacionController;
use App\Http\Controllers\Admin\RhMappingController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\SociosController;
use App\Http\Controllers\Admin\UsuariosController;
use App\Http\Controllers\Asignaciones\AsignacionesController;
use App\Http\Controllers\Auth\Auth0Controller;
use App\Http\Controllers\Auth\EmailPasswordController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\Cotizaciones\CotizacionesController;
use App\Http\Controllers\Cronograma\CronogramaController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Ejecutivo\EjecutivoController;
use App\Http\Controllers\Kom\KomController;
use App\Http\Controllers\Minutas\MinutaEntregaController;
use App\Http\Controllers\Oportunidades\OportunidadesController;
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

    // M2 Oportunidades / Status de Ofertas
    Route::get('/oportunidades', [OportunidadesController::class, 'index'])->name('oportunidades.index');
    Route::get('/oportunidades/nueva', [OportunidadesController::class, 'create'])->name('oportunidades.create');
    Route::post('/oportunidades', [OportunidadesController::class, 'store'])->name('oportunidades.store');
    Route::get('/oportunidades/{proyecto}', [OportunidadesController::class, 'show'])->name('oportunidades.show');
    Route::post('/oportunidades/{proyecto}/aprobar', [OportunidadesController::class, 'aprobarCp'])->name('oportunidades.aprobarCp');
    Route::post('/oportunidades/{proyecto}/equipo', [OportunidadesController::class, 'asignarEquipo'])->name('oportunidades.asignarEquipo');

    // M3 Cotizaciones / COSS
    Route::get('/oportunidades/{proyecto}/cotizaciones', [CotizacionesController::class, 'index'])->name('cotizaciones.index');
    Route::post('/oportunidades/{proyecto}/cotizaciones', [CotizacionesController::class, 'store'])->name('cotizaciones.store');
    Route::get('/oportunidades/{proyecto}/cotizaciones/{cotizacion}', [CotizacionesController::class, 'show'])->name('cotizaciones.show');
    Route::get('/oportunidades/{proyecto}/cotizaciones/{cotizacion}/edit', [CotizacionesController::class, 'edit'])->name('cotizaciones.edit');
    Route::patch('/oportunidades/{proyecto}/cotizaciones/{cotizacion}', [CotizacionesController::class, 'update'])->name('cotizaciones.update');
    Route::post('/oportunidades/{proyecto}/cotizaciones/{cotizacion}/partidas', [CotizacionesController::class, 'storePartida'])->name('cotizaciones.partidas.store');
    Route::patch('/oportunidades/{proyecto}/cotizaciones/{cotizacion}/partidas/{partida}', [CotizacionesController::class, 'updatePartida'])->name('cotizaciones.partidas.update');
    Route::delete('/oportunidades/{proyecto}/cotizaciones/{cotizacion}/partidas/{partida}', [CotizacionesController::class, 'destroyPartida'])->name('cotizaciones.partidas.destroy');
    Route::post('/oportunidades/{proyecto}/cotizaciones/{cotizacion}/emitir', [CotizacionesController::class, 'emitir'])->name('cotizaciones.emitir');
    Route::get('/oportunidades/{proyecto}/cotizaciones/{cotizacion}/pdf', [CotizacionesController::class, 'pdf'])->name('cotizaciones.pdf');

    // M4 Adjudicación · transiciones del CP
    Route::post('/oportunidades/{proyecto}/presentar', [AdjudicacionController::class, 'presentar'])->name('adjudicacion.presentar');
    Route::post('/oportunidades/{proyecto}/adjudicar', [AdjudicacionController::class, 'registrarAdjudicacion'])->name('adjudicacion.registrar');
    Route::post('/oportunidades/{proyecto}/firmar-oc', [AdjudicacionController::class, 'firmarOc'])->name('adjudicacion.firmar');
    Route::post('/oportunidades/{proyecto}/iniciar-ejecucion', [AdjudicacionController::class, 'iniciarEjecucion'])->name('adjudicacion.iniciar');
    Route::post('/oportunidades/{proyecto}/marcar-perdido', [AdjudicacionController::class, 'marcarPerdido'])->name('adjudicacion.perdido');

    // M4 Minuta de Entrega CP→DN
    Route::get('/oportunidades/{proyecto}/minuta-entrega', [MinutaEntregaController::class, 'show'])->name('minutas.show');
    Route::patch('/oportunidades/{proyecto}/minuta-entrega', [MinutaEntregaController::class, 'update'])->name('minutas.update');
    Route::post('/oportunidades/{proyecto}/minuta-entrega/participantes', [MinutaEntregaController::class, 'addParticipante'])->name('minutas.participantes.add');
    Route::delete('/oportunidades/{proyecto}/minuta-entrega/participantes/{userId}', [MinutaEntregaController::class, 'removeParticipante'])->name('minutas.participantes.remove');
    Route::post('/oportunidades/{proyecto}/minuta-entrega/firmar', [MinutaEntregaController::class, 'firmar'])->name('minutas.firmar');
    Route::get('/oportunidades/{proyecto}/minuta-entrega/pdf', [MinutaEntregaController::class, 'pdf'])->name('minutas.pdf');

    // M5a · KOM
    Route::get('/proyectos/{proyecto}/koms', [KomController::class, 'index'])->name('koms.index');
    Route::post('/proyectos/{proyecto}/koms', [KomController::class, 'store'])->name('koms.store');
    Route::get('/proyectos/{proyecto}/koms/{kom}', [KomController::class, 'show'])->name('koms.show');
    Route::patch('/proyectos/{proyecto}/koms/{kom}', [KomController::class, 'update'])->name('koms.update');
    Route::delete('/proyectos/{proyecto}/koms/{kom}', [KomController::class, 'destroy'])->name('koms.destroy');
    Route::get('/proyectos/{proyecto}/koms/{kom}/pdf', [KomController::class, 'pdf'])->name('koms.pdf');

    // M5a · Cronograma
    Route::get('/proyectos/{proyecto}/cronogramas', [CronogramaController::class, 'index'])->name('cronogramas.index');
    Route::post('/proyectos/{proyecto}/cronogramas', [CronogramaController::class, 'store'])->name('cronogramas.store');
    Route::get('/proyectos/{proyecto}/cronogramas/{cronograma}', [CronogramaController::class, 'show'])->name('cronogramas.show');
    Route::post('/proyectos/{proyecto}/cronogramas/{cronograma}/actividades', [CronogramaController::class, 'storeActividad'])->name('cronogramas.actividades.store');
    Route::patch('/proyectos/{proyecto}/cronogramas/{cronograma}/actividades/{actividad}', [CronogramaController::class, 'updateActividad'])->name('cronogramas.actividades.update');
    Route::delete('/proyectos/{proyecto}/cronogramas/{cronograma}/actividades/{actividad}', [CronogramaController::class, 'destroyActividad'])->name('cronogramas.actividades.destroy');

    Route::get('/clientes', fn () => view('stub', ['mod' => 'M2 Clientes — UI pendiente']))->name('clientes.index');
    Route::get('/proyectos', fn () => redirect()->route('oportunidades.index'))->name('proyectos.index');
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
