<x-layouts.app :title="'Cronograma · ' . $proyecto->cp_numero">
    <a href="{{ route('oportunidades.show', $proyecto) }}" class="text-sm text-slate-500 hover:underline">← Volver al CP</a>

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Cronograma</h1>
            <p class="text-slate-600 text-sm mt-1">
                <span class="font-mono">{{ $proyecto->cp_numero }}</span>
                @if ($proyecto->dn_numero) / <span class="font-mono text-emerald-700">{{ $proyecto->dn_numero }}</span> @endif
                · {{ $proyecto->cliente?->razon_social }}
            </p>
        </div>
        <form method="POST" action="{{ route('cronogramas.store', $proyecto) }}">
            @csrf
            <button class="rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm px-4 py-2">+ Nueva versión</button>
        </form>
    </div>

    <details class="bg-white border border-slate-200 rounded-xl p-5 mb-6">
        <summary class="cursor-pointer text-sm font-semibold text-gpt-600">📥 Importar desde MS Project (.xml o .csv)</summary>
        <form method="POST" action="{{ route('cronogramas.importar', $proyecto) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
            @csrf
            <input name="archivo" type="file" accept=".xml,.csv,.txt" required class="block w-full text-sm" />
            <p class="text-xs text-slate-500">
                MSP → File → Save As → <strong>XML</strong> (recomendado, conserva predecesoras y outline level), o exportar la vista de tareas como <strong>CSV</strong>.
                <span class="text-amber-700">.mpp binario no se soporta nativamente.</span>
            </p>
            <button class="rounded-md bg-slate-900 hover:bg-slate-800 text-white text-sm px-4 py-2">Importar archivo</button>
        </form>
        <p class="text-[10px] text-slate-400 mt-2">El import crea una nueva versión y reemplaza las actividades. La versión anterior queda intacta.</p>
    </details>

    @if ($cronogramas->isEmpty())
        <div class="bg-white border border-dashed border-slate-300 rounded-xl p-10 text-center">
            <p class="text-slate-500">Aún no hay cronogramas. Crea la primera versión para empezar.</p>
        </div>
    @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="text-left px-4 py-3">Versión</th>
                        <th class="text-left px-4 py-3">Inicio</th>
                        <th class="text-left px-4 py-3">Fin</th>
                        <th class="text-left px-4 py-3">Generado por</th>
                        <th class="text-left px-4 py-3">Creada</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($cronogramas as $c)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono">v{{ $c->version }}</td>
                            <td class="px-4 py-3">{{ optional($c->fecha_inicio)->format('Y-m-d') ?? '—' }}</td>
                            <td class="px-4 py-3">{{ optional($c->fecha_fin)->format('Y-m-d') ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $c->generadoPor?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600 text-xs">{{ $c->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('cronogramas.show', [$proyecto, $c]) }}" class="text-gpt-600 hover:underline text-sm">Abrir</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.app>
