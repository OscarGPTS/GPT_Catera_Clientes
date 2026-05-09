<x-layouts.app :title="'Solicitudes Internas · ' . $proyecto->cp_numero">
    <a href="{{ route('oportunidades.show', $proyecto) }}" class="text-sm text-slate-500 hover:underline">← Volver al CP</a>

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Solicitudes Internas</h1>
            <p class="text-slate-600 text-sm mt-1">
                <span class="font-mono">{{ $proyecto->cp_numero }}</span> · {{ $proyecto->cliente?->razon_social }}
            </p>
        </div>
    </div>

    <details open class="bg-white border border-slate-200 rounded-xl p-5 mb-6"
             x-data="{ items: [{descripcion: '', cantidad: 1, unidad: '', especificacion: ''}] }">
        <summary class="cursor-pointer text-sm font-semibold text-gpt-600">+ Nueva solicitud</summary>
        <form method="POST" action="{{ route('solicitudes.store', $proyecto) }}" class="space-y-4 mt-4">
            @csrf
            <div class="grid md:grid-cols-3 gap-3">
                <label class="block">
                    <span class="text-xs text-slate-600">Tipo *</span>
                    <select name="tipo" required class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                        <option value="requisicion_compras">Requisición a Compras</option>
                        <option value="orden_trabajo_ingenieria">OT a Ingeniería</option>
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs text-slate-600">Asignar a</span>
                    <select name="asignado_id" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                        <option value="">— sin asignar —</option>
                        @foreach ($asignables as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-xs text-slate-600">Fecha respuesta requerida</span>
                    <input name="fecha_respuesta_requerida" type="date" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                </label>
            </div>
            <label class="block">
                <span class="text-xs text-slate-600">Descripción</span>
                <textarea name="descripcion" rows="2" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm"></textarea>
            </label>

            <div>
                <p class="text-xs text-slate-600 font-semibold mb-2">Items</p>
                <div class="space-y-2">
                    <template x-for="(it, idx) in items" :key="idx">
                        <div class="grid grid-cols-12 gap-2 items-center">
                            <input :name="`items[${idx}][descripcion]`" x-model="items[idx].descripcion" placeholder="Descripción" class="col-span-5 rounded-md border border-slate-200 px-3 py-2 text-sm" />
                            <input :name="`items[${idx}][cantidad]`" x-model.number="items[idx].cantidad" type="number" step="0.0001" min="0.0001" class="col-span-2 rounded-md border border-slate-200 px-3 py-2 text-sm" />
                            <input :name="`items[${idx}][unidad]`" x-model="items[idx].unidad" placeholder="Unidad" class="col-span-2 rounded-md border border-slate-200 px-3 py-2 text-sm" />
                            <input :name="`items[${idx}][especificacion]`" x-model="items[idx].especificacion" placeholder="Especificación" class="col-span-2 rounded-md border border-slate-200 px-3 py-2 text-sm" />
                            <button type="button" @click="items.splice(idx, 1)" class="col-span-1 text-rose-600 hover:underline text-xs">−</button>
                        </div>
                    </template>
                </div>
                <button type="button" @click="items.push({descripcion: '', cantidad: 1, unidad: '', especificacion: ''})" class="text-sm text-gpt-600 hover:underline mt-2">+ Item</button>
            </div>

            <button class="rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm px-4 py-2">Crear borrador</button>
        </form>
    </details>

    @if ($solicitudes->isEmpty())
        <div class="bg-white border border-dashed border-slate-300 rounded-xl p-10 text-center">
            <p class="text-slate-500">Sin solicitudes registradas.</p>
        </div>
    @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="text-left px-4 py-3">Tipo</th>
                        <th class="text-left px-4 py-3">Estado</th>
                        <th class="text-left px-4 py-3">Solicitante</th>
                        <th class="text-left px-4 py-3">Asignado</th>
                        <th class="text-left px-4 py-3">Items</th>
                        <th class="text-left px-4 py-3">Fecha solic.</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($solicitudes as $s)
                        @php
                            $estadoBadge = match ($s->estado) {
                                'borrador' => 'bg-slate-100 text-slate-700',
                                'emitida' => 'bg-blue-100 text-blue-800',
                                'en_proceso' => 'bg-amber-100 text-amber-800',
                                'respondida' => 'bg-emerald-100 text-emerald-800',
                                'cancelada' => 'bg-rose-100 text-rose-800',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 text-slate-700 text-xs">{{ str_replace('_', ' ', $s->tipo) }}</td>
                            <td class="px-4 py-3"><span class="rounded text-xs px-2 py-0.5 {{ $estadoBadge }}">{{ $s->estado }}</span></td>
                            <td class="px-4 py-3 text-slate-600">{{ $s->solicitante?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $s->asignado?->name ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $s->items->count() }}</td>
                            <td class="px-4 py-3 text-xs">{{ optional($s->fecha_solicitud)->format('Y-m-d') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('solicitudes.show', [$proyecto, $s]) }}" class="text-gpt-600 hover:underline text-sm">Abrir</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.app>
