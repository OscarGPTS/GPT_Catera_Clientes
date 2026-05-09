<x-layouts.app :title="'Reportes Semanales · ' . $proyecto->cp_numero">
    <a href="{{ route('oportunidades.show', $proyecto) }}" class="text-sm text-slate-500 hover:underline">← Volver al CP</a>

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Reportes semanales</h1>
            <p class="text-slate-600 text-sm mt-1">
                <span class="font-mono">{{ $proyecto->cp_numero }}</span> · {{ $proyecto->cliente?->razon_social }}
            </p>
        </div>
        <form method="POST" action="{{ route('reportes.store', $proyecto) }}" class="flex gap-2 items-center">
            @csrf
            <input name="semana_inicio" type="date" required value="{{ $semanaActual }}" class="rounded-md border border-slate-200 px-3 py-2 text-sm" />
            <button class="rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm px-4 py-2">Generar reporte</button>
        </form>
    </div>

    @if ($reportes->isEmpty())
        <div class="bg-white border border-dashed border-slate-300 rounded-xl p-10 text-center">
            <p class="text-slate-500">No se ha generado ningún reporte aún.</p>
        </div>
    @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="text-left px-4 py-3">Semana</th>
                        <th class="text-left px-4 py-3">Generado</th>
                        <th class="text-left px-4 py-3">Enviado</th>
                        <th class="text-left px-4 py-3">Destinatarios</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($reportes as $r)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono">{{ $r->semana_inicio->format('Y-m-d') }} → {{ $r->semana_fin->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 text-xs text-slate-600">{{ optional($r->generado_at)->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-xs">
                                @if ($r->enviado_at)
                                    <span class="rounded bg-emerald-100 text-emerald-800 px-2 py-0.5">{{ $r->enviado_at->format('Y-m-d H:i') }}</span>
                                @else
                                    <span class="rounded bg-slate-100 text-slate-700 px-2 py-0.5">no enviado</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ count($r->recipients ?? []) }} dest.</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('reportes.show', [$proyecto, $r]) }}" class="text-gpt-600 hover:underline text-sm">Abrir</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.app>
