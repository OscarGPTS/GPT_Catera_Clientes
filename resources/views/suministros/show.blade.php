<x-layouts.app :title="'Suministros · ' . $proyecto->cp_numero">
    <a href="{{ route('oportunidades.show', $proyecto) }}" class="text-sm text-slate-500 hover:underline">← Volver al CP</a>

    @php
        $etapas = [
            '0-25' => ['titulo' => 'Definición (0-25%)', 'color' => 'bg-slate-100 border-slate-300'],
            '26-50' => ['titulo' => 'Cotizando (26-50%)', 'color' => 'bg-amber-50 border-amber-300'],
            '51-75' => ['titulo' => 'Ordenado / en tránsito (51-75%)', 'color' => 'bg-blue-50 border-blue-300'],
            '76-100' => ['titulo' => 'Entregado (76-100%)', 'color' => 'bg-emerald-50 border-emerald-300'],
        ];
    @endphp

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Listado de Suministros</h1>
            <p class="text-slate-600 text-sm mt-1">
                <span class="font-mono">{{ $proyecto->cp_numero }}</span>
                @if ($proyecto->dn_numero) / <span class="font-mono text-emerald-700">{{ $proyecto->dn_numero }}</span> @endif
                · {{ $proyecto->cliente?->razon_social }}
                · <strong>Avance global: {{ number_format((float) $listado->porcentaje_avance_global, 1) }}%</strong>
            </p>
        </div>
        @if ($listado->items->isEmpty() && $tieneBom)
            <form method="POST" action="{{ route('suministros.importar', $proyecto) }}">
                @csrf
                <button class="rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm px-4 py-2">Heredar del BOM/BOE</button>
            </form>
        @endif
    </div>

    <div class="bg-slate-200 rounded-full h-3 mb-6 overflow-hidden">
        <div class="bg-emerald-500 h-3" style="width: {{ (float) $listado->porcentaje_avance_global }}%"></div>
    </div>

    @if ($listado->items->isEmpty())
        <div class="bg-white border border-dashed border-slate-300 rounded-xl p-10 text-center mb-6">
            <p class="text-slate-500">Sin items en el listado.
                @if ($tieneBom) Hereda del BOM/BOE arriba o agrégalos manualmente. @endif
            </p>
        </div>
    @else
        <div class="grid md:grid-cols-4 gap-3 mb-6">
            @foreach ($etapas as $etapaKey => $etapaInfo)
                <div class="rounded-xl border-2 {{ $etapaInfo['color'] }} p-3">
                    <h3 class="text-xs uppercase font-semibold text-slate-700 mb-3">{{ $etapaInfo['titulo'] }}</h3>
                    <p class="text-xs text-slate-500 mb-3">{{ $porEtapa->get($etapaKey, collect())->count() }} items</p>
                    <div class="space-y-2">
                        @foreach ($porEtapa->get($etapaKey, collect()) as $item)
                            <div class="bg-white rounded-lg p-3 border border-slate-200 text-xs">
                                <p class="font-medium text-slate-900 line-clamp-2">{{ $item->descripcion }}</p>
                                <div class="flex items-center justify-between mt-2 text-slate-500">
                                    <span class="font-mono">{{ rtrim(rtrim(number_format((float) $item->cantidad, 4), '0'), '.') }} {{ $item->unidad }}</span>
                                    <span>{{ optional($item->fecha_requerida)->format('M d') ?? '—' }}</span>
                                </div>
                                <div class="flex gap-1 mt-2">
                                    <form method="POST" action="{{ route('suministros.items.update', [$proyecto, $item]) }}" class="flex-1">
                                        @csrf
                                        @method('PATCH')
                                        <select name="status" onchange="this.form.submit()" class="w-full rounded border border-slate-200 px-1 py-0.5 text-[10px]">
                                            @foreach ($statusOptions as $k => $lbl)
                                                <option value="{{ $k }}" @selected($item->status === $k)>{{ $lbl }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                    <form method="POST" action="{{ route('suministros.items.destroy', [$proyecto, $item]) }}"
                                          onsubmit="return confirm('¿Eliminar?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-rose-600 text-[10px] hover:underline">×</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <details class="bg-white border border-slate-200 rounded-xl p-5">
        <summary class="cursor-pointer text-sm font-semibold text-gpt-600">+ Agregar item al listado</summary>
        <form method="POST" action="{{ route('suministros.items.store', $proyecto) }}" class="grid md:grid-cols-5 gap-3 mt-3">
            @csrf
            <label class="md:col-span-2 block">
                <span class="text-xs text-slate-600">Descripción *</span>
                <input name="descripcion" required minlength="3" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
            </label>
            <label class="block">
                <span class="text-xs text-slate-600">Cantidad *</span>
                <input name="cantidad" type="number" step="0.0001" min="0.0001" required class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
            </label>
            <label class="block">
                <span class="text-xs text-slate-600">Unidad</span>
                <input name="unidad" maxlength="20" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
            </label>
            <label class="block">
                <span class="text-xs text-slate-600">Status *</span>
                <select name="status" required class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    @foreach ($statusOptions as $k => $lbl)
                        <option value="{{ $k }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="text-xs text-slate-600">Fecha req.</span>
                <input name="fecha_requerida" type="date" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
            </label>
            <div class="md:col-span-5">
                <button class="rounded-md bg-slate-900 hover:bg-slate-800 text-white text-sm px-4 py-2">Agregar</button>
            </div>
        </form>
    </details>
</x-layouts.app>
