<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\Cronograma;
use App\Models\Proyecto;
use App\Models\Sublinea;
use App\Models\User;
use App\Services\Chat\ChatService;
use App\Services\Cierre\CartaFiniquitoService;
use App\Services\Cierre\PostMortemService;
use App\Services\Cotizaciones\CotizacionPdfGenerator;
use App\Services\Cotizaciones\CotizacionService;
use App\Services\Ejecucion\BitacoraService;
use App\Services\Ejecucion\ReporteSemanalService;
use App\Services\Ejecucion\ViaticosService;
use App\Services\Libro\AperturaLibroService;
use App\Services\Libro\LibroService;
use App\Services\Minutas\MinutaEntregaService;
use App\Services\Procura\BomService;
use App\Services\Procura\SolicitudInternaService;
use App\Services\Procura\SuministrosService;
use App\Services\Proyectos\CronogramaService;
use App\Services\Proyectos\KomService;
use App\Services\Proyectos\SecuenciasService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Siembra 10 oportunidades cubriendo todos los estados del flujo:
 * en_revision, cotizando, cotizado, presentado, adjudicado_pendiente,
 * adjudicado_firmado, en_ejecucion, en_cierre, cerrado, perdido.
 *
 * Asigna roles del equipo según el estado y crea eventos en la timeline.
 * Idempotente: si ya hay proyectos sembrados, no los duplica.
 */
