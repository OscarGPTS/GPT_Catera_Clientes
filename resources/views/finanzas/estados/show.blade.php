<x-layouts.app :title="'Estado ' . str_pad($estado->mes, 2, '0', STR_PAD_LEFT) . '/' . $estado->año">
    <a href="{{ route('finanzas.estados.index', $cuenta) }}" class="text-sm text-slate-500 hover:underline">← Estados</a>

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">
                Estado {{ str_pad($estado->mes, 2, '0', STR_PAD_LEFT) }}/{{ $estado->año }}
                <span class="ml-2 text-sm text-slate-500 uppercase font-normal">{{ $cuenta->banco }} · {{ $cuenta->alias }}</span>
            </h1>
            <p class="text-slate-600 text-sm mt-1">{{ $estado->total_movimientos }} movimientos · {{ $totales['no_conciliados'] }} sin conciliar</p>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
            <p class="text-xs uppercase text-emerald-700">Ingresos</p>
            <p class="text-2xl font-mono font-semibold text-emerald-700 mt-1">${{ number_format((float) $totales['ingresos'], 2) }}</p>
        </div>
        <div class="bg-rose-50 border border-rose-200 rounded-xl p-4">
            <p class="text-xs uppercase text-rose-700">Egresos</p>
            <p class="text-2xl font-mono font-semibold text-rose-700 mt-1">${{ number_format((float) $totales['egresos'], 2) }}</p>
        </div>
        <div class="bg-slate-50 border border-slate-200 rounded-xl p-4">
            <p class="text-xs uppercase text-slate-500">Neto</p>
            <p class="text-2xl font-mono font-semibold text-slate-900 mt-1">${{ number_format((float) ($totales['ingresos'] - $totales['egresos']), 2) }}</p>
        </div>
    </div>

    @if ($estado->movimientos->isEmpty())
        <div class="bg-white border border-dashed border-slate-300 rounded-xl p-10 text-center">
            <p class="text-slate-500">Sin movimientos.</p>
        </div>
    @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="text-left px-3 py-2">Fecha</th>
                        <th class="text-left px-3 py-2">Descripción</th>
                        <th class="text-right px-3 py-2">Monto</th>
                        <th class="text-left px-3 py-2">Tipo</th>
                        <th class="text-left px-3 py-2">Conciliación</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($estado->movimientos as $m)
                        <tr class="{{ $m->conciliado_at ? '' : 'bg-amber-50/40' }} hover:bg-slate-50">
                            <td class="px-3 py-2 font-mono text-xs">{{ $m->fecha->format('Y-m-d') }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $m->descripcion }}</td>
                            <td class="px-3 py-2 text-right font-mono {{ $m->tipo === 'ingreso' ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $m->tipo === 'ingreso' ? '+' : '−' }}${{ number_format((float) $m->monto, 2) }}
                            </td>
                            <td class="px-3 py-2 text-xs">{{ $m->tipo }}</td>
                            <td class="px-3 py-2 text-xs">
                                @if ($m->conciliado_at)
                                    @if ($m->proyecto)
                                        <a href="{{ route('oportunidades.show', $m->proyecto) }}" class="font-mono text-gpt-700 hover:underline">{{ $m->proyecto->cp_numero }}</a>
                                    @endif
                                    @if ($m->conciliado_con_factura)
                                        <span class="ml-1 text-slate-500">F-{{ $m->conciliado_con_factura }}</span>
                                    @endif
                                @else
                                    <span class="text-amber-700">— sin conciliar —</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">
                                <a href="{{ route('finanzas.conciliacion.show', [$cuenta, $estado, $m]) }}" class="text-xs text-gpt-600 hover:underline">{{ $m->conciliado_at ? 'Ver' : 'Conciliar' }}</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.app>
