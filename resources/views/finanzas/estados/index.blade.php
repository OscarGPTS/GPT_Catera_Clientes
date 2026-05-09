<x-layouts.app :title="'Estados de cuenta · ' . strtoupper($cuenta->banco)">
    <a href="{{ route('finanzas.cuentas') }}" class="text-sm text-slate-500 hover:underline">← Cuentas</a>

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 uppercase">{{ $cuenta->banco }} · {{ $cuenta->alias }}</h1>
            <p class="text-slate-600 text-sm mt-1 font-mono">{{ $cuenta->numero_cuenta_enmascarado }} · {{ $cuenta->moneda }}</p>
        </div>
    </div>

    <details open class="bg-white border border-slate-200 rounded-xl p-5 mb-6">
        <summary class="cursor-pointer text-sm font-semibold text-gpt-600">+ Importar estado de cuenta</summary>
        <form method="POST" action="{{ route('finanzas.estados.store', $cuenta) }}" enctype="multipart/form-data" class="grid md:grid-cols-4 gap-3 mt-4">
            @csrf
            <label class="block">
                <span class="text-xs text-slate-600">Mes *</span>
                <select name="mes" required class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}">{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="text-xs text-slate-600">Año *</span>
                <input name="año" type="number" min="2020" max="2050" value="{{ now()->year }}" required class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
            </label>
            <label class="block md:col-span-2">
                <span class="text-xs text-slate-600">Archivo CSV/TXT *</span>
                <input name="archivo" type="file" accept=".csv,.txt" required class="mt-1 w-full text-sm" />
                <span class="text-[10px] text-slate-400">Parser: {{ $cuenta->banco }} (CSV o TXT exportado del banco).</span>
            </label>
            <div class="md:col-span-4">
                <button class="rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm px-4 py-2">Importar</button>
            </div>
        </form>
    </details>

    @if ($estados->isEmpty())
        <div class="bg-white border border-dashed border-slate-300 rounded-xl p-10 text-center">
            <p class="text-slate-500">Aún no hay estados de cuenta importados.</p>
        </div>
    @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="text-left px-4 py-3">Periodo</th>
                        <th class="text-right px-4 py-3">Movimientos</th>
                        <th class="text-left px-4 py-3">Importado</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($estados as $e)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono">{{ str_pad($e->mes, 2, '0', STR_PAD_LEFT) }}/{{ $e->año }}</td>
                            <td class="px-4 py-3 text-right font-mono">{{ $e->total_movimientos }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ optional($e->parseado_at)->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('finanzas.estados.show', [$cuenta, $e]) }}" class="text-gpt-600 hover:underline text-sm">Abrir</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.app>
