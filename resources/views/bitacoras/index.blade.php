<x-layouts.app :title="'Bitácoras · ' . $proyecto->cp_numero">
    <a href="{{ route('oportunidades.show', $proyecto) }}" class="text-sm text-slate-500 hover:underline">← Volver al CP</a>

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Bitácora diaria</h1>
            <p class="text-slate-600 text-sm mt-1">
                <span class="font-mono">{{ $proyecto->cp_numero }}</span>
                @if ($proyecto->dn_numero) / <span class="font-mono text-emerald-700">{{ $proyecto->dn_numero }}</span> @endif
                · {{ $proyecto->cliente?->razon_social }}
            </p>
        </div>
    </div>

    <details open class="bg-white border border-slate-200 rounded-xl p-5 mb-6"
             x-data="{ personal: [], equipos: [], proveedores: [] }">
        <summary class="cursor-pointer text-sm font-semibold text-gpt-600">+ Nueva bitácora</summary>
        <form method="POST" action="{{ route('bitacoras.store', $proyecto) }}" class="space-y-4 mt-4">
            @csrf
            <div class="grid md:grid-cols-3 gap-3">
                <label class="block">
                    <span class="text-xs text-slate-600">Fecha *</span>
                    <input name="fecha" type="date" required value="{{ now()->toDateString() }}" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                </label>
            </div>
            <label class="block">
                <span class="text-xs text-slate-600">Relación de actividades *</span>
                <textarea name="relacion_actividades" required minlength="10" rows="6"
                          class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono"
                          placeholder="Describe lo realizado, hallazgos, retrasos, incidentes…"></textarea>
                <span class="text-[10px] text-slate-400">D7: keywords como "retraso", "falla", "incidente" disparan alerta de desviación.</span>
            </label>

            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <p class="text-xs text-slate-600 font-semibold mb-1">Personal GPT</p>
                    <template x-for="(p, idx) in personal" :key="idx">
                        <div class="flex gap-1 mb-1">
                            <input :name="`personal_gpt[${idx}][nombre]`" x-model="personal[idx].nombre" placeholder="Nombre" class="flex-1 rounded border border-slate-200 px-2 py-1 text-xs" />
                            <input :name="`personal_gpt[${idx}][rol]`" x-model="personal[idx].rol" placeholder="Rol" class="flex-1 rounded border border-slate-200 px-2 py-1 text-xs" />
                            <button type="button" @click="personal.splice(idx, 1)" class="text-rose-600 text-xs">−</button>
                        </div>
                    </template>
                    <button type="button" @click="personal.push({nombre: '', rol: ''})" class="text-xs text-gpt-600 hover:underline">+ Agregar</button>
                </div>
                <div>
                    <p class="text-xs text-slate-600 font-semibold mb-1">Equipos en sitio</p>
                    <template x-for="(e, idx) in equipos" :key="idx">
                        <div class="flex gap-1 mb-1">
                            <input :name="`equipos_en_sitio[${idx}][nombre]`" x-model="equipos[idx].nombre" placeholder="Equipo" class="flex-1 rounded border border-slate-200 px-2 py-1 text-xs" />
                            <input :name="`equipos_en_sitio[${idx}][cantidad]`" x-model="equipos[idx].cantidad" placeholder="Cant." class="w-16 rounded border border-slate-200 px-2 py-1 text-xs" />
                            <button type="button" @click="equipos.splice(idx, 1)" class="text-rose-600 text-xs">−</button>
                        </div>
                    </template>
                    <button type="button" @click="equipos.push({nombre: '', cantidad: ''})" class="text-xs text-gpt-600 hover:underline">+ Agregar</button>
                </div>
                <div>
                    <p class="text-xs text-slate-600 font-semibold mb-1">Proveedores / subcontratistas</p>
                    <template x-for="(pr, idx) in proveedores" :key="idx">
                        <div class="flex gap-1 mb-1">
                            <input :name="`proveedores_subcontratistas[${idx}][nombre]`" x-model="proveedores[idx].nombre" placeholder="Nombre" class="flex-1 rounded border border-slate-200 px-2 py-1 text-xs" />
                            <button type="button" @click="proveedores.splice(idx, 1)" class="text-rose-600 text-xs">−</button>
                        </div>
                    </template>
                    <button type="button" @click="proveedores.push({nombre: ''})" class="text-xs text-gpt-600 hover:underline">+ Agregar</button>
                </div>
            </div>

            <button class="rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm px-4 py-2">Cargar bitácora</button>
        </form>
    </details>

    @if ($bitacoras->isEmpty())
        <div class="bg-white border border-dashed border-slate-300 rounded-xl p-10 text-center">
            <p class="text-slate-500">Aún no se ha cargado ninguna bitácora.</p>
        </div>
    @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="text-left px-4 py-3">Fecha</th>
                        <th class="text-left px-4 py-3">Cargado por</th>
                        <th class="text-left px-4 py-3">Resumen</th>
                        <th class="text-left px-4 py-3">VoBo</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($bitacoras as $b)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono">
                                {{ $b->fecha->format('Y-m-d') }}
                                @if ($b->tieneDesviacion())
                                    <span class="ml-1 text-rose-600" title="Posible desviación">⚠</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ $b->cargadoPor?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-700 text-xs">{{ \Illuminate\Support\Str::limit($b->relacion_actividades, 80) }}</td>
                            <td class="px-4 py-3">
                                @if ($b->firmado_at)
                                    <span class="rounded text-xs px-2 py-0.5 bg-emerald-100 text-emerald-800">firmada {{ $b->firmado_at->format('Y-m-d') }}</span>
                                @else
                                    <span class="rounded text-xs px-2 py-0.5 bg-slate-100 text-slate-700">pendiente</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('bitacoras.show', [$proyecto, $b]) }}" class="text-gpt-600 hover:underline text-sm">Abrir</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $bitacoras->links() }}</div>
    @endif
</x-layouts.app>
