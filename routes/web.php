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
use App\Http\Controllers\Chat\ChatController;
use App\Http\Controllers\Cierre\CierreController;
use App\Http\Controllers\Cotizaciones\CotizacionesController;
use App\Http\Controllers\Cronograma\CronogramaController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Ejecucion\BitacoraController;
use App\Http\Controllers\Ejecucion\ReporteSemanalController;
use App\Http\Controllers\Ejecucion\ViaticosController;
use App\Http\Controllers\Ejecutivo\EjecutivoController;
use App\Http\Controllers\Finanzas\CierresController;
use App\Http\Controllers\Finanzas\ConciliacionController;
use App\Http\Controllers\Finanzas\CuentasBancariasController;
use App\Http\Controllers\Finanzas\EstadosCuentaController;
use App\Http\Controllers\Kom\KomController;
use App\Http\Controllers\Libro\LibroController;
use App\Http\Controllers\Minutas\MinutaEntregaController;
use App\Http\Controllers\Oportunidades\OportunidadesController;
use App\Http\Controllers\Procura\BomController;
use App\Http\Controllers\Procura\SolicitudesInternasController;
use App\Http\Controllers\Procura\SuministrosController;
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

    // M5b · BOM/BOE
    Route::get('/proyectos/{proyecto}/bom', [BomController::class, 'index'])->name('bom.index');
    Route::post('/proyectos/{proyecto}/bom/importar', [BomController::class, 'importar'])->name('bom.importar');
    Route::post('/proyectos/{proyecto}/bom', [BomController::class, 'store'])->name('bom.store');
    Route::patch('/proyectos/{proyecto}/bom/{item}', [BomController::class, 'update'])->name('bom.update');
    Route::delete('/proyectos/{proyecto}/bom/{item}', [BomController::class, 'destroy'])->name('bom.destroy');

    // M5b · Suministros (listado por etapas)
    Route::get('/proyectos/{proyecto}/suministros', [SuministrosController::class, 'show'])->name('suministros.show');
    Route::post('/proyectos/{proyecto}/suministros/importar', [SuministrosController::class, 'importar'])->name('suministros.importar');
    Route::post('/proyectos/{proyecto}/suministros/items', [SuministrosController::class, 'store'])->name('suministros.items.store');
    Route::patch('/proyectos/{proyecto}/suministros/items/{item}', [SuministrosController::class, 'update'])->name('suministros.items.update');
    Route::delete('/proyectos/{proyecto}/suministros/items/{item}', [SuministrosController::class, 'destroy'])->name('suministros.items.destroy');

    // M5b · Solicitudes Internas (Compras / Ingeniería)
    Route::get('/proyectos/{proyecto}/solicitudes', [SolicitudesInternasController::class, 'index'])->name('solicitudes.index');
    Route::post('/proyectos/{proyecto}/solicitudes', [SolicitudesInternasController::class, 'store'])->name('solicitudes.store');
    Route::get('/proyectos/{proyecto}/solicitudes/{solicitud}', [SolicitudesInternasController::class, 'show'])->name('solicitudes.show');
    Route::post('/proyectos/{proyecto}/solicitudes/{solicitud}/emitir', [SolicitudesInternasController::class, 'emitir'])->name('solicitudes.emitir');
    Route::post('/proyectos/{proyecto}/solicitudes/{solicitud}/tomar', [SolicitudesInternasController::class, 'tomar'])->name('solicitudes.tomar');
    Route::post('/proyectos/{proyecto}/solicitudes/{solicitud}/responder', [SolicitudesInternasController::class, 'responder'])->name('solicitudes.responder');
    Route::post('/proyectos/{proyecto}/solicitudes/{solicitud}/cancelar', [SolicitudesInternasController::class, 'cancelar'])->name('solicitudes.cancelar');

    // M6 · Libro de Proyecto
    Route::get('/proyectos/{proyecto}/libro', [LibroController::class, 'show'])->name('libro.show');
    Route::patch('/proyectos/{proyecto}/libro/items/{item}/toggle', [LibroController::class, 'toggleItem'])->name('libro.items.toggle');
    Route::post('/proyectos/{proyecto}/libro/secciones/{seccion}/items', [LibroController::class, 'storeItem'])->name('libro.items.store');
    Route::delete('/proyectos/{proyecto}/libro/items/{item}', [LibroController::class, 'destroyItem'])->name('libro.items.destroy');
    Route::patch('/proyectos/{proyecto}/libro/secciones/{seccion}', [LibroController::class, 'updateSeccion'])->name('libro.secciones.update');
    Route::post('/proyectos/{proyecto}/libro/secciones/{seccion}/documentos', [LibroController::class, 'uploadDocumento'])->name('libro.documentos.upload');
    Route::get('/proyectos/{proyecto}/libro/documentos/{documento}/descargar', [LibroController::class, 'descargarDocumento'])->name('libro.documentos.descargar');
    Route::delete('/proyectos/{proyecto}/libro/documentos/{documento}', [LibroController::class, 'destroyDocumento'])->name('libro.documentos.destroy');

    // M7 · Bitácora diaria
    Route::get('/proyectos/{proyecto}/bitacoras', [BitacoraController::class, 'index'])->name('bitacoras.index');
    Route::post('/proyectos/{proyecto}/bitacoras', [BitacoraController::class, 'store'])->name('bitacoras.store');
    Route::get('/proyectos/{proyecto}/bitacoras/{bitacora}', [BitacoraController::class, 'show'])->name('bitacoras.show');
    Route::patch('/proyectos/{proyecto}/bitacoras/{bitacora}', [BitacoraController::class, 'update'])->name('bitacoras.update');
    Route::post('/proyectos/{proyecto}/bitacoras/{bitacora}/vobo', [BitacoraController::class, 'vobo'])->name('bitacoras.vobo');
    Route::delete('/proyectos/{proyecto}/bitacoras/{bitacora}', [BitacoraController::class, 'destroy'])->name('bitacoras.destroy');
    Route::get('/proyectos/{proyecto}/bitacoras/{bitacora}/pdf', [BitacoraController::class, 'pdf'])->name('bitacoras.pdf');

    // M7 · Reportes semanales
    Route::get('/proyectos/{proyecto}/reportes', [ReporteSemanalController::class, 'index'])->name('reportes.index');
    Route::post('/proyectos/{proyecto}/reportes', [ReporteSemanalController::class, 'store'])->name('reportes.store');
    Route::get('/proyectos/{proyecto}/reportes/{reporte}', [ReporteSemanalController::class, 'show'])->name('reportes.show');
    Route::patch('/proyectos/{proyecto}/reportes/{reporte}/regenerar', [ReporteSemanalController::class, 'regenerar'])->name('reportes.regenerar');
    Route::post('/proyectos/{proyecto}/reportes/{reporte}/enviar', [ReporteSemanalController::class, 'enviar'])->name('reportes.enviar');
    Route::get('/proyectos/{proyecto}/reportes/{reporte}/pdf', [ReporteSemanalController::class, 'pdf'])->name('reportes.pdf');

    // M7 · Viáticos
    Route::get('/proyectos/{proyecto}/viaticos', [ViaticosController::class, 'index'])->name('viaticos.index');
    Route::post('/proyectos/{proyecto}/viaticos', [ViaticosController::class, 'store'])->name('viaticos.store');
    Route::get('/proyectos/{proyecto}/viaticos/{solicitud}', [ViaticosController::class, 'show'])->name('viaticos.show');
    Route::post('/proyectos/{proyecto}/viaticos/{solicitud}/emitir', [ViaticosController::class, 'emitir'])->name('viaticos.emitir');
    Route::post('/proyectos/{proyecto}/viaticos/{solicitud}/aprobar-servgrales', [ViaticosController::class, 'aprobarServGrales'])->name('viaticos.aprobar.servgrales');
    Route::post('/proyectos/{proyecto}/viaticos/{solicitud}/aprobar-direccion', [ViaticosController::class, 'aprobarDireccion'])->name('viaticos.aprobar.direccion');
    Route::post('/proyectos/{proyecto}/viaticos/{solicitud}/rechazar', [ViaticosController::class, 'rechazar'])->name('viaticos.rechazar');
    Route::patch('/proyectos/{proyecto}/viaticos/{solicitud}/reales', [ViaticosController::class, 'registrarReales'])->name('viaticos.reales');
    Route::get('/proyectos/{proyecto}/viaticos/{solicitud}/pdf', [ViaticosController::class, 'pdf'])->name('viaticos.pdf');

    // M8 · Cierre (Carta Finiquito + Post-Mortem)
    Route::get('/proyectos/{proyecto}/cierre', [CierreController::class, 'show'])->name('cierre.show');
    Route::post('/proyectos/{proyecto}/cierre/carta', [CierreController::class, 'storeCarta'])->name('cierre.carta.store');
    Route::post('/proyectos/{proyecto}/cierre/carta/firmar-gpt', [CierreController::class, 'firmarGpt'])->name('cierre.carta.firmar.gpt');
    Route::post('/proyectos/{proyecto}/cierre/carta/firmar-cliente', [CierreController::class, 'firmarCliente'])->name('cierre.carta.firmar.cliente');
    Route::get('/proyectos/{proyecto}/cierre/carta/pdf', [CierreController::class, 'pdfCarta'])->name('cierre.carta.pdf');
    Route::post('/proyectos/{proyecto}/cierre/postmortem', [CierreController::class, 'storePostMortem'])->name('cierre.postmortem.store');
    Route::get('/proyectos/{proyecto}/cierre/postmortem/pdf', [CierreController::class, 'pdfPostMortem'])->name('cierre.postmortem.pdf');
    Route::post('/proyectos/{proyecto}/cierre/cerrar', [CierreController::class, 'cerrarProyecto'])->name('cierre.cerrar');

    // M10 · Chat
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/{canalId}', [ChatController::class, 'show'])->whereNumber('canalId')->name('chat.show');
    Route::post('/chat/proyecto/{proyecto}', [ChatController::class, 'paraProyecto'])->name('chat.proyecto');

    Route::get('/clientes', fn () => view('stub', ['mod' => 'M2 Clientes — UI pendiente']))->name('clientes.index');
    Route::get('/proyectos', fn () => redirect()->route('oportunidades.index'))->name('proyectos.index');
    Route::get('/proyectos/asignaciones', [AsignacionesController::class, 'index'])->name('proyectos.asignaciones');
    Route::get('/bitacoras', fn () => redirect()->route('oportunidades.index'))->name('bitacoras');
    Route::get('/suministros', fn () => redirect()->route('oportunidades.index'))->name('suministros.index');
    Route::middleware('finanzas')->group(function () {
        // M11 · Cuentas bancarias
        Route::get('/finanzas/cuentas', [CuentasBancariasController::class, 'index'])->name('finanzas.cuentas');
        Route::post('/finanzas/cuentas', [CuentasBancariasController::class, 'store'])->name('finanzas.cuentas.store');
        Route::patch('/finanzas/cuentas/{cuenta}', [CuentasBancariasController::class, 'update'])->name('finanzas.cuentas.update');

        // M11 · Estados de cuenta
        Route::get('/finanzas/cuentas/{cuenta}/estados', [EstadosCuentaController::class, 'index'])->name('finanzas.estados.index');
        Route::post('/finanzas/cuentas/{cuenta}/estados', [EstadosCuentaController::class, 'store'])->name('finanzas.estados.store');
        Route::get('/finanzas/cuentas/{cuenta}/estados/{estado}', [EstadosCuentaController::class, 'show'])->name('finanzas.estados.show');

        // M11 · Conciliación
        Route::get('/finanzas/cuentas/{cuenta}/estados/{estado}/movimientos/{mov}', [ConciliacionController::class, 'show'])->name('finanzas.conciliacion.show');
        Route::post('/finanzas/cuentas/{cuenta}/estados/{estado}/movimientos/{mov}/conciliar', [ConciliacionController::class, 'conciliar'])->name('finanzas.conciliacion.conciliar');
        Route::delete('/finanzas/cuentas/{cuenta}/estados/{estado}/movimientos/{mov}/conciliar', [ConciliacionController::class, 'desconciliar'])->name('finanzas.conciliacion.desconciliar');

        // M12 · Cierres mensuales
        Route::get('/finanzas/cierres', [CierresController::class, 'index'])->name('finanzas.cierres');
        Route::post('/finanzas/cierres', [CierresController::class, 'store'])->name('finanzas.cierres.store');
        Route::get('/finanzas/cierres/{cierre}', [CierresController::class, 'show'])->name('finanzas.cierres.show');
        Route::patch('/finanzas/cierres/{cierre}/regenerar', [CierresController::class, 'regenerar'])->name('finanzas.cierres.regenerar');
        Route::post('/finanzas/cierres/{cierre}/aprobar', [CierresController::class, 'aprobar'])->name('finanzas.cierres.aprobar');
        Route::post('/finanzas/cierres/{cierre}/cerrar', [CierresController::class, 'cerrar'])->name('finanzas.cierres.cerrar');
        Route::patch('/finanzas/cierres/{cierre}/regresar', [CierresController::class, 'regresar'])->name('finanzas.cierres.regresar');
        Route::get('/finanzas/cierres/{cierre}/pdf', [CierresController::class, 'pdf'])->name('finanzas.cierres.pdf');
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
