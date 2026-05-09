<x-layouts.app title="Cierres mensuales">
    <div class="flex items-start justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Cierres mensuales</h1>
            <p class="text-slate-600 text-sm mt-1">SAT (contable) y Gerencial Avance (D1: SAT + devengado + pipeline ponderado).</p>
        </div>
    </div>

    <details open class="bg-white border border-slate-200 rounded-xl p-5 mb-6">
        <summary class="cursor-pointer text-sm font-semibold text-gpt-600">+ Generar cierre</summary>
        <form method="POST" action="{{ route('finanzas.cierres.store') }}" class="grid md:grid-cols-4 gap-3 mt-4">
            @csrf
            <label class="block">
                <span class="text-xs text-slate-600">Tipo *</span>
                <select name="tipo" required class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    <option value="gerencial_avance">Gerencial Avance (D1)</option>
                    <option value="contable_sat">Contable SAT</option>
                </select>
            </label>
            <label class="block">
                <span class="text-xs text-slate-600">Mes *</span>
                <select name="mes" required class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}" @selected($m === $mesActual)>{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="text-xs text-slate-600">Año *</span>
                <input name="año" type="number" min="2020" max="2050" value="{{ $añoActual }}" required class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
            </label>
            <div class="flex items-end">
                <button class="rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm px-4 py-2 w-full">Generar</button>
            </div>
        </form>
        <p class="text-[10px] text-slate-400 mt-2">Si ya existe el cierre del periodo, se actualiza el contenido manteniendo el ID.</p>
    </details>

    @if ($cierres->isEmpty())
        <div class="bg-white border border-dashed border-slate-300 rounded-xl p-10 text-center">
            <p class="text-slate-500">No hay cierres generados todavía.</p>
        </div>
    @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="text-left px-4 py-3">Periodo</th>
                        <th class="text-left px-4 py-3">Tipo</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-right px-4 py-3">Total</th>
                        <th class="text-left px-4 py-3">Generado por</th>
                        <th class="text-left px-4 py-3">Aprobado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($cierres as $c)
                        @php
                            $statusBadge = match ($c->status) {
                                'borrador' => 'bg-slate-100 text-slate-700',
                                'aprobado' => 'bg-amber-100 text-amber-800',
                                'cerrado' => 'bg-emerald-100 text-emerald-800',
                            };
                            $tipoBadge = $c->tipo === 'contable_sat' ? 'bg-blue-100 text-blue-800' : 'bg-violet-100 text-violet-800';
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono">{{ $c->periodoLabel() }}</td>
                            <td class="px-4 py-3"><span class="rounded text-xs px-2 py-0.5 {{ $tipoBadge }}">{{ str_replace('_', ' ', $c->tipo) }}</span></td>
                            <td class="px-4 py-3"><span class="rounded text-xs px-2 py-0.5 {{ $statusBadge }}">{{ $c->status }}</span></td>
                            <td class="px-4 py-3 text-right font-mono">${{ number_format($c->totalGeneral(), 2) }}</td>
                            <td class="px-4 py-3 text-slate-600 text-xs">{{ $c->generadoPor?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600 text-xs">{{ $c->aprobadoPor?->name ? $c->aprobadoPor->name . ' · ' . optional($c->aprobado_at)->format('Y-m-d') : '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('finanzas.cierres.show', $c) }}" class="text-gpt-600 hover:underline text-sm">Abrir</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $cierres->links() }}</div>
    @endif
</x-layouts.app>
