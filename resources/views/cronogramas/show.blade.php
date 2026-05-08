<x-layouts.app :title="'Cronograma v' . $cronograma->version">
    <a href="{{ route('cronogramas.index', $proyecto) }}" class="text-sm text-slate-500 hover:underline">← Versiones</a>

    @php
        $rangoInicio = $cronograma->fecha_inicio ?? now();
        $rangoFin = $cronograma->fecha_fin ?? now()->addMonths(2);
        $totalDias = max(1, \Carbon\Carbon::parse($rangoInicio)->diffInDays(\Carbon\Carbon::parse($rangoFin))) ?: 1;
    @endphp

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">
                Cronograma <span class="font-mono">v{{ $cronograma->version }}</span>
            </h1>
            <p class="text-slate-600 text-sm mt-1">
                <span class="font-mono">{{ $proyecto->cp_numero }}</span> · {{ $proyecto->cliente?->razon_social }}
                · {{ optional($cronograma->fecha_inicio)->format('Y-m-d') }} → {{ optional($cronograma->fecha_fin)->format('Y-m-d') }}
                · <strong>{{ number_format($avanceGlobal, 1) }}% avance</strong>
            </p>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <section class="bg-white border border-slate-200 rounded-xl p-6 overflow-hidden">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-slate-900">Actividades · vista Gantt</h2>
                </div>

                @if ($cronograma->actividades->isEmpty())
                    <p class="text-sm text-slate-500">Sin actividades aún.</p>
                @else
                    <div class="space-y-1.5 overflow-x-auto">
                        <div class="flex items-center text-xs uppercase text-slate-400 border-b pb-1">
                            <span class="w-72 shrink-0 pl-2">Actividad</span>
                            <span class="flex-1">{{ optional($rangoInicio)->format('M Y') }} — {{ optional($rangoFin)->format('M Y') }}</span>
                            <span class="w-14 text-right pr-2">%</span>
                        </div>
                        @foreach ($cronograma->actividades as $act)
                            @php
                                $offsetDias = $act->fecha_inicio_planeada
                                    ? \Carbon\Carbon::parse($rangoInicio)->diffInDays($act->fecha_inicio_planeada)
                                    : 0;
                                $duracionDias = $act->fecha_fin_planeada && $act->fecha_inicio_planeada
                                    ? max(1, \Carbon\Carbon::parse($act->fecha_inicio_planeada)->diffInDays($act->fecha_fin_planeada))
                                    : 0;
                                $left = max(0, ($offsetDias / $totalDias) * 100);
                                $width = min(100 - $left, max(0.5, ($duracionDias / $totalDias) * 100));
                                $colorBar = $act->porcentaje_avance >= 100
                                    ? 'bg-emerald-500'
                                    : ($act->porcentaje_avance > 0 ? 'bg-gpt-500' : 'bg-slate-300');
                            @endphp
                            <div class="flex items-center text-sm hover:bg-slate-50 rounded">
                                <span class="w-72 shrink-0 pl-2 truncate" title="{{ $act->nombre }}">
                                    @if ($act->codigo) <span class="font-mono text-xs text-slate-400">{{ $act->codigo }}</span> @endif
                                    {{ $act->nombre }}
                                </span>
                                <span class="flex-1 relative h-6">
                                    <span class="absolute h-3 top-1.5 rounded {{ $colorBar }}"
                                          style="left: {{ $left }}%; width: {{ $width }}%;"></span>
                                    <span class="absolute h-3 top-1.5 rounded bg-emerald-700"
                                          style="left: {{ $left }}%; width: {{ ($width * (float) $act->porcentaje_avance) / 100 }}%;"></span>
                                </span>
                                <span class="w-14 text-right pr-2 text-xs font-mono text-slate-600">{{ number_format((float) $act->porcentaje_avance, 0) }}%</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="bg-white border border-slate-200 rounded-xl p-6">
                <h2 class="font-semibold text-slate-900 mb-3">Detalle de actividades</h2>
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500 border-b">
                        <tr>
                            <th class="text-left py-2">Cód.</th>
                            <th class="text-left py-2">Nombre</th>
                            <th class="text-left py-2">Inicio</th>
                            <th class="text-left py-2">Fin</th>
                            <th class="text-right py-2">Avance</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($cronograma->actividades as $act)
                            <tr>
                                <td class="py-2 font-mono text-xs text-slate-500">{{ $act->codigo ?? '—' }}</td>
                                <td class="py-2 text-slate-700">{{ $act->nombre }}</td>
                                <td class="py-2 text-xs">{{ optional($act->fecha_inicio_planeada)->format('Y-m-d') }}</td>
                                <td class="py-2 text-xs">{{ optional($act->fecha_fin_planeada)->format('Y-m-d') }}</td>
                                <td class="py-2 text-right">
                                    <form method="POST" action="{{ route('cronogramas.actividades.update', [$proyecto, $cronograma, $act]) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <input name="porcentaje_avance" type="number" min="0" max="100" step="1"
                                               value="{{ (int) $act->porcentaje_avance }}"
                                               onchange="this.form.submit()"
                                               class="w-16 rounded border border-slate-200 px-2 py-1 text-xs text-right" />
                                    </form>
                                </td>
                                <td class="py-2 text-right">
                                    <form method="POST" action="{{ route('cronogramas.actividades.destroy', [$proyecto, $cronograma, $act]) }}"
                                          onsubmit="return confirm('¿Eliminar actividad {{ $act->codigo ?? $act->nombre }}?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-rose-600 hover:underline text-xs">−</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <details class="mt-4">
                    <summary class="cursor-pointer text-sm text-gpt-600 hover:underline">+ Agregar actividad</summary>
                    <form method="POST" action="{{ route('cronogramas.actividades.store', [$proyecto, $cronograma]) }}"
                          class="grid md:grid-cols-6 gap-3 mt-3 p-4 bg-slate-50 rounded-lg">
                        @csrf
                        <label class="block">
                            <span class="text-xs text-slate-600">Código</span>
                            <input name="codigo" maxlength="30" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono" />
                        </label>
                        <label class="md:col-span-2 block">
                            <span class="text-xs text-slate-600">Nombre *</span>
                            <input name="nombre" required minlength="3" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                        </label>
                        <label class="block">
                            <span class="text-xs text-slate-600">Inicio</span>
                            <input name="fecha_inicio_planeada" type="date" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                        </label>
                        <label class="block">
                            <span class="text-xs text-slate-600">Fin</span>
                            <input name="fecha_fin_planeada" type="date" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                        </label>
                        <label class="block">
                            <span class="text-xs text-slate-600">% avance</span>
                            <input name="porcentaje_avance" type="number" min="0" max="100" step="1" value="0" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                        </label>
                        <div class="md:col-span-6">
                            <button class="rounded-md bg-slate-900 hover:bg-slate-800 text-white text-sm px-4 py-2">Agregar</button>
                        </div>
                    </form>
                </details>
            </section>
        </div>

        <aside class="space-y-4">
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <h3 class="text-xs uppercase font-semibold text-slate-500 mb-3">Resumen</h3>
                <dl class="text-sm space-y-2">
                    <div class="flex justify-between"><dt class="text-slate-500">Actividades</dt><dd class="font-mono">{{ $cronograma->actividades->count() }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Avance global</dt><dd class="font-mono font-semibold text-emerald-700">{{ number_format($avanceGlobal, 1) }}%</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Inicio</dt><dd class="text-xs">{{ optional($cronograma->fecha_inicio)->format('Y-m-d') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Fin</dt><dd class="text-xs">{{ optional($cronograma->fecha_fin)->format('Y-m-d') ?? '—' }}</dd></div>
                </dl>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-5 text-xs text-slate-500">
                Importador MS Project (.mpp/.xml/.csv): <span class="text-amber-700">pendiente M5b</span>.
            </div>
        </aside>
    </div>
</x-layouts.app>
