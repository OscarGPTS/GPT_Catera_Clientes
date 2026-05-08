<x-layouts.app :title="'KOM · ' . $proyecto->cp_numero">
    <a href="{{ route('koms.index', $proyecto) }}" class="text-sm text-slate-500 hover:underline">← KOMs</a>

    @php $tipoBadge = $kom->tipo === 'kom_interno' ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800'; @endphp

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">
                {{ $kom->tipo === 'kom_interno' ? 'KOM Interno' : 'KOM con Cliente' }}
                <span class="ml-2 inline-block rounded text-xs px-2 py-0.5 {{ $tipoBadge }}">{{ str_replace('_', ' ', $kom->tipo) }}</span>
            </h1>
            <p class="text-slate-600 text-sm mt-1">
                <span class="font-mono">{{ $proyecto->cp_numero }}</span>
                @if ($proyecto->dn_numero) / <span class="font-mono text-emerald-700">{{ $proyecto->dn_numero }}</span> @endif
                · {{ $proyecto->cliente?->razon_social }} · {{ $proyecto->sublinea?->nombre }}
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('koms.pdf', [$proyecto, $kom]) }}" class="rounded-md bg-rose-600 hover:bg-rose-700 text-white text-sm px-4 py-2">Descargar PDF</a>
            <form method="POST" action="{{ route('koms.destroy', [$proyecto, $kom]) }}" onsubmit="return confirm('¿Eliminar este KOM?');">
                @csrf
                @method('DELETE')
                <button class="rounded-md border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm px-4 py-2">Eliminar</button>
            </form>
        </div>
    </div>

    <form method="POST" action="{{ route('koms.update', [$proyecto, $kom]) }}" class="grid lg:grid-cols-3 gap-6">
        @csrf
        @method('PATCH')

        <div class="lg:col-span-2 space-y-6">
            <section class="bg-white border border-slate-200 rounded-xl p-6">
                <h2 class="font-semibold text-slate-900 mb-3">Información</h2>
                <div class="grid md:grid-cols-2 gap-3">
                    <label class="block">
                        <span class="text-xs text-slate-600">Fecha y hora *</span>
                        <input name="fecha" type="datetime-local" required
                               value="{{ $kom->fecha?->format('Y-m-d\TH:i') }}"
                               class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                    </label>
                    <label class="block">
                        <span class="text-xs text-slate-600">Cronograma adjunto</span>
                        <select name="cronograma_attached_id" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                            <option value="">— ninguno —</option>
                            @foreach ($proyecto->cronogramas as $c)
                                <option value="{{ $c->id }}" @selected($kom->cronograma_attached_id === $c->id)>v{{ $c->version }} ({{ optional($c->fecha_inicio)->format('Y-m-d') }} → {{ optional($c->fecha_fin)->format('Y-m-d') }})</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </section>

            <section class="bg-white border border-slate-200 rounded-xl p-6">
                <h2 class="font-semibold text-slate-900 mb-3">Agenda</h2>
                <textarea name="agenda" rows="6" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">{{ $kom->agenda }}</textarea>
            </section>

            <section class="bg-white border border-slate-200 rounded-xl p-6">
                <h2 class="font-semibold text-slate-900 mb-3">Minuta de la reunión</h2>
                <textarea name="minuta" rows="10" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono"
                          placeholder="Acuerdos, riesgos identificados, asignaciones, fechas compromiso…">{{ $kom->minuta }}</textarea>
            </section>

            <section class="bg-white border border-slate-200 rounded-xl p-6"
                     x-data="{ items: {{ Js::from(array_values((array) ($kom->participantes ?? []))) }} }">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-slate-900">Participantes</h2>
                    <button type="button" @click="items.push({nombre: '', rol: '', empresa: ''})" class="text-sm text-gpt-600 hover:underline">+ Agregar</button>
                </div>
                <div class="space-y-2">
                    <template x-for="(p, idx) in items" :key="idx">
                        <div class="grid grid-cols-12 gap-2 items-center">
                            <input :name="`participantes[${idx}][nombre]`" x-model="items[idx].nombre" placeholder="Nombre"
                                   class="col-span-5 rounded-md border border-slate-200 px-3 py-2 text-sm" />
                            <input :name="`participantes[${idx}][rol]`" x-model="items[idx].rol" placeholder="Rol"
                                   class="col-span-3 rounded-md border border-slate-200 px-3 py-2 text-sm" />
                            <input :name="`participantes[${idx}][empresa]`" x-model="items[idx].empresa" placeholder="Empresa"
                                   class="col-span-3 rounded-md border border-slate-200 px-3 py-2 text-sm" />
                            <button type="button" @click="items.splice(idx, 1)" class="col-span-1 text-rose-600 hover:underline text-xs">−</button>
                        </div>
                    </template>
                    <p x-show="items.length === 0" class="text-sm text-slate-500">Sin participantes registrados.</p>
                </div>
            </section>

            <button class="rounded-md bg-slate-900 hover:bg-slate-800 text-white text-sm px-4 py-2">Guardar cambios</button>
        </div>

        <aside class="space-y-4">
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <h3 class="text-xs uppercase font-semibold text-slate-500 mb-3">Cronograma</h3>
                @if ($kom->cronograma)
                    <p class="text-sm">v{{ $kom->cronograma->version }} ({{ $kom->cronograma->actividades->count() }} actividades)</p>
                    <a href="{{ route('cronogramas.show', [$proyecto, $kom->cronograma]) }}" class="text-xs text-gpt-600 hover:underline">Abrir cronograma →</a>
                @else
                    <p class="text-sm text-slate-500">Ningún cronograma adjunto.</p>
                    <a href="{{ route('cronogramas.index', $proyecto) }}" class="text-xs text-gpt-600 hover:underline">Gestionar cronogramas →</a>
                @endif
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-5 text-xs text-slate-500">
                Tip: el KOM con cliente normalmente ratifica un cronograma. Adjúntalo aquí para que aparezca en el PDF.
            </div>
        </aside>
    </form>
</x-layouts.app>