class ProyectosTestSeeder extends Seeder
{
    public function run(): void
    {
        if (Proyecto::count() >= 10) {
            $this->command?->warn('ProyectosTestSeeder: ya hay 10+ proyectos, salteando.');

            return;
        }

        $año = now()->year;
        $secuencias = app(SecuenciasService::class);
        $aperturaLibro = app(AperturaLibroService::class);
        $cotizacionService = app(CotizacionService::class);
        $cotizacionPdf = app(CotizacionPdfGenerator::class);
        $minutaService = app(MinutaEntregaService::class);
        $komService = app(KomService::class);
        $cronogramaService = app(CronogramaService::class);
        $bomService = app(BomService::class);
        $suministrosService = app(SuministrosService::class);
        $solicitudService = app(SolicitudInternaService::class);

        // Personas clave (sembradas por TestUsersSeeder)
        $directorDn = User::where('email', 'pmartinez@gptservices.com')->firstOrFail();
        $direccion = User::where('email', 'gguterrez@gptservices.com')->firstOrFail();
        $gp = User::where('email', 'fbasave@gptservices.com')->firstOrFail();
        $go = User::where('email', 'rgarcia@gptservices.com')->firstOrFail();
        $ic = User::where('email', 'mlopez@gptservices.com')->firstOrFail();
        $ip = User::where('email', 'jsanchez@gptservices.com')->firstOrFail();
        $tr = User::where('email', 'lvazquez@gptservices.com')->firstOrFail();

        // Catálogos
        $iga = Cliente::where('alias_3letras', 'IGA')->firstOrFail();
        $sdn = Cliente::where('alias_3letras', 'SDN')->firstOrFail();
        $ptx = Cliente::where('alias_3letras', 'PTX')->firstOrFail();
        $eng = Cliente::where('alias_3letras', 'ENG')->firstOrFail();
        $cgs = Cliente::where('alias_3letras', 'CGS')->firstOrFail();
        $pmx = Cliente::where('alias_3letras', 'PMX')->firstOrFail();
        $cfe = Cliente::where('alias_3letras', 'CFE')->firstOrFail();
        $ien = Cliente::where('alias_3letras', 'IEN')->firstOrFail();
        $frm = Cliente::where('alias_3letras', 'FRM')->firstOrFail();

        $htp = Sublinea::where('codigo', 'HTP')->firstOrFail();
        $lsp = Sublinea::where('codigo', 'LSP')->firstOrFail();
        $vlv = Sublinea::where('codigo', 'VLV')->firstOrFail();
        $sol = Sublinea::where('codigo', 'SOL')->firstOrFail();
        $sg = Sublinea::where('codigo', 'SG')->firstOrFail();

        $oportunidades = [
            // 1. Recién creada — pendiente de aprobación del comité
            [
                'cliente' => $iga, 'sublinea' => $htp,
                'usuario_final' => 'CENAGAS',
                'sector' => 'Energía',
                'resumen' => 'Hot Tap 30"x10" en ducto de Texmelucan. Cliente requiere intervención sin parar operaciones.',
                'monto' => 96_998.30,
                'moneda' => 'USD',
                'estado' => 'en_revision',
                'director_dn' => $directorDn,
            ],
            // 2. CP aprobado, equipo cotizando
            [
                'cliente' => $cgs, 'sublinea' => $htp,
                'usuario_final' => 'CENAGAS',
                'sector' => 'Gobierno',
                'resumen' => 'Hot Tap 24"x6" en línea de gas natural en Tula. Reparación urgente.',
                'monto' => 78_500.00,
                'moneda' => 'USD',
                'estado' => 'cotizando',
                'director_dn' => $directorDn,
                'gerente_proyectos' => $gp,
                'ingeniero_costos' => $ic,
            ],
            // 3. Cotización terminada, lista para presentar
            [
                'cliente' => $ptx, 'sublinea' => $lsp,
                'usuario_final' => 'PEMEX TRI',
                'sector' => 'Energía',
                'resumen' => 'Line Stop 16" en oleoducto Salina Cruz. Mantenimiento programado.',
                'monto' => 145_300.00,
                'moneda' => 'USD',
                'estado' => 'cotizado',
                'director_dn' => $directorDn,
                'gerente_proyectos' => $gp,
                'ingeniero_costos' => $ic,
                'ingeniero_proyectos' => $ip,
            ],
            // 4. Presentada al cliente, esperando respuesta
            [
                'cliente' => $eng, 'sublinea' => $vlv,
                'usuario_final' => 'ENGIE Generación',
                'sector' => 'Energía',
                'resumen' => 'Suministro y cambio de válvula de bola 36" ANSI 600 en termoeléctrica.',
                'monto' => 312_500.00,
                'moneda' => 'USD',
                'estado' => 'presentado',
                'director_dn' => $directorDn,
                'gerente_proyectos' => $gp,
                'ingeniero_costos' => $ic,
                'ingeniero_proyectos' => $ip,
            ],
            // 5. Adjudicada pero pendiente de firma de OC
            [
                'cliente' => $sdn, 'sublinea' => $sol,
                'usuario_final' => 'SEDENA',
                'sector' => 'Gobierno',
                'resumen' => 'Reparación con soldadura de tanque de almacenamiento de combustible. Trabajo bajo restricciones de seguridad.',
                'monto' => 1_850_000.00,
                'moneda' => 'MXN',
                'estado' => 'adjudicado_pendiente',
                'director_dn' => $direccion,
                'gerente_proyectos' => $gp,
                'ingeniero_proyectos' => $ip,
            ],
            // 6. OC firmada, próxima a iniciar (con DN)
            [
                'cliente' => $frm, 'sublinea' => $htp,
                'usuario_final' => 'Fermaca',
                'sector' => 'Energía',
                'resumen' => 'Hot Tap múltiple en gasoducto del proyecto LIB 4.1km, conforme procedimiento.',
                'monto' => 425_000.00,
                'moneda' => 'USD',
                'estado' => 'adjudicado_firmado',
                'director_dn' => $directorDn,
                'gerente_proyectos' => $gp,
                'ingeniero_proyectos' => $ip,
                'gerente_operaciones' => $go,
                'crear_dn' => true,
            ],
            // 7. En ejecución
            [
                'cliente' => $pmx, 'sublinea' => $sol,
                'usuario_final' => 'PEMEX',
                'sector' => 'Gobierno',
                'resumen' => 'Soldadura especializada en planta criogénica. 3 procedimientos calificados.',
                'monto' => 980_000.00,
                'moneda' => 'MXN',
                'estado' => 'en_ejecucion',
                'director_dn' => $directorDn,
                'gerente_proyectos' => $gp,
                'ingeniero_proyectos' => $ip,
                'trainee' => $tr,
                'gerente_operaciones' => $go,
                'crear_dn' => true,
                'crear_libro' => true,
            ],
            // 8. En ejecución, casi terminado
            [
                'cliente' => $ien, 'sublinea' => $vlv,
                'usuario_final' => 'IEnova / Sempra',
                'sector' => 'Energía',
                'resumen' => 'Cambio de válvulas de control en estación compresora. Ventana de mantenimiento de 48h.',
                'monto' => 215_000.00,
                'moneda' => 'USD',
                'estado' => 'en_cierre',
                'director_dn' => $directorDn,
                'gerente_proyectos' => $gp,
                'ingeniero_proyectos' => $ip,
                'gerente_operaciones' => $go,
                'crear_dn' => true,
                'crear_libro' => true,
            ],
            // 9. Cerrado y archivado (caso histórico exitoso)
            [
                'cliente' => $cfe, 'sublinea' => $sg,
                'usuario_final' => 'CFE',
                'sector' => 'Gobierno',
                'resumen' => 'Servicio integral de mantenimiento en subestación. Proyecto completado satisfactoriamente.',
                'monto' => 580_000.00,
                'moneda' => 'MXN',
                'estado' => 'cerrado',
                'director_dn' => $directorDn,
                'gerente_proyectos' => $gp,
                'ingeniero_proyectos' => $ip,
                'gerente_operaciones' => $go,
                'crear_dn' => true,
                'crear_libro' => true,
            ],
            // 10. Perdido (referencia para hit rate)
            [
                'cliente' => $iga, 'sublinea' => $lsp,
                'usuario_final' => 'IGASAMEX',
                'sector' => 'Energía',
                'resumen' => 'Line Stop 12" en línea de exportación. Cliente decidió contratar a otro proveedor.',
                'monto' => 95_000.00,
                'moneda' => 'USD',
                'estado' => 'perdido',
                'director_dn' => $directorDn,
                'gerente_proyectos' => $gp,
                'ingeniero_costos' => $ic,
            ],
        ];

        foreach ($oportunidades as $row) {
            $cpNumero = $secuencias->asignarCp($año);

            $proyecto = Proyecto::create([
                'cp_numero' => $cpNumero,
                'año' => $año,
                'cliente_id' => $row['cliente']->id,
                'sublinea_id' => $row['sublinea']->id,
                'usuario_final' => $row['usuario_final'],
                'sector' => $row['sector'],
                'resumen_ejecutivo' => $row['resumen'],
                'estado' => $row['estado'],
                'monto_preliminar' => $row['monto'],
                'moneda' => $row['moneda'],
                'metodo_distribucion_plurianual' => 'dias_naturales',
                'fecha_inicio_planeada' => now()->subDays(rand(0, 60))->format('Y-m-d'),
                'fecha_fin_planeada' => now()->addDays(rand(60, 240))->format('Y-m-d'),
                'director_dn_id' => $row['director_dn']->id,
                'gerente_proyectos_id' => $row['gerente_proyectos']->id ?? null,
                'gerente_operaciones_id' => $row['gerente_operaciones']->id ?? null,
                'ingeniero_costos_id' => $row['ingeniero_costos']->id ?? null,
                'ingeniero_proyectos_id' => $row['ingeniero_proyectos']->id ?? null,
                'trainee_id' => $row['trainee']->id ?? null,
            ]);

            // DN para los que ya están adjudicados firmados o más adelante
            if (! empty($row['crear_dn'])) {
                $proyecto->update(['dn_numero' => $secuencias->asignarDn($año)]);
            }

            // Eventos de timeline
            $proyecto->recordEvent('cp_asignado', $row['director_dn']->id, ['cp_numero' => $cpNumero]);

            if (in_array($row['estado'], ['cotizando', 'cotizado', 'presentado', 'adjudicado_pendiente', 'adjudicado_firmado', 'en_ejecucion', 'en_cierre', 'cerrado', 'perdido'])) {
                $proyecto->recordEvent('cp_aprobado', $row['director_dn']->id, [
                    'gerente_proyectos_id' => $row['gerente_proyectos']->id ?? null,
                ]);
            }

            // Sembrar cotizaciones según el estado
            if (in_array($row['estado'], ['cotizando', 'cotizado', 'presentado', 'adjudicado_pendiente', 'adjudicado_firmado', 'en_ejecucion', 'en_cierre', 'cerrado'])) {
                $usuarioCotiza = $row['ingeniero_costos'] ?? $row['gerente_proyectos'] ?? $row['director_dn'];
                $cot = $cotizacionService->crearBorrador($proyecto, $usuarioCotiza->id);

                $partidas = $this->partidasParaSublinea($row['sublinea']->codigo, (float) $row['monto']);
                foreach ($partidas as $i => $part) {
                    $cot->partidas()->create([
                        'numero_partida' => $i + 1,
                        'descripcion' => $part['descripcion'],
                        'cantidad' => $part['cantidad'],
                        'unidad' => $part['unidad'],
                        'costo_unitario' => $part['costo_unitario'],
                        'costo_total' => round($part['cantidad'] * $part['costo_unitario'], 2),
                    ]);
                }

                if ($row['estado'] === 'cotizando') {
                    $cotizacionService->recalcular($cot);
                } else {
                    $cotizacionService->emitir($cot, $usuarioCotiza->id);
                    if (! app()->runningUnitTests()) {
                        $cotizacionPdf->generar($cot->fresh());
                    }
                }
            }

            if (! empty($row['crear_dn'])) {
                $proyecto->recordEvent('dn_asignado', $row['director_dn']->id, ['dn_numero' => $proyecto->dn_numero]);
            }

            // Sembrar minuta CP→DN para los adjudicados (D10).
            if (in_array($row['estado'], ['adjudicado_pendiente', 'adjudicado_firmado', 'en_ejecucion', 'en_cierre', 'cerrado'])) {
                $minuta = $minutaService->crearOSeleccionar($proyecto->fresh(), $row['gerente_proyectos']->id ?? $row['director_dn']->id);

                // Para los que ya están en ejecución o más adelante, sellar todas las firmas.
                if (in_array($row['estado'], ['en_ejecucion', 'en_cierre', 'cerrado'])) {
                    foreach ($minuta->participantes as $part) {
                        $minutaService->firmarPorUsuario($minuta->fresh(), $part->user_id);
                    }
                }
            }

            if (! empty($row['crear_libro'])) {
                $libro = $aperturaLibro->abrirParaProyecto($proyecto->fresh());

                // M6 · Llenar parcialmente el checklist según el estado del proyecto.
                $porcentajeCompletado = match ($row['estado']) {
                    'en_ejecucion' => 0.40,
                    'en_cierre' => 0.85,
                    'cerrado' => 1.00,
                    default => 0,
                };

                if ($porcentajeCompletado > 0) {
                    $autor = $row['gerente_proyectos'] ?? $row['director_dn'];
                    $libroService = app(LibroService::class);

                    foreach ($libro->secciones as $seccion) {
                        $items = $seccion->checklist;
                        $aMarcar = (int) ceil($items->count() * $porcentajeCompletado);
                        foreach ($items->take($aMarcar) as $item) {
                            $libroService->toggleChecklistItem($item, $autor->id);
                        }
                    }

                    $libroService->actualizarBloqueoCierre($libro->fresh());
                }
            }

            // KOM + cronograma para los que ya están en ejecución (M5a)
            if (in_array($row['estado'], ['en_ejecucion', 'en_cierre', 'cerrado'])) {
                $autor = $row['gerente_proyectos'] ?? $row['director_dn'];

                $cron = $cronogramaService->crearVersion($proyecto->fresh(), $autor->id);
                $this->sembrarActividades($cron, $row['sublinea']->codigo, $proyecto->fecha_inicio_planeada, $proyecto->fecha_fin_planeada);

                $komService->crear($proyecto->fresh(), $autor->id, [
                    'tipo' => 'kom_interno',
                    'fecha' => now()->subDays(rand(20, 60)),
                    'agenda' => "Revisión de alcance, asignación de responsables y plan de procura para {$row['sublinea']->nombre}.",
                    'minuta' => 'Equipo alineado en alcance. GP toma la batuta. Riesgos identificados: ventana de cliente, certificaciones de personal.',
                    'participantes' => [
                        ['nombre' => $row['director_dn']->name, 'rol' => 'Director DN', 'empresa' => 'GPT'],
                        ['nombre' => $autor->name, 'rol' => 'Gerente de Proyectos', 'empresa' => 'GPT'],
                        ['nombre' => $row['gerente_operaciones']?->name ?? 'GO', 'rol' => 'Gerente de Operaciones', 'empresa' => 'GPT'],
                    ],
                ]);

                $komCliente = $komService->crear($proyecto->fresh(), $autor->id, [
                    'tipo' => 'kom_cliente',
                    'fecha' => now()->subDays(rand(15, 40)),
                    'agenda' => 'Presentación del equipo, ratificación de fechas, revisión del cronograma y permisos del cliente.',
                    'minuta' => 'Cliente confirma fechas y aprueba cronograma. Acceso a sitio se libera la próxima semana.',
                    'participantes' => [
                        ['nombre' => $autor->name, 'rol' => 'Gerente de Proyectos', 'empresa' => 'GPT'],
                        ['nombre' => 'Representante del cliente', 'rol' => 'Project Manager', 'empresa' => $row['cliente']->razon_social],
                    ],
                ]);
                $komService->actualizar($komCliente, ['cronograma_attached_id' => $cron->id]);

                // M5b · BOM/BOE heredado de la cotización + Suministros heredado del BOM.
                $bomService->importarDesdeCotizacion($proyecto->fresh(), $autor->id);
                $suministrosService->importarDesdeBom($proyecto->fresh());

                // M7 · Bitácoras de los últimos 7 días + un reporte semanal
                $bitacoraService = app(BitacoraService::class);
                $reporteService = app(ReporteSemanalService::class);
                $resumenes = [
                    'Equipo arribó a sitio. Permisos QHSE liberados. Inicio de actividades del día sin novedades.',
                    'Avance normal de soldadura. Sin desviaciones reportadas. Cliente realiza inspección parcial.',
                    'Retraso de 2 horas por lluvia. Se recuperó turno extendido al final del día.',
                    'Pruebas de hermeticidad ejecutadas con resultados conformes. Documento NDT cargado al libro.',
                    'Falla menor en equipo de soldadura corregida en sitio. Cero impacto en avance.',
                    'Jornada productiva. Personal alineado con cronograma. Sin incidentes.',
                    'Cierre del día con avance del 12% del plan semanal. VoBo verbal del cliente.',
                ];
                foreach (range(0, 6) as $i) {
                    $fecha = now()->subDays(7 - $i)->toDateString();
                    if (! $proyecto->bitacoras()->where('fecha', $fecha)->exists()) {
                        $bitacoraService->crear($proyecto->fresh(), $autor->id, [
                            'fecha' => $fecha,
                            'relacion_actividades' => $resumenes[$i % count($resumenes)],
                            'personal_gpt' => [
                                ['nombre' => $autor->name, 'rol' => 'GP'],
                                ['nombre' => $row['ingeniero_proyectos']?->name ?? 'IP', 'rol' => 'IP'],
                            ],
                            'equipos_en_sitio' => [
                                ['nombre' => 'Máquina de soldadura', 'cantidad' => '2'],
                            ],
                            'proveedores_subcontratistas' => [],
                        ]);
                    }
                }

                $reporteService->generar($proyecto->fresh(), CarbonImmutable::now()->startOfWeek(), $autor->id);

                // M7 · Una solicitud de viáticos aprobada para los proyectos en ejecución
                if ($row['estado'] === 'en_ejecucion') {
                    $servGrales = User::role('serv_generales')->first() ?? User::where('email', 'sordaz@gptservices.com')->first();
                    $direccion = User::role('direccion_general')->first() ?? $row['director_dn'];
                    $viaticosService = app(ViaticosService::class);

                    $solicitud = $viaticosService->crear($proyecto->fresh(), $autor->id, [
                        'periodo_inicio' => now()->subDays(10)->toDateString(),
                        'periodo_fin' => now()->subDays(5)->toDateString(),
                        'justificacion' => 'Movilización de cuadrilla a sitio para arranque de actividades.',
                        'personal' => [
                            ['user_id' => $autor->id, 'dias' => 5],
                            ['user_id' => $row['ingeniero_proyectos']?->id ?? $autor->id, 'dias' => 5],
                        ],
                        'partidas' => [
                            ['concepto' => 'hospedaje', 'monto_estimado' => 8000, 'observaciones' => '5 noches × 2 personas'],
                            ['concepto' => 'alimentos', 'monto_estimado' => 4500, 'observaciones' => 'viático estándar'],
                            ['concepto' => 'transporte', 'monto_estimado' => 3000, 'observaciones' => 'gasolina + casetas'],
                        ],
                    ]);

                    $viaticosService->emitir($solicitud, $autor->id);
                    if ($servGrales) {
                        $viaticosService->aprobarServGrales($solicitud->fresh(), $servGrales->id);
                    }
                    if ($direccion) {
                        $viaticosService->aprobarDireccion($solicitud->fresh(), $direccion->id);
                    }
                }

                // Una solicitud interna típica respondida por Compras.
                $compras = User::where('email', 'jbecerra@gptservices.com')->first();
                if ($compras) {
                    $solicitud = $solicitudService->crear($proyecto->fresh(), $autor->id, [
                        'tipo' => 'requisicion_compras',
                        'codigo_formato' => 'FO-GPT-CMP-01',
                        'asignado_id' => $compras->id,
                        'fecha_respuesta_requerida' => now()->subDays(10)->toDateString(),
                        'descripcion' => 'Solicitud de procura urgente para arranque de proyecto.',
                        'items' => [
                            ['descripcion' => 'Material de procedimientos', 'cantidad' => 1, 'unidad' => 'lote', 'especificacion' => 'según especificación técnica'],
                            ['descripcion' => 'Consumibles de soldadura', 'cantidad' => 50, 'unidad' => 'kg'],
                        ],
                    ]);
                    $solicitudService->emitir($solicitud, $autor->id);
                    $solicitudService->responder($solicitud, $compras->id, 'OC liberada con proveedor habitual. Entrega confirmada para próxima semana.');
                }
            }

            // M10 · Canal de chat por proyecto (adjudicado_firmado+) con mensajes de muestra.
            if (in_array($row['estado'], ['adjudicado_firmado', 'en_ejecucion', 'en_cierre', 'cerrado'])) {
                $chatService = app(ChatService::class);
                $canal = $chatService->canalParaProyecto($proyecto->fresh());

                $autorChat = $row['gerente_proyectos'] ?? $row['director_dn'];
                $compras = User::where('email', 'jbecerra@gptservices.com')->first();

                $chatService->enviarMensaje($canal, $autorChat->id, "Equipo, ya tenemos OC firmada para {$proyecto->cp_numero}. Arrancamos.");

                if (in_array($row['estado'], ['en_ejecucion', 'en_cierre', 'cerrado'])) {
                    if ($compras) {
                        $chatService->enviarMensaje($canal, $compras->id, "@{$autorChat->name} confirmo que la procura está liberada.");
                    }
                    if (! empty($row['ingeniero_proyectos'])) {
                        $chatService->enviarMensaje(
                            $canal,
                            $row['ingeniero_proyectos']->id,
                            'Bitácora del día subida. Sin desviaciones.',
                        );
                    }
                }

                if ($row['estado'] === 'cerrado') {
                    $chatService->enviarMensaje($canal, $autorChat->id, 'Proyecto cerrado oficialmente. Buen trabajo equipo 🎉');
                }
            }

            // M8 · Carta Finiquito + Post-Mortem para en_cierre y cerrado
            if (in_array($row['estado'], ['en_cierre', 'cerrado'])) {
                $autor = $row['gerente_proyectos'] ?? $row['director_dn'];
                $cartaService = app(CartaFiniquitoService::class);
                $pmService = app(PostMortemService::class);

                $cartaService->crearOActualizar($proyecto->fresh(), $autor->id, [
                    'fecha_emision' => now()->subDays(rand(1, 10))->toDateString(),
                    'observaciones' => 'Proyecto entregado satisfactoriamente. No quedan reclamaciones pendientes.',
                    'personal_liberado' => [
                        ['nombre' => $autor->name, 'rol' => 'Gerente de Proyectos'],
                        ['nombre' => $row['ingeniero_proyectos']?->name ?? '—', 'rol' => 'Ingeniero de Proyectos'],
                    ],
                    'equipos_liberados' => [
                        ['nombre' => 'Máquina de soldadura'],
                        ['nombre' => 'Equipo de inspección NDT'],
                    ],
                ]);

                if ($row['estado'] === 'cerrado') {
                    // Para cerrado: firmar ambas y luego cerrar formalmente
                    $cartaService->firmarGpt($proyecto->fresh()->cartaFiniquito, $autor->id);
                    $cartaService->firmarCliente(
                        $proyecto->fresh()->cartaFiniquito,
                        $autor->id,
                        'Representante del cliente',
                    );

                    $pmService->crearOActualizar($proyecto->fresh(), $autor->id, [
                        'fecha_sesion' => now()->subDays(rand(1, 5))->toDateString(),
                        'lecciones_aprendidas' => "Proyecto entregado en tiempo y forma.\n\nLo que funcionó: planeación temprana del cronograma, comunicación constante con cliente.\n\nÁreas de mejora: documentar mejor las desviaciones del día a día.",
                        'presupuesto_planeado' => $row['monto'],
                        'presupuesto_real' => $row['monto'] * 1.05, // 5% sobrecosto
                        'participantes' => [
                            ['nombre' => $row['director_dn']->name, 'rol' => 'Director DN'],
                            ['nombre' => $autor->name, 'rol' => 'GP'],
                            ['nombre' => $row['gerente_operaciones']?->name ?? '—', 'rol' => 'GO'],
                        ],
                        'recomendaciones_mejora' => [
                            ['texto' => 'Establecer reuniones semanales con cliente desde el día 1', 'responsable' => 'GP'],
                            ['texto' => 'Mejorar template de bitácora diaria', 'responsable' => 'QHSE'],
                        ],
                    ]);

                    // Cerrar formalmente (transiciona estado a 'cerrado' — pero ya está)
                    // así que sólo registramos un evento adicional si hace falta.
                    if ($proyecto->fresh()->estado !== 'cerrado') {
                        $cartaService->cerrarProyecto($proyecto->fresh()->cartaFiniquito, $autor->id);
                    }
                }
            }
        }

        $this->command?->info('ProyectosTestSeeder: '.count($oportunidades).' oportunidades sembradas con eventos.');
    }

