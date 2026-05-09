<x-layouts.app :title="'Libro · ' . $proyecto->cp_numero">
    <a href="{{ route('oportunidades.show', $proyecto) }}" class="text-sm text-slate-500 hover:underline">← Volver al CP</a>

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Libro de Proyecto</h1>
            <p class="text-slate-600 text-sm mt-1">
                <span class="font-mono">{{ $proyecto->cp_numero }}</span>
                @if ($proyecto->dn_numero) / <span class="font-mono text-emerald-700">{{ $proyecto->dn_numero }}</span> @endif
                · {{ $proyecto->cliente?->razon_social }} · {{ $proyecto->sublinea?->nombre }}
                · Apertura {{ optional($libro->fecha_apertura)->format('Y-m-d') }}
            </p>
        </div>
        <div class="text-right">
            <p class="text-3xl font-bold text-emerald-700">{{ number_format((float) $libro->porcentaje_avance_global, 1) }}%</p>
            <p class="text-xs text-slate-500">avance global del dossier</p>
            <a href="{{ route('libro.dossier', $proyecto) }}" class="inline-block mt-2 rounded-md bg-rose-600 hover:bg-rose-700 text-white text-xs px-3 py-1.5">📦 Dossier consolidado PDF</a>
        </div>
    </div>

    <div class="bg-slate-200 rounded-full h-3 mb-6 overflow-hidden">
        <div class="bg-emerald-500 h-3 transition-all" style="width: {{ (float) $libro->porcentaje_avance_global }}%"></div>
    </div>

    @if ($bloqueo['bloqueado'])
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 mb-6">
            <p class="text-sm font-semibold text-amber-900">D11 · Cierre bloqueado</p>
            <p class="text-xs text-amber-800 mt-1">El proyecto no puede cerrarse hasta completar las secciones siguientes:</p>
            <ul class="list-disc pl-5 mt-2 text-xs text-amber-900 space-y-0.5">
                @foreach ($bloqueo['razones'] as $razon)
                    <li>{{ $razon }}</li>
                @endforeach
            </ul>
        </div>
    @else
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 p-4 mb-6">
            <p class="text-sm font-semibold text-emerald-900">✓ Dossier completo · listo para cierre</p>
        </div>
    @endif

    @php
        $estadoBadge = [
            'pendiente' => 'bg-slate-100 text-slate-700',
            'en_proceso' => 'bg-amber-100 text-amber-800',
            'completo' => 'bg-emerald-100 text-emerald-800',
        ];
    @endphp

    <div class="space-y-3" x-data="{ open: '{{ optional($libro->secciones->firstWhere('estado', 'en_proceso'))->codigo ?? 'A' }}' }">
        @foreach ($libro->secciones as $seccion)
            <section class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <button type="button" @click="open === '{{ $seccion->codigo }}' ? open = null : open = '{{ $seccion->codigo }}'"
                        class="w-full flex items-center gap-4 p-4 text-left hover:bg-slate-50">
                    <span class="h-10 w-10 rounded-full bg-gpt-600 text-white font-bold flex items-center justify-center">{{ $seccion->codigo }}</span>
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-slate-900">{{ $seccion->nombre }}</p>
                        <p class="text-xs text-slate-500 truncate">
                            {{ $seccion->checklist->where('completado', true)->count() }}/{{ $seccion->checklist->count() }} items
                            @if ($seccion->responsable) · resp. {{ $seccion->responsable->name }} @endif
                            @if ($seccion->documentos->isNotEmpty()) · {{ $seccion->documentos->count() }} docs @endif
                        </p>
                    </div>
                    <div class="w-32">
                        <div class="bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="bg-emerald-500 h-2" style="width: {{ (float) $seccion->porcentaje_avance }}%"></div>
                        </div>
                        <p class="text-xs text-right mt-1 font-mono">{{ number_format((float) $seccion->porcentaje_avance, 0) }}%</p>
                    </div>
                    <span class="rounded text-xs px-2 py-0.5 {{ $estadoBadge[$seccion->estado] ?? 'bg-slate-100' }}">{{ $seccion->estado }}</span>
                    <svg class="h-5 w-5 transition-transform" :class="open === '{{ $seccion->codigo }}' ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div x-show="open === '{{ $seccion->codigo }}'" x-cloak class="border-t border-slate-100 p-5 grid lg:grid-cols-3 gap-5">
                    {{-- Checklist --}}
                    <div class="lg:col-span-2 space-y-2">
                        <h3 class="text-xs uppercase font-semibold text-slate-500">Checklist</h3>
                        @if ($seccion->checklist->isEmpty())
                            <p class="text-sm text-slate-500">Sin items.</p>
                        @else
                            <ul class="space-y-1">
                                @foreach ($seccion->checklist as $item)
                                    <li class="flex items-start gap-2 group hover:bg-slate-50 rounded p-1.5">
                                        <form method="POST" action="{{ route('libro.items.toggle', [$proyecto, $item]) }}" class="flex items-center mt-0.5">
                                            @csrf
                                            @method('PATCH')
                                            <button class="flex-shrink-0">
                                                @if ($item->completado)
                                                    <span class="inline-flex h-5 w-5 items-center justify-center rounded border-2 border-emerald-600 bg-emerald-600 text-white text-xs">✓</span>
                                                @else
                                                    <span class="inline-flex h-5 w-5 rounded border-2 border-slate-300 hover:border-gpt-500"></span>
                                                @endif
                                            </button>
                                        </form>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm {{ $item->completado ? 'text-slate-500 line-through' : 'text-slate-700' }}">{{ $item->item_descripcion }}</p>
                                            @if ($item->completado && $item->completadoPor)
                                                <p class="text-xs text-slate-400">por {{ $item->completadoPor->name }} · {{ optional($item->completado_at)->diffForHumans() }}</p>
                                            @endif
                                            @if ($item->evidencia)
                                                <a href="{{ route('libro.documentos.descargar', [$proyecto, $item->evidencia]) }}" class="text-xs text-gpt-600 hover:underline">📎 {{ $item->evidencia->nombre }}</a>
                                            @endif
                                        </div>
                                        <form method="POST" action="{{ route('libro.items.destroy', [$proyecto, $item]) }}"
                                              onsubmit="return confirm('¿Eliminar item?');" class="opacity-0 group-hover:opacity-100">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-rose-600 hover:underline text-xs">−</button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <details class="mt-3">
                            <summary class="cursor-pointer text-sm text-gpt-600 hover:underline">+ Agregar item al checklist</summary>
                            <form method="POST" action="{{ route('libro.items.store', [$proyecto, $seccion]) }}" class="flex gap-2 mt-2">
                                @csrf
                                <input name="item_descripcion" required minlength="3" maxlength="200" class="flex-1 rounded-md border border-slate-200 px-3 py-2 text-sm" placeholder="Nuevo item del checklist…" />
                                <button class="rounded-md bg-slate-900 hover:bg-slate-800 text-white text-sm px-4 py-2">Agregar</button>
                            </form>
                        </details>
                    </div>

                    {{-- Sidebar de la sección --}}
                    <div class="space-y-4">
                        {{-- Responsable --}}
                        <form method="POST" action="{{ route('libro.secciones.update', [$proyecto, $seccion]) }}" class="bg-slate-50 rounded-lg p-3 space-y-2">
                            @csrf
                            @method('PATCH')
                            <label class="block">
                                <span class="text-xs text-slate-600 font-semibold">Responsable</span>
                                <select name="responsable_id" onchange="this.form.submit()" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                                    <option value="">— sin asignar —</option>
                                    @foreach ($responsables as $u)
                                        <option value="{{ $u->id }}" @selected($seccion->responsable_id === $u->id)>{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="block">
                                <span class="text-xs text-slate-600 font-semibold">Observaciones</span>
                                <textarea name="observaciones" rows="2" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-xs">{{ $seccion->observaciones }}</textarea>
                            </label>
                            <button class="text-xs text-gpt-600 hover:underline">Guardar observaciones</button>
                        </form>

                        {{-- Documentos --}}
                        <div class="bg-slate-50 rounded-lg p-3">
                            <h4 class="text-xs uppercase font-semibold text-slate-500 mb-2">Documentos ({{ $seccion->documentos->count() }})</h4>
                            <ul class="space-y-1 mb-3">
                                @foreach ($seccion->documentos as $doc)
                                    <li class="flex items-center justify-between gap-2 text-xs">
                                        <a href="{{ route('libro.documentos.descargar', [$proyecto, $doc]) }}" class="text-gpt-600 hover:underline truncate flex-1">📎 {{ $doc->nombre }} <span class="text-slate-400">v{{ $doc->version }}</span></a>
                                        <form method="POST" action="{{ route('libro.documentos.destroy', [$proyecto, $doc]) }}"
                                              onsubmit="return confirm('¿Eliminar documento?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-rose-600 hover:underline">×</button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                            <form method="POST" action="{{ route('libro.documentos.upload', [$proyecto, $seccion]) }}" enctype="multipart/form-data" class="space-y-2">
                                @csrf
                                <input name="archivo" type="file" required class="text-xs w-full" />
                                @if ($seccion->checklist->isNotEmpty())
                                    <select name="link_checklist_id" class="w-full rounded border border-slate-200 px-2 py-1 text-xs">
                                        <option value="">— sin vincular a item —</option>
                                        @foreach ($seccion->checklist->where('completado', false) as $it)
                                            <option value="{{ $it->id }}">vincular a: {{ \Illuminate\Support\Str::limit($it->item_descripcion, 40) }}</option>
                                        @endforeach
                                    </select>
                                @endif
                                <button class="rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-xs px-3 py-1.5">Subir</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        @endforeach
    </div>

    <style>[x-cloak] { display: none !important; }</style>
</x-layouts.app>
