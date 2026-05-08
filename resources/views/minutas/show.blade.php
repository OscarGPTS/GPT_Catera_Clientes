<x-layouts.app :title="'Minuta CP→DN · ' . $proyecto->cp_numero">
    <a href="{{ route('oportunidades.show', $proyecto) }}" class="text-sm text-slate-500 hover:underline">← Volver al CP</a>

    @php
        $bloqueada = $minuta->status === 'firmada';
        $miParticipacion = $minuta->participantes->firstWhere('user_id', auth()->id());
    @endphp

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">
                Minuta de Entrega CP→DN
                <span class="ml-2 inline-block rounded text-xs px-2 py-0.5
                    {{ $bloqueada ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                    {{ $minuta->status }}
                </span>
            </h1>
            <p class="text-slate-600 text-sm mt-1">
                <span class="font-mono">{{ $proyecto->cp_numero }}</span>
                @if ($proyecto->dn_numero) / <span class="font-mono text-emerald-700">{{ $proyecto->dn_numero }}</span> @endif
                · {{ $proyecto->cliente?->razon_social }} · {{ $proyecto->sublinea?->nombre }}
            </p>
        </div>
        @if ($bloqueada)
            <a href="{{ route('minutas.pdf', $proyecto) }}" class="rounded-md bg-rose-600 hover:bg-rose-700 text-white text-sm px-4 py-2">Descargar PDF</a>
        @endif
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <section class="bg-white border border-slate-200 rounded-xl p-6">
                <h2 class="font-semibold text-slate-900 mb-3">Datos de la reunión</h2>
                <form method="POST" action="{{ route('minutas.update', $proyecto) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <fieldset @if ($bloqueada) disabled @endif class="space-y-4">
                        <div class="grid md:grid-cols-3 gap-3">
                            <label class="block">
                                <span class="text-xs text-slate-600">Fecha *</span>
                                <input name="fecha_reunion" type="date" required
                                       value="{{ $minuta->fecha_reunion?->format('Y-m-d') }}"
                                       class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                            </label>
                            <label class="block">
                                <span class="text-xs text-slate-600">Hora inicio</span>
                                <input name="hora_inicio" type="time" value="{{ $minuta->hora_inicio }}" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                            </label>
                            <label class="block">
                                <span class="text-xs text-slate-600">Hora fin</span>
                                <input name="hora_fin" type="time" value="{{ $minuta->hora_fin }}" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                            </label>
                        </div>
                        <label class="block">
                            <span class="text-xs text-slate-600">Modalidad *</span>
                            <select name="modalidad" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                                @foreach (['virtual', 'presencial', 'mixta'] as $mod)
                                    <option value="{{ $mod }}" @selected($minuta->modalidad === $mod)>{{ ucfirst($mod) }}</option>
                                @endforeach
                            </select>
                        </label>

                        <div>
                            <span class="text-xs text-slate-600 font-semibold">Orden del día</span>
                            <div class="space-y-1 mt-1" x-data="{ items: {{ Js::from(array_values((array) ($minuta->orden_del_dia ?? []))) }} }">
                                <template x-for="(item, idx) in items" :key="idx">
                                    <div class="flex gap-2">
                                        <input :name="`orden_del_dia[${idx}]`" x-model="items[idx]"
                                               class="flex-1 rounded-md border border-slate-200 px-3 py-2 text-sm" />
                                        <button type="button" @click="items.splice(idx, 1)" class="text-rose-600 hover:underline text-xs">−</button>
                                    </div>
                                </template>
                                <button type="button" @click="items.push('')" class="text-sm text-gpt-600 hover:underline">+ Agregar punto</button>
                            </div>
                        </div>

                        <div>
                            <span class="text-xs text-slate-600 font-semibold">Acuerdos</span>
                            <div class="space-y-2 mt-1" x-data="{ items: {{ Js::from(array_values((array) ($minuta->acuerdos ?? []))) }} }">
                                <template x-for="(item, idx) in items" :key="idx">
                                    <div class="grid grid-cols-12 gap-2 items-center">
                                        <input :name="`acuerdos[${idx}][texto]`" x-model="items[idx].texto" placeholder="Acuerdo"
                                               class="col-span-6 rounded-md border border-slate-200 px-3 py-2 text-sm" />
                                        <input :name="`acuerdos[${idx}][responsable]`" x-model="items[idx].responsable" placeholder="Responsable"
                                               class="col-span-3 rounded-md border border-slate-200 px-3 py-2 text-sm" />
                                        <input :name="`acuerdos[${idx}][fecha]`" x-model="items[idx].fecha" type="date"
                                               class="col-span-2 rounded-md border border-slate-200 px-3 py-2 text-sm" />
                                        <button type="button" @click="items.splice(idx, 1)" class="col-span-1 text-rose-600 hover:underline text-xs">−</button>
                                    </div>
                                </template>
                                <button type="button" @click="items.push({texto: '', responsable: '', fecha: ''})" class="text-sm text-gpt-600 hover:underline">+ Agregar acuerdo</button>
                            </div>
                        </div>

                        <button class="rounded-md bg-slate-900 hover:bg-slate-800 text-white text-sm px-4 py-2">Guardar cambios</button>
                    </fieldset>
                </form>
            </section>

            <section class="bg-white border border-slate-200 rounded-xl p-6">
                <h2 class="font-semibold text-slate-900 mb-3">Participantes</h2>
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500 border-b">
                        <tr>
                            <th class="text-left py-2">Nombre</th>
                            <th class="text-left py-2">Rol en minuta</th>
                            <th class="text-left py-2">Firma</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($minuta->participantes as $p)
                            <tr>
                                <td class="py-2">{{ $p->user?->name ?? '—' }}</td>
                                <td class="py-2 text-slate-600 text-xs">{{ $p->rol_en_minuta ?? '—' }}</td>
                                <td class="py-2">
                                    @if ($p->firma_pendiente)
                                        <span class="inline-block rounded bg-amber-100 text-amber-800 text-xs px-2 py-0.5">pendiente</span>
                                    @else
                                        <span class="inline-block rounded bg-emerald-100 text-emerald-800 text-xs px-2 py-0.5">firmada {{ optional($p->firmado_at)->format('Y-m-d') }}</span>
                                    @endif
                                </td>
                                <td class="py-2 text-right">
                                    @if (! $bloqueada)
                                        <form method="POST" action="{{ route('minutas.participantes.remove', [$proyecto, $p->user_id]) }}"
                                              onsubmit="return confirm('¿Quitar a este participante?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-rose-600 hover:underline text-xs">Quitar</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                @if (! $bloqueada)
                    <form method="POST" action="{{ route('minutas.participantes.add', $proyecto) }}" class="grid md:grid-cols-3 gap-2 mt-4 p-3 bg-slate-50 rounded-lg">
                        @csrf
                        <select name="user_id" required class="rounded-md border border-slate-200 px-3 py-2 text-sm">
                            <option value="">Seleccionar usuario…</option>
                            @foreach ($candidatos as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                        <input name="rol_en_minuta" placeholder="Rol (ej. Recibe DN)" class="rounded-md border border-slate-200 px-3 py-2 text-sm" />
                        <button class="rounded-md bg-slate-900 hover:bg-slate-800 text-white text-sm px-4 py-2">Agregar</button>
                    </form>
                @endif
            </section>
        </div>

        <aside class="space-y-4">
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <h3 class="text-xs uppercase font-semibold text-slate-500 mb-3">Mi firma</h3>
                @if (! $miParticipacion)
                    <p class="text-sm text-slate-500">No estás listado como participante.</p>
                @elseif (! $miParticipacion->firma_pendiente)
                    <p class="text-sm text-emerald-700">Ya firmaste esta minuta el {{ optional($miParticipacion->firmado_at)->format('Y-m-d H:i') }}.</p>
                @else
                    <p class="text-sm text-slate-700 mb-3">Tu firma está pendiente. Una vez todos firmen, la minuta se sellará y podrás iniciar ejecución.</p>
                    <form method="POST" action="{{ route('minutas.firmar', $proyecto) }}">
                        @csrf
                        <button class="rounded-md bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 py-2 w-full">Firmar minuta</button>
                    </form>
                @endif
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <h3 class="text-xs uppercase font-semibold text-slate-500 mb-3">D10 · Reglas</h3>
                <p class="text-xs text-slate-500">
                    Si <code>minuta_entrega_obligatoria</code> = true, esta minuta debe estar 100% firmada antes
                    de poder iniciar ejecución. Cambia el setting en
                    <a href="#" class="text-gpt-600 hover:underline">Admin · Settings</a>.
                </p>
            </div>
        </aside>
    </div>
</x-layouts.app>