    /**
     * Siembra actividades realistas para el cronograma según la sublínea.
     */
    private function sembrarActividades(Cronograma $cronograma, string $codigoSublinea, $fechaInicio, $fechaFin): void
    {
        $inicio = $fechaInicio ? Carbon::parse($fechaInicio) : now()->subDays(15);
        $fin = $fechaFin ? Carbon::parse($fechaFin) : now()->addDays(60);
        $duracionTotal = max(7, $inicio->diffInDays($fin));

        $plantillas = [
            'HTP' => [
                ['cod' => '1', 'nombre' => 'Procura de equipos Hot Tap', 'pesoStart' => 0, 'pesoEnd' => 0.20, 'avance' => 100],
                ['cod' => '2', 'nombre' => 'Movilización a sitio', 'pesoStart' => 0.15, 'pesoEnd' => 0.25, 'avance' => 100],
                ['cod' => '3', 'nombre' => 'Permisos QHSE y AST', 'pesoStart' => 0.20, 'pesoEnd' => 0.30, 'avance' => 80],
                ['cod' => '4', 'nombre' => 'Soldadura de tee', 'pesoStart' => 0.30, 'pesoEnd' => 0.50, 'avance' => 60],
                ['cod' => '5', 'nombre' => 'Hot Tap y prueba', 'pesoStart' => 0.50, 'pesoEnd' => 0.75, 'avance' => 30],
                ['cod' => '6', 'nombre' => 'Cierre y desmovilización', 'pesoStart' => 0.75, 'pesoEnd' => 0.95, 'avance' => 10],
                ['cod' => '7', 'nombre' => 'Dossier final', 'pesoStart' => 0.85, 'pesoEnd' => 1.0, 'avance' => 0],
            ],
            'LSP' => [
                ['cod' => '1', 'nombre' => 'Procura equipo Line Stop', 'pesoStart' => 0, 'pesoEnd' => 0.25, 'avance' => 100],
                ['cod' => '2', 'nombre' => 'Movilización', 'pesoStart' => 0.20, 'pesoEnd' => 0.30, 'avance' => 100],
                ['cod' => '3', 'nombre' => 'Soldadura tees', 'pesoStart' => 0.30, 'pesoEnd' => 0.50, 'avance' => 70],
                ['cod' => '4', 'nombre' => 'Line Stop instalación', 'pesoStart' => 0.50, 'pesoEnd' => 0.75, 'avance' => 40],
                ['cod' => '5', 'nombre' => 'Pruebas de hermeticidad', 'pesoStart' => 0.75, 'pesoEnd' => 0.90, 'avance' => 10],
                ['cod' => '6', 'nombre' => 'Dossier', 'pesoStart' => 0.85, 'pesoEnd' => 1.0, 'avance' => 0],
            ],
            'VLV' => [
                ['cod' => '1', 'nombre' => 'Procura de válvula', 'pesoStart' => 0, 'pesoEnd' => 0.40, 'avance' => 100],
                ['cod' => '2', 'nombre' => 'Recepción y QC', 'pesoStart' => 0.35, 'pesoEnd' => 0.50, 'avance' => 80],
                ['cod' => '3', 'nombre' => 'Movilización', 'pesoStart' => 0.45, 'pesoEnd' => 0.60, 'avance' => 60],
                ['cod' => '4', 'nombre' => 'Cambio de válvula', 'pesoStart' => 0.55, 'pesoEnd' => 0.85, 'avance' => 20],
                ['cod' => '5', 'nombre' => 'Pruebas y dossier', 'pesoStart' => 0.80, 'pesoEnd' => 1.0, 'avance' => 0],
            ],
            'SOL' => [
                ['cod' => '1', 'nombre' => 'Calificación WPS/PQR', 'pesoStart' => 0, 'pesoEnd' => 0.15, 'avance' => 100],
                ['cod' => '2', 'nombre' => 'Movilización de soldadores', 'pesoStart' => 0.10, 'pesoEnd' => 0.25, 'avance' => 100],
                ['cod' => '3', 'nombre' => 'Soldadura', 'pesoStart' => 0.20, 'pesoEnd' => 0.65, 'avance' => 50],
                ['cod' => '4', 'nombre' => 'Inspección NDT', 'pesoStart' => 0.50, 'pesoEnd' => 0.80, 'avance' => 25],
                ['cod' => '5', 'nombre' => 'Reparaciones', 'pesoStart' => 0.70, 'pesoEnd' => 0.90, 'avance' => 0],
                ['cod' => '6', 'nombre' => 'Dossier', 'pesoStart' => 0.85, 'pesoEnd' => 1.0, 'avance' => 0],
            ],
            'SG' => [
                ['cod' => '1', 'nombre' => 'Planeación', 'pesoStart' => 0, 'pesoEnd' => 0.20, 'avance' => 100],
                ['cod' => '2', 'nombre' => 'Ejecución de mantenimiento', 'pesoStart' => 0.15, 'pesoEnd' => 0.80, 'avance' => 50],
                ['cod' => '3', 'nombre' => 'Pruebas finales', 'pesoStart' => 0.75, 'pesoEnd' => 0.95, 'avance' => 0],
                ['cod' => '4', 'nombre' => 'Cierre', 'pesoStart' => 0.90, 'pesoEnd' => 1.0, 'avance' => 0],
            ],
        ];

        $plantilla = $plantillas[$codigoSublinea] ?? $plantillas['SG'];
        $service = app(CronogramaService::class);

        foreach ($plantilla as $a) {
            $iniAct = $inicio->copy()->addDays((int) ($duracionTotal * $a['pesoStart']));
            $finAct = $inicio->copy()->addDays((int) ($duracionTotal * $a['pesoEnd']));

            $service->agregarActividad($cronograma, [
                'codigo' => $a['cod'],
                'nombre' => $a['nombre'],
                'fecha_inicio_planeada' => $iniAct->toDateString(),
                'fecha_fin_planeada' => $finAct->toDateString(),
                'porcentaje_avance' => $a['avance'],
            ]);
        }
    }

