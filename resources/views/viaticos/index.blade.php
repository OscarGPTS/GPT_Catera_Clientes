<x-layouts.app :title="'Viáticos · ' . $proyecto->cp_numero">
    <a href="{{ route('oportunidades.show', $proyecto) }}" class="text-sm text-slate-500 hover:underline">← Volver al CP</a>

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Solicitudes de viáticos</h1>
            <p class="text-slate-600 text-sm mt-1">
                <span class="font-mono">{{ $proyecto->cp_numero }}</span> · {{ $proyecto->cliente?->razon_social }}
            </p>
        </div>
    </div>

    <details class="bg-white border border-slate-200 rounded-xl p-5 mb-6"
             x-data="{ personal: [{user_id: '', dias: 1}], partidas: [{concepto: 'hospedaje', monto_estimado: 0, observaciones: ''}] }">
        <summary class="cursor-pointer text-sm font-semibold text-gpt-600">+ Nueva solicitud</summary>
        <form method="POST" action="{{ route('viaticos.store', $proyecto) }}" class="space-y-4 mt-4">
            @csrf
            <div class="grid md:grid-cols-2 gap-3">
                <label class="block">
                    <span class="text-xs text-slate-600">Periodo inicio *</span>
                    <input name="periodo_inicio" type="date" required class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                </label>
                <label class="block">
                    <span class="text-xs text-slate-600">Periodo fin *</span>
                    <input name="periodo_fin" type="date" required class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                </label>
            </div>
            <label class="block">
                <span class="text-xs text-slate-600">Justificación</span>
                <textarea name="justificacion" rows="2" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm"></textarea>
            </label>

            <div>
                <p class="text-xs text-slate-600 font-semibold mb-1">Personal beneficiario</p>
                <template x-for="(p, idx) in personal" :key="idx">
                    <div class="flex gap-2 mb-1">
                        <select :name="`personal[${idx}][user_id]`" x-model="personal[idx].user_id" class="flex-1 rounded border border-slate-200 px-2 py-1 text-sm">
                            <option value="">— Seleccionar usuario —</option>
                            @foreach ($usuarios as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                        <input :name="`personal[${idx}][dias]`" x-model.number="personal[idx].dias" type="number" min="1" placeholder="Días" class="w-20 rounded border border-slate-200 px-2 py-1 text-sm" />
                        <button type="button" @click="personal.splice(idx, 1)" class="text-rose-600 text-xs">−</button>
                    </div>
                </template>
                <button type="button" @click="personal.push({user_id: '', dias: 1})" class="text-xs text-gpt-600 hover:underline">+ Agregar persona</button>
            </div>

            <div>
                <p class="text-xs text-slate-600 font-semibold mb-1">Partidas estimadas</p>
                <template x-for="(p, idx) in partidas" :key="idx">
                    <div class="flex gap-2 mb-1">
                        <select :name="`partidas[${idx}][concepto]`" x-model="partidas[idx].concepto" class="rounded border border-slate-200 px-2 py-1 text-sm">
                            <option value="hospedaje">Hospedaje</option>
                            <option value="alimentos">Alimentos</option>
                            <option value="transporte">Transporte</option>
                            <option value="otros">Otros</option>
                        </select>
                        <input :name="`partidas[${idx}][monto_estimado]`" x-model.number="partidas[idx].monto_estimado" type="number" step="0.01" min="0" placeholder="Estimado" class="w-32 rounded border border-slate-200 px-2 py-1 text-sm font-mono" />
                        <input :name="`partidas[${idx}][observaciones]`" x-model="partidas[idx].observaciones" placeholder="Observaciones" class="flex-1 rounded border border-slate-200 px-2 py-1 text-sm" />
                        <button type="button" @click="partidas.splice(idx, 1)" class="text-rose-600 text-xs">−</button>
                    </div>
                </template>
                <button type="button" @click="partidas.push({concepto: 'hospedaje', monto_estimado: 0, observaciones: ''})" class="text-xs text-gpt-600 hover:underline">+ Agregar partida</button>
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
                        <th class="text-left px-4 py-3">Periodo</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-left px-4 py-3">Solicitante</th>
                        <th class="text-left px-4 py-3">Personal</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($solicitudes as $s)
                        @php
                            $statusBadge = match ($s->status) {
                                'borrador' => 'bg-slate-100 text-slate-700',
                                'pendiente_servgrales' => 'bg-blue-100 text-blue-800',
                                'pendiente_direccion' => 'bg-amber-100 text-amber-800',
                                'aprobada' => 'bg-emerald-100 text-emerald-800',
                                'rechazada' => 'bg-rose-100 text-rose-800',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs">{{ $s->periodo_inicio->format('Y-m-d') }} → {{ $s->periodo_fin->format('Y-m-d') }}</td>
                            <td class="px-4 py-3"><span class="rounded text-xs px-2 py-0.5 {{ $statusBadge }}">{{ str_replace('_', ' ', $s->status) }}</span></td>
                            <td class="px-4 py-3 text-slate-600">{{ $s->solicitante?->name }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ $s->personal->count() }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('viaticos.show', [$proyecto, $s]) }}" class="text-gpt-600 hover:underline text-sm">Abrir</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.app>
