<x-layouts.app title="Reporte de Asignación">
    @php
        $colorClasses = [
            'emerald' => 'bg-emerald-100 text-emerald-900',
            'amber' => 'bg-amber-100 text-amber-900',
            'orange' => 'bg-orange-200 text-orange-900',
            'red' => 'bg-rose-200 text-rose-900',
            'gray' => 'bg-slate-100 text-slate-500',
        ];
        $mesesNombre = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    @endphp

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Reporte de Asignación por persona</h1>
            <p class="text-sm text-slate-500">Heatmap mes × persona — código de color por carga total (verde 0-4, amarillo 5-7, naranja 8-9, rojo 10+).</p>
        </div>
        <form method="GET" class="flex gap-2">
            <select name="año" onchange="this.form.submit()" class="rounded-md border-slate-200 text-sm">
                @for ($y = now()->year - 2; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" @selected($año === $y)>{{ $y }}</option>
                @endfor
            </select>
        </form>
    </div>

    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-xs text-slate-500 uppercase font-semibold">Equipo</p>
            <p class="text-2xl font-semibold text-slate-900 mt-1">{{ $personas->count() }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-xs text-slate-500 uppercase font-semibold">Carga promedio (mes actual)</p>
            <p class="text-2xl font-semibold text-slate-900 mt-1">
                @php
                    $current = $snapshots->flatten(1)->where('mes', now()->month);
                    $avg = $current->isNotEmpty() ? round($current->avg(fn ($s) => $s->totalProyectos()), 1) : 0;
                @endphp
                {{ $avg }}
            </p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-xs text-slate-500 uppercase font-semibold">Sobrecarga (≥10)</p>
            <p class="text-2xl font-semibold text-slate-900 mt-1">
                {{ $snapshots->flatten(1)->where('mes', now()->month)->filter(fn ($s) => $s->totalProyectos() >= 10)->count() }}
            </p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-xs text-slate-500 uppercase font-semibold">Subutilizados (0)</p>
            <p class="text-2xl font-semibold text-slate-900 mt-1">
                {{ $snapshots->flatten(1)->where('mes', now()->month)->filter(fn ($s) => $s->totalProyectos() === 0)->count() }}
            </p>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl overflow-x-auto">
        <table class="min-w-full text-xs">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left text-slate-600 sticky left-0 bg-slate-50">Persona</th>
                    @foreach ($meses as $m)
                        <th class="px-2 py-2 text-slate-600 font-semibold w-14">{{ $mesesNombre[$m - 1] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($personas as $persona)
                    @php $userSnapshots = $snapshots->get($persona->id, collect())->keyBy('mes'); @endphp
                    <tr>
                        <td class="px-3 py-2 sticky left-0 bg-white">
                            <div class="font-medium text-slate-900">{{ $persona->name }}</div>
                            <div class="text-[10px] text-slate-500">{{ $persona->puesto ?? '—' }}</div>
                        </td>
                        @foreach ($meses as $m)
                            @php
                                $snap = $userSnapshots->get($m);
                                $color = $snap ? $snap->colorCarga() : 'gray';
                                $total = $snap?->totalProyectos() ?? 0;
                            @endphp
                            <td class="px-1 py-1 text-center">
                                <div class="inline-flex items-center justify-center w-12 h-10 rounded-md font-semibold text-sm {{ $colorClasses[$color] }}">
                                    {{ $snap ? $total : '—' }}
                                </div>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="text-xs text-slate-500 mt-4">
        El snapshot se genera el día 1 de cada mes. Para forzar uno: <code>php artisan asignaciones:snapshot --mes=current</code>
    </p>
</x-layouts.app>