    /**
     * Genera partidas representativas por sublínea, calibradas para sumar
     * aproximadamente el monto preliminar (el factor de venta lo lleva por encima).
     *
     * @return array<int, array{descripcion: string, cantidad: float, unidad: string, costo_unitario: float}>
     */
    private function partidasParaSublinea(string $codigo, float $montoObjetivo): array
    {
        // El monto objetivo es el precio de venta. El costo directo objetivo es ~63% (1.18*1.08*1.15 ≈ 1.466).
        $cdObjetivo = $montoObjetivo / 1.466;

        $plantillas = [
            'HTP' => [
                ['descripcion' => 'Tee Hot Tap fabricado a medida', 'cantidad' => 1, 'unidad' => 'pza', 'peso' => 0.18],
                ['descripcion' => 'Válvula sandwich de bola', 'cantidad' => 1, 'unidad' => 'pza', 'peso' => 0.16],
                ['descripcion' => 'Máquina de Hot Tap (renta)', 'cantidad' => 3, 'unidad' => 'día', 'peso' => 0.20],
                ['descripcion' => 'Cuadrilla técnica certificada', 'cantidad' => 4, 'unidad' => 'jornada', 'peso' => 0.18],
                ['descripcion' => 'Soldadura, ensayos y permisos', 'cantidad' => 1, 'unidad' => 'lote', 'peso' => 0.14],
                ['descripcion' => 'Logística y traslado', 'cantidad' => 1, 'unidad' => 'lote', 'peso' => 0.14],
            ],
            'LSP' => [
                ['descripcion' => 'Equipo Line Stop con accesorios', 'cantidad' => 1, 'unidad' => 'pza', 'peso' => 0.30],
                ['descripcion' => 'Tee de derivación fabricada', 'cantidad' => 2, 'unidad' => 'pza', 'peso' => 0.20],
                ['descripcion' => 'Cuadrilla técnica especializada', 'cantidad' => 5, 'unidad' => 'jornada', 'peso' => 0.18],
                ['descripcion' => 'Servicio de soldadura calificada', 'cantidad' => 1, 'unidad' => 'lote', 'peso' => 0.16],
                ['descripcion' => 'Pruebas hidrostáticas + permisos', 'cantidad' => 1, 'unidad' => 'lote', 'peso' => 0.16],
            ],
            'VLV' => [
                ['descripcion' => 'Válvula a suministrar (con certificados)', 'cantidad' => 1, 'unidad' => 'pza', 'peso' => 0.55],
                ['descripcion' => 'Mano de obra de cambio', 'cantidad' => 4, 'unidad' => 'jornada', 'peso' => 0.20],
                ['descripcion' => 'Pruebas, dossier y certificación', 'cantidad' => 1, 'unidad' => 'lote', 'peso' => 0.15],
                ['descripcion' => 'Logística y maniobras', 'cantidad' => 1, 'unidad' => 'lote', 'peso' => 0.10],
            ],
            'SOL' => [
                ['descripcion' => 'Procedimiento WPS/PQR', 'cantidad' => 3, 'unidad' => 'pza', 'peso' => 0.10],
                ['descripcion' => 'Soldadores calificados', 'cantidad' => 8, 'unidad' => 'jornada', 'peso' => 0.30],
                ['descripcion' => 'Consumibles (electrodos, gas)', 'cantidad' => 1, 'unidad' => 'lote', 'peso' => 0.15],
                ['descripcion' => 'Inspección NDT (RT/UT/PT)', 'cantidad' => 1, 'unidad' => 'lote', 'peso' => 0.20],
                ['descripcion' => 'Equipos especiales (renta)', 'cantidad' => 5, 'unidad' => 'día', 'peso' => 0.15],
                ['descripcion' => 'Permisos y EHS', 'cantidad' => 1, 'unidad' => 'lote', 'peso' => 0.10],
            ],
            'SG' => [
                ['descripcion' => 'Mantenimiento integral planeado', 'cantidad' => 1, 'unidad' => 'lote', 'peso' => 0.40],
                ['descripcion' => 'Cuadrilla multidisciplinaria', 'cantidad' => 10, 'unidad' => 'jornada', 'peso' => 0.30],
                ['descripcion' => 'Refacciones genéricas', 'cantidad' => 1, 'unidad' => 'lote', 'peso' => 0.20],
                ['descripcion' => 'Logística', 'cantidad' => 1, 'unidad' => 'lote', 'peso' => 0.10],
            ],
        ];

        $plantilla = $plantillas[$codigo] ?? $plantillas['SG'];
        $partidas = [];

        foreach ($plantilla as $p) {
            $costoTotal = $cdObjetivo * $p['peso'];
            $unitario = $p['cantidad'] > 0 ? $costoTotal / $p['cantidad'] : 0;

            $partidas[] = [
                'descripcion' => $p['descripcion'],
                'cantidad' => $p['cantidad'],
                'unidad' => $p['unidad'],
                'costo_unitario' => round($unitario, 4),
            ];
        }

        return $partidas;
    }
}
