<x-layouts.app :title="'KOM · ' . $proyecto->cp_numero">
    <a href="{{ route('oportunidades.show', $proyecto) }}" class="text-sm text-slate-500 hover:underline">← Volver al CP</a>

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Kick-Off Meetings</h1>
            <p class="text-slate-600 text-sm mt-1">
                <span class="font-mono">{{ $proyecto->cp_numero }}</span>
                @if ($proyecto->dn_numero) / <span class="font-mono text-emerald-700">{{ $proyecto->dn_numero }}</span> @endif
                · {{ $proyecto->cliente?->razon_social }}
            </p>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4 mb-6">
        @foreach (['kom_interno' => 'KOM Interno', 'kom_cliente' => 'KOM con Cliente'] as $tipo => $titulo)
            <form method="POST" action="{{ route('koms.store', $proyecto) }}" class="bg-white border border-slate-200 rounded-xl p-5">
                @csrf
                <input type="hidden" name="tipo" value="{{ $tipo }}" />
                <h3 class="font-semibold text-slate-900 mb-3">+ {{ $titulo }}</h3>
                <label class="block mb-2">
                    <span class="text-xs text-slate-600">Fecha y hora *</span>
                    <input name="fecha" type="datetime-local" required class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                </label>
                <label class="block mb-2">
                    <span class="text-xs text-slate-600">Agenda</span>
                    <textarea name="agenda" rows="2" placeholder="Puntos a tratar…" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm"></textarea>
                </label>
                <button class="rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm px-4 py-2">Crear KOM</button>
            </form>
        @endforeach
    </div>

    @if ($koms->isEmpty())
        <div class="bg-white border border-dashed border-slate-300 rounded-xl p-10 text-center">
            <p class="text-slate-500">Aún no se ha realizado ningún KOM para este proyecto.</p>
        </div>
    @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="text-left px-4 py-3">Tipo</th>
                        <th class="text-left px-4 py-3">Fecha</th>
                        <th class="text-left px-4 py-3">Participantes</th>
                        <th class="text-left px-4 py-3">Cronograma adjunto</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($koms as $k)
                        @php $tipoBadge = $k->tipo === 'kom_interno' ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800'; @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3"><span class="rounded text-xs px-2 py-0.5 {{ $tipoBadge }}">{{ str_replace('_', ' ', $k->tipo) }}</span></td>
                            <td class="px-4 py-3 font-mono">{{ $k->fecha?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ count($k->participantes ?? []) }}</td>
                            <td class="px-4 py-3 text-slate-600 text-xs">
                                @if ($k->cronograma_attached_id)
                                    <a href="{{ route('cronogramas.show', [$proyecto, $k->cronograma_attached_id]) }}" class="text-gpt-600 hover:underline">v{{ $k->cronograma?->version ?? '?' }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('koms.show', [$proyecto, $k]) }}" class="text-gpt-600 hover:underline text-sm">Abrir</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.app>
