<x-layouts.app :title="$proyecto->cp_numero ?? 'Oportunidad'">
    @php
        $estadoBadge = [
            'en_revision' => 'bg-slate-100 text-slate-700',
            'cotizando' => 'bg-amber-100 text-amber-800',
            'cotizado' => 'bg-amber-100 text-amber-800',
            'presentado' => 'bg-blue-100 text-blue-800',
            'adjudicado_pendiente' => 'bg-emerald-50 text-emerald-700',
            'adjudicado_firmado' => 'bg-emerald-100 text-emerald-800',
            'en_ejecucion' => 'bg-emerald-200 text-emerald-900',
            'en_cierre' => 'bg-violet-100 text-violet-800',
            'cerrado' => 'bg-slate-200 text-slate-800',
            'cancelado' => 'bg-rose-100 text-rose-800',
            'perdido' => 'bg-rose-100 text-rose-800',
        ];
    @endphp

    <a href="{{ route('oportunidades.index') }}" class="text-sm text-slate-500 hover:underline">← Volver al status</a>

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-semibold text-slate-900 font-mono">{{ $proyecto->cp_numero ?? '—' }}</h1>
                @if ($proyecto->dn_numero)
                    <span class="text-xl font-mono text-emerald-700">/ {{ $proyecto->dn_numero }}</span>
                @endif
                <span class="inline-block rounded px-2 py-0.5 text-xs {{ $estadoBadge[$proyecto->estado] ?? 'bg-slate-100' }}">
                    {{ $estados[$proyecto->estado] ?? $proyecto->estado }}
                </span>
            </div>
            <p class="text-slate-600 mt-1">
                {{ $proyecto->cliente?->razon_social }} · {{ $proyecto->sublinea?->nombre }}
                @if ($proyecto->usuario_final)
                    · usuario final {{ $proyecto->usuario_final }}
                @endif
            </p>
            @if ($proyecto->tech_reference)
                <p class="text-xs font-mono text-slate-500 mt-1">{{ $proyecto->tech_reference }}</p>
            @endif
        </div>
        @if (in_array($proyecto->estado, ['adjudicado_firmado', 'en_ejecucion', 'en_cierre', 'cerrado']))
            <form method="POST" action="{{ route('chat.proyecto', $proyecto) }}">
                @csrf
                <button class="rounded-md border border-gpt-200 hover:bg-gpt-50 text-gpt-700 text-sm px-3 py-1.5">💬 Chat del proyecto</button>
            </form>
        @endif
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <section class="bg-white border border-slate-200 rounded-xl p-6">
                <h2 class="font-semibold text-slate-900 mb-3">Resumen ejecutivo</h2>
                <p class="text-sm text-slate-700 whitespace-pre-line">{{ $proyecto->resumen_ejecutivo ?? '—' }}</p>
            </section>

            @if ($proyecto->estado === 'en_revision')
                <section class="bg-white border border-amber-200 rounded-xl p-6">
                    <h2 class="font-semibold text-slate-900 mb-1">Aprobación del Comité Comercial</h2>
                    <p class="text-sm text-slate-500 mb-4">El director_dn / direccion_general decide y asigna gerente_proyectos.</p>

                    @canany(['cp.approve', 'admin.manage'])
                        <form method="POST" action="{{ route('oportunidades.aprobarCp', $proyecto) }}" class="space-y-3">
                            @csrf
                            <label class="block">
                                <span class="text-xs text-slate-600">Gerente de Proyectos *</span>
                                <select name="gerente_proyectos_id" required class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                                    <option value="">Seleccionar…</option>
                                    @foreach ($gerentes as $g)
                                        <option value="{{ $g->id }}">{{ $g->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block">
                                <span class="text-xs text-slate-600">Comentario</span>
                                <textarea name="comentario" rows="2" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm"></textarea>
                            </label>
                            <div class="flex gap-2">
                                <button name="decision" value="aprobar" class="rounded-md bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 py-2">Aprobar</button>
                                <button name="decision" value="rechazar" onclick="return confirm('¿Rechazar este CP?')" class="rounded-md border border-rose-200 hover:bg-rose-50 text-rose-700 text-sm px-4 py-2">Rechazar</button>
                            </div>
                        </form>
                    @else
                        <p class="text-sm text-slate-500">Sin permiso para aprobar. Esta acción la realiza dirección comercial.</p>
                    @endcanany
                </section>
            @endif

            @if (in_array($proyecto->estado, ['cotizando', 'cotizado']) && $proyecto->gerente_proyectos_id)
                <section class="bg-white border border-slate-200 rounded-xl p-6">
                    <h2 class="font-semibold text-slate-900 mb-3">Asignación interna del CP</h2>
                    <form method="POST" action="{{ route('oportunidades.asignarEquipo', $proyecto) }}" class="grid md:grid-cols-3 gap-3">
                        @csrf
                        <label class="block">
                            <span class="text-xs text-slate-600">Ingeniero de Costos</span>
                            <select name="ingeniero_costos_id" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                                <option value="">—</option>
                                @foreach ($ingenieros->get('ingeniero_costos', collect()) as $u)
                                    <option value="{{ $u->id }}" @selected($proyecto->ingeniero_costos_id === $u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs text-slate-600">Ingeniero de Proyectos</span>
                            <select name="ingeniero_proyectos_id" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                                <option value="">—</option>
                                @foreach ($ingenieros->get('ingeniero_proyectos', collect()) as $u)
                                    <option value="{{ $u->id }}" @selected($proyecto->ingeniero_proyectos_id === $u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs text-slate-600">Trainee</span>
                            <select name="trainee_id" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                                <option value="">—</option>
                                @foreach ($ingenieros->get('trainee_proyectos', collect()) as $u)
                                    <option value="{{ $u->id }}" @selected($proyecto->trainee_id === $u->id)>{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <div class="md:col-span-3">
                            <button class="rounded-md bg-slate-900 hover:bg-slate-800 text-white text-sm px-4 py-2">Guardar asignación</button>
                        </div>
                    </form>
                </section>
            @endif

            @if (in_array($proyecto->estado, ['cotizado', 'presentado', 'adjudicado_pendiente', 'adjudicado_firmado']))
                @include('oportunidades.partials.stepper-adjudicacion', ['proyecto' => $proyecto])
            @endif

            @if (in_array($proyecto->estado, ['cotizando', 'cotizado', 'presentado', 'adjudicado_pendiente', 'adjudicado_firmado', 'en_ejecucion', 'en_cierre', 'cerrado']))
                <section class="bg-white border border-slate-200 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-semibold text-slate-900">Cotizaciones</h2>
                        <a href="{{ route('cotizaciones.index', $proyecto) }}" class="text-sm text-gpt-600 hover:underline">Ver todas →</a>
                    </div>
                    @if ($proyecto->cotizaciones->isEmpty())
                        <p class="text-sm text-slate-500 mb-3">Aún no hay cotizaciones para este CP.</p>
                        @if ($proyecto->estado === 'cotizando')
                            <form method="POST" action="{{ route('cotizaciones.store', $proyecto) }}">
                                @csrf
                                <button class="rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm px-4 py-2">Crear primer borrador</button>
                            </form>
                        @endif
                    @else
                        <ul class="divide-y divide-slate-100">
                            @foreach ($proyecto->cotizaciones->take(3) as $c)
                                <li class="py-2 flex items-center justify-between">
                                    <div>
                                        <span class="font-mono text-sm">v{{ $c->version }}</span>
                                        <span class="ml-2 text-xs rounded px-2 py-0.5 {{ $c->status === 'emitida' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">{{ str_replace('_', ' ', $c->status) }}</span>
                                        <span class="ml-2 font-mono text-sm text-slate-700">${{ number_format((float) $c->precio_venta_final, 2) }} {{ $c->moneda }}</span>
                                    </div>
                                    <a href="{{ $c->status === 'borrador' ? route('cotizaciones.edit', [$proyecto, $c]) : route('cotizaciones.show', [$proyecto, $c]) }}"
                                       class="text-xs text-gpt-600 hover:underline">{{ $c->status === 'borrador' ? 'Editar' : 'Ver' }}</a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            @endif

            @if (in_array($proyecto->estado, ['en_ejecucion', 'en_cierre', 'cerrado']))
                <a href="{{ route('libro.show', $proyecto) }}"
                   class="block bg-white border-2 border-emerald-300 rounded-xl p-5 hover:border-emerald-500 hover:bg-emerald-50">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="font-semibold text-slate-900">📘 Libro de Proyecto</h3>
                            <p class="text-xs text-slate-500 mt-1">10 secciones A-J · checklist + documentos · D11 bloqueo de cierre</p>
                        </div>
                        @if ($proyecto->libro)
                            <div class="text-right">
                                <p class="text-2xl font-bold text-emerald-700">{{ number_format((float) $proyecto->libro->porcentaje_avance_global, 0) }}%</p>
                                <p class="text-xs text-slate-500">avance</p>
                            </div>
                        @endif
                    </div>
                </a>
            @endif

            @if (in_array($proyecto->estado, ['en_cierre', 'cerrado']))
                <a href="{{ route('cierre.show', $proyecto) }}"
                   class="block bg-white border-2 border-amber-300 rounded-xl p-5 hover:border-amber-500 hover:bg-amber-50">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="font-semibold text-slate-900">🏁 Cierre del proyecto</h3>
                            <p class="text-xs text-slate-500 mt-1">Carta Finiquito + Post-Mortem · D11/D12</p>
                        </div>
                        @php
                            $cartaCierre = $proyecto->cartaFiniquito;
                            $cartaFirmada = $cartaCierre && $cartaCierre->firmado_gpt_at && $cartaCierre->firmado_cliente_at;
                        @endphp
                        <div class="text-right">
                            <p class="text-sm font-semibold {{ $proyecto->estado === 'cerrado' ? 'text-emerald-700' : 'text-amber-700' }}">
                                {{ $proyecto->estado === 'cerrado' ? 'cerrado' : ($cartaFirmada ? 'listo para cerrar' : 'pendiente') }}
                            </p>
                            <p class="text-xs text-slate-500">
                                {{ $cartaCierre ? 'Carta ' . ($cartaFirmada ? '✓✓' : ($cartaCierre->firmado_gpt_at ? '✓-' : '--')) : 'Sin carta' }}
                                ·
                                {{ $proyecto->postMortem ? 'PM ✓' : 'PM —' }}
                            </p>
                        </div>
                    </div>
                </a>
            @endif

            @if (in_array($proyecto->estado, ['adjudicado_firmado', 'en_ejecucion', 'en_cierre', 'cerrado']))
                <section class="bg-white border border-slate-200 rounded-xl p-6">
                    <div class="grid md:grid-cols-2 lg:grid-cols-5 gap-3">
                        <a href="{{ route('koms.index', $proyecto) }}"
                           class="block p-4 border border-slate-200 rounded-lg hover:border-gpt-400 hover:bg-gpt-50">
                            <h3 class="font-semibold text-slate-900">KOMs</h3>
                            <p class="text-xs text-slate-500 mt-1">{{ $proyecto->koms->count() }} reuniones</p>
                        </a>
                        <a href="{{ route('cronogramas.index', $proyecto) }}"
                           class="block p-4 border border-slate-200 rounded-lg hover:border-gpt-400 hover:bg-gpt-50">
                            <h3 class="font-semibold text-slate-900">Cronograma</h3>
                            <p class="text-xs text-slate-500 mt-1">
                                @php $cv = $proyecto->cronogramas->first(); @endphp
                                @if ($cv)
                                    v{{ $cv->version }}
                                @else
                                    Sin cronograma
                                @endif
                            </p>
                        </a>
                        <a href="{{ route('bom.index', $proyecto) }}"
                           class="block p-4 border border-slate-200 rounded-lg hover:border-gpt-400 hover:bg-gpt-50">
                            <h3 class="font-semibold text-slate-900">BOM/BOE</h3>
                            <p class="text-xs text-slate-500 mt-1">{{ $proyecto->bomBoeItems->count() }} items</p>
                        </a>
                        <a href="{{ route('suministros.show', $proyecto) }}"
                           class="block p-4 border border-slate-200 rounded-lg hover:border-gpt-400 hover:bg-gpt-50">
                            <h3 class="font-semibold text-slate-900">Suministros</h3>
                            <p class="text-xs text-slate-500 mt-1">
                                @if ($proyecto->listadoSuministros)
                                    {{ number_format((float) $proyecto->listadoSuministros->porcentaje_avance_global, 0) }}% avance
                                @else
                                    No abierto
                                @endif
                            </p>
                        </a>
                        <a href="{{ route('solicitudes.index', $proyecto) }}"
                           class="block p-4 border border-slate-200 rounded-lg hover:border-gpt-400 hover:bg-gpt-50">
                            <h3 class="font-semibold text-slate-900">Solicitudes Internas</h3>
                            <p class="text-xs text-slate-500 mt-1">{{ $proyecto->solicitudesInternas->count() }} registradas</p>
                        </a>
                    </div>

                    @if (in_array($proyecto->estado, ['en_ejecucion', 'en_cierre', 'cerrado']))
                        <div class="grid md:grid-cols-3 gap-3 mt-3">
                            <a href="{{ route('bitacoras.index', $proyecto) }}"
                               class="block p-4 border border-slate-200 rounded-lg hover:border-gpt-400 hover:bg-gpt-50">
                                <h3 class="font-semibold text-slate-900">📓 Bitácoras</h3>
                                <p class="text-xs text-slate-500 mt-1">{{ $proyecto->bitacoras->count() }} cargadas</p>
                            </a>
                            <a href="{{ route('reportes.index', $proyecto) }}"
                               class="block p-4 border border-slate-200 rounded-lg hover:border-gpt-400 hover:bg-gpt-50">
                                <h3 class="font-semibold text-slate-900">📊 Reportes semanales</h3>
                                <p class="text-xs text-slate-500 mt-1">{{ $proyecto->reportesSemanales->count() }} generados</p>
                            </a>
                            <a href="{{ route('viaticos.index', $proyecto) }}"
                               class="block p-4 border border-slate-200 rounded-lg hover:border-gpt-400 hover:bg-gpt-50">
                                <h3 class="font-semibold text-slate-900">💰 Viáticos</h3>
                                <p class="text-xs text-slate-500 mt-1">{{ $proyecto->solicitudesViaticos->count() }} solicitudes</p>
                            </a>
                        </div>
                    @endif
                </section>
            @endif

            @if (in_array($proyecto->estado, ['adjudicado_pendiente', 'adjudicado_firmado', 'en_ejecucion', 'en_cierre', 'cerrado']))
                <section class="bg-white border border-slate-200 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-semibold text-slate-900">Minuta de Entrega CP→DN</h2>
                        <a href="{{ route('minutas.show', $proyecto) }}" class="text-sm text-gpt-600 hover:underline">Abrir →</a>
                    </div>
                    @php($minuta = $proyecto->minutaEntrega)
                    @if (! $minuta)
                        <p class="text-sm text-slate-500">Pendiente de levantar.</p>
                    @else
                        <p class="text-sm text-slate-700">
                            Status:
                            <span class="rounded px-2 py-0.5 text-xs {{ $minuta->status === 'firmada' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $minuta->status }}
                            </span>
                            · Reunión {{ $minuta->fecha_reunion?->format('Y-m-d') }}
                            @if ($minuta->status === 'firmada')
                                · firmada {{ optional($minuta->firmado_at)->format('Y-m-d H:i') }}
                            @endif
                        </p>
                    @endif
                </section>
            @endif

            <section class="bg-white border border-slate-200 rounded-xl p-6">
                <h2 class="font-semibold text-slate-900 mb-3">Timeline</h2>
                @if ($proyecto->eventos->isEmpty())
                    <p class="text-sm text-slate-500">Aún no hay eventos.</p>
                @else
                    <ol class="relative border-l-2 border-slate-100 ml-2 space-y-4">
                        @foreach ($proyecto->eventos as $ev)
                            <li class="ml-4">
                                <span class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full bg-gpt-500 border-2 border-white"></span>
                                <p class="text-sm font-medium text-slate-900">{{ str_replace('_', ' ', $ev->tipo) }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $ev->created_at->diffForHumans() }} · {{ $ev->user?->name ?? 'sistema' }}
                                    @if ($ev->estado_anterior && $ev->estado_anterior !== $ev->estado_nuevo)
                                        · {{ $ev->estado_anterior }} → {{ $ev->estado_nuevo }}
                                    @endif
                                </p>
                                @if ($ev->comentario)
                                    <p class="text-xs text-slate-700 mt-1">{{ $ev->comentario }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        </div>

        <aside class="space-y-4">
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <h3 class="text-xs uppercase font-semibold text-slate-500 mb-3">Plazo</h3>
                <dl class="text-sm space-y-2">
                    <div class="flex justify-between"><dt class="text-slate-500">Inicio</dt><dd class="font-medium">{{ optional($proyecto->fecha_inicio_planeada)->format('Y-m-d') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Fin</dt><dd class="font-medium">{{ optional($proyecto->fecha_fin_planeada)->format('Y-m-d') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Distribución</dt><dd class="font-medium text-xs">{{ $proyecto->metodo_distribucion_plurianual }}</dd></div>
                </dl>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <h3 class="text-xs uppercase font-semibold text-slate-500 mb-3">Monto</h3>
                <p class="text-2xl font-semibold text-slate-900">
                    {{ $proyecto->monto_preliminar ? '$'.number_format((float) $proyecto->monto_preliminar, 0) : '—' }}
                    <span class="text-sm font-normal text-slate-500">{{ $proyecto->moneda }}</span>
                </p>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <h3 class="text-xs uppercase font-semibold text-slate-500 mb-3">Equipo</h3>
                <dl class="text-sm space-y-2">
                    <div><dt class="text-slate-500 text-xs">Director DN</dt><dd class="font-medium">{{ $proyecto->directorDn?->name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500 text-xs">Gerente proyectos</dt><dd class="font-medium">{{ $proyecto->gerenteProyectos?->name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500 text-xs">Gerente operaciones</dt><dd class="font-medium">{{ $proyecto->gerenteOperaciones?->name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500 text-xs">Ing. costos</dt><dd class="font-medium">{{ $proyecto->ingenieroCostos?->name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500 text-xs">Ing. proyectos</dt><dd class="font-medium">{{ $proyecto->ingenieroProyectos?->name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500 text-xs">Trainee</dt><dd class="font-medium">{{ $proyecto->trainee?->name ?? '—' }}</dd></div>
                </dl>
            </div>
        </aside>
    </div>
</x-layouts.app>
