<x-layouts.app :title="'BOM/BOE · ' . $proyecto->cp_numero">
    <a href="{{ route('oportunidades.show', $proyecto) }}" class="text-sm text-slate-500 hover:underline">← Volver al CP</a>

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">BOM / BOE</h1>
            <p class="text-slate-600 text-sm mt-1">
                <span class="font-mono">{{ $proyecto->cp_numero }}</span>
                @if ($proyecto->dn_numero) / <span class="font-mono text-emerald-700">{{ $proyecto->dn_numero }}</span> @endif
                · {{ $proyecto->cliente?->razon_social }} · {{ $proyecto->sublinea?->nombre }}
            </p>
        </div>
        @if ($items->isEmpty() && $tieneCotizacion)
            <form method="POST" action="{{ route('bom.importar', $proyecto) }}">
                @csrf
                <button class="rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm px-4 py-2">
                    Heredar partidas de cotización
                </button>
            </form>
        @endif
    </div>

    @php
        $statusBadges = [
            'en_almacen' => 'bg-emerald-100 text-emerald-800',
            'por_afilar' => 'bg-amber-100 text-amber-800',
            'por_fabricar' => 'bg-violet-100 text-violet-800',
            'por_comprar' => 'bg-blue-100 text-blue-800',
            'en_transito' => 'bg-indigo-100 text-indigo-800',
            'entregado' => 'bg-slate-100 text-slate-700',
        ];
        $statusLabels = [
            'en_almacen' => 'En almacén',
            'por_afilar' => 'Por afilar',
            'por_fabricar' => 'Por fabricar',
            'por_comprar' => 'Por comprar',
            'en_transito' => 'En tránsito',
            'entregado' => 'Entregado',
        ];
    @endphp

    @if (! $items->isEmpty())
        <div class="grid grid-cols-2 md:grid-cols-6 gap-2 mb-4">
            @foreach ($statusBadges as $key => $badge)
                <div class="bg-white border border-slate-200 rounded-lg p-3 text-center">
                    <p class="text-xs uppercase text-slate-500">{{ $statusLabels[$key] }}</p>
                    <p class="text-2xl font-semibold mt-1">{{ $resumen[$key] ?? 0 }}</p>
                </div>
            @endforeach
        </div>
    @endif

    @if ($items->isEmpty())
        <div class="bg-white border border-dashed border-slate-300 rounded-xl p-10 text-center">
            <p class="text-slate-500">Sin items en el BOM/BOE.
                @if ($tieneCotizacion) Usa "Heredar partidas de cotización" arriba o agrégalos manualmente. @endif
            </p>
        </div>
    @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden mb-4">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="text-left px-3 py-2">Tipo</th>
                        <th class="text-left px-3 py-2">Descripción</th>
                        <th class="text-right px-3 py-2">Cantidad</th>
                        <th class="text-left px-3 py-2">Unidad</th>
                        <th class="text-left px-3 py-2">Status</th>
                        <th class="text-left px-3 py-2">Responsable</th>
                        <th class="text-left px-3 py-2">Fecha req.</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($items as $it)
                        <tr class="hover:bg-slate-50">
                            <form method="POST" action="{{ route('bom.update', [$proyecto, $it]) }}" class="contents">
                                @csrf
                                @method('PATCH')
                                <td class="px-3 py-2">
                                    <select name="tipo" class="rounded border border-slate-200 px-2 py-1 text-xs">
                                        <option value="BOM" @selected($it->tipo === 'BOM')>BOM</option>
                                        <option value="BOE" @selected($it->tipo === 'BOE')>BOE</option>
                                    </select>
                                </td>
                                <td class="px-3 py-2 text-slate-700">{{ $it->descripcion }}</td>
                                <td class="px-3 py-2 text-right font-mono text-xs">{{ rtrim(rtrim(number_format((float) $it->cantidad, 4), '0'), '.') }}</td>
                                <td class="px-3 py-2 text-slate-500 text-xs">{{ $it->unidad }}</td>
                                <td class="px-3 py-2">
                                    <select name="status" onchange="this.form.submit()" class="rounded border border-slate-200 px-2 py-1 text-xs">
                                        @foreach ($statusLabels as $k => $lbl)
                                            <option value="{{ $k }}" @selected($it->status === $k)>{{ $lbl }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2 text-xs text-slate-600">
                                    <select name="responsable_id" onchange="this.form.submit()" class="rounded border border-slate-200 px-2 py-1 text-xs">
                                        <option value="">—</option>
                                        @foreach ($responsables as $r)
                                            <option value="{{ $r->id }}" @selected($it->responsable_id === $r->id)>{{ $r->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2 text-xs">
                                    <input type="date" name="fecha_requerida" value="{{ optional($it->fecha_requerida)->format('Y-m-d') }}" onchange="this.form.submit()"
                                           class="rounded border border-slate-200 px-2 py-1 text-xs" />
                                </td>
                                <td class="px-3 py-2 text-right"><button class="text-slate-500 hover:text-slate-900 text-xs">Guardar</button></td>
                            </form>
                            <td class="px-1">
                                <form method="POST" action="{{ route('bom.destroy', [$proyecto, $it]) }}"
                                      onsubmit="return confirm('¿Eliminar item?');" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-rose-600 hover:underline text-xs">−</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <details class="bg-white border border-slate-200 rounded-xl p-5">
        <summary class="cursor-pointer text-sm font-semibold text-gpt-600">+ Agregar item</summary>
        <form method="POST" action="{{ route('bom.store', $proyecto) }}" class="grid md:grid-cols-7 gap-3 mt-3">
            @csrf
            <label class="block">
                <span class="text-xs text-slate-600">Tipo *</span>
                <select name="tipo" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    <option value="BOM">BOM</option>
                    <option value="BOE">BOE</option>
                </select>
            </label>
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
                <select name="status" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    @foreach ($statusLabels as $k => $lbl)
                        <option value="{{ $k }}">{{ $lbl }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="text-xs text-slate-600">Fecha req.</span>
                <input name="fecha_requerida" type="date" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
            </label>
            <div class="md:col-span-7">
                <button class="rounded-md bg-slate-900 hover:bg-slate-800 text-white text-sm px-4 py-2">Agregar</button>
            </div>
        </form>
    </details>
</x-layouts.app>
