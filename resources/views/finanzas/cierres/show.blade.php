<x-layouts.app :title="'Cierre ' . $cierre->periodoLabel()">
    <a href="{{ route('finanzas.cierres') }}" class="text-sm text-slate-500 hover:underline">← Cierres</a>

    @php
        $statusBadge = match ($cierre->status) {
            'borrador' => 'bg-slate-100 text-slate-700',
            'aprobado' => 'bg-amber-100 text-amber-800',
            'cerrado' => 'bg-emerald-100 text-emerald-800',
        };
        $tipoLabel = $cierre->tipo === 'contable_sat' ? 'Cierre Contable SAT' : 'Cierre Gerencial · Avance D1';
        $seccionLabels = [
            'sat_base' => ['titulo' => 'A · Facturado/SAT', 'desc' => 'Movimientos conciliados con factura', 'color' => 'blue'],
            'devengado' => ['titulo' => 'B · Devengado', 'desc' => 'Proyectos firmados, prorrateo días naturales (D8)', 'color' => 'violet'],
            'pipeline_ponderado' => ['titulo' => 'C · Pipeline ponderado', 'desc' => 'Cotizando/cotizado/presentado × probabilidad', 'color' => 'amber'],
        ];
    @endphp

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">
                {{ $tipoLabel }}
                <span class="ml-2 inline-block rounded text-xs px-2 py-0.5 {{ $statusBadge }}">{{ $cierre->status }}</span>
            </h1>
            <p class="text-slate-600 text-sm mt-1">
                Periodo <span class="font-mono">{{ $cierre->periodoLabel() }}</span>
                · corte {{ optional($cierre->fecha_corte)->format('Y-m-d') }}
                · generado por {{ $cierre->generadoPor?->name ?? '—' }}
                @if ($cierre->aprobadoPor)
                    · aprobado por {{ $cierre->aprobadoPor->name }}
                @endif
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('finanzas.cierres.pdf', $cierre) }}" class="rounded-md bg-rose-600 hover:bg-rose-700 text-white text-sm px-4 py-2">PDF</a>
            @if ($cierre->status === 'borrador')
                <form method="POST" action="{{ route('finanzas.cierres.regenerar', $cierre) }}">
                    @csrf
                    @method('PATCH')
                    <button class="rounded-md border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm px-3 py-2">Regenerar</button>
                </form>
                <form method="POST" action="{{ route('finanzas.cierres.aprobar', $cierre) }}">
                    @csrf
                    <button class="rounded-md bg-amber-600 hover:bg-amber-700 text-white text-sm px-4 py-2">Aprobar</button>
                </form>
            @endif
            @if ($cierre->status === 'aprobado')
                <form method="POST" action="{{ route('finanzas.cierres.cerrar', $cierre) }}"
                      onsubmit="return confirm('Cerrar es irreversible. ¿Continuar?');">
                    @csrf
                    <button class="rounded-md bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 py-2">Cerrar (irreversible)</button>
                </form>
                <form method="POST" action="{{ route('finanzas.cierres.regresar', $cierre) }}" class="flex gap-1">
                    @csrf
                    @method('PATCH')
                    <input name="razon" required minlength="5" placeholder="Razón" class="rounded-md border border-slate-200 px-2 py-2 text-xs" />
                    <button class="rounded-md border border-rose-200 hover:bg-rose-50 text-rose-700 text-sm px-3 py-2">↶ Borrador</button>
                </form>
            @endif
        </div>
    </div>

    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-5 mb-6 flex items-center justify-between">
        <p class="text-sm text-slate-600">Total general del cierre</p>
        <p class="text-3xl font-mono font-bold text-emerald-800">${{ number_format($cierre->totalGeneral(), 2) }}</p>
    </div>

    <div class="space-y-4">
        @foreach ($cierre->secciones as $sec)
            @php $info = $seccionLabels[$sec->codigo] ?? ['titulo' => $sec->codigo, 'desc' => '', 'color' => 'slate']; @endphp
            <section class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                <header class="px-5 py-3 border-b bg-slate-50 flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-slate-900">{{ $info['titulo'] }}</h2>
                        <p class="text-xs text-slate-500">{{ $info['desc'] }}</p>
                    </div>
                    <p class="font-mono font-bold text-lg text-{{ $info['color'] }}-700">${{ number_format((float) $sec->total, 2) }}</p>
                </header>
                @if ($sec->lineas->isEmpty())
                    <p class="px-5 py-6 text-sm text-slate-500 text-center">Sin líneas en esta sección.</p>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="text-left px-4 py-2">Proyecto</th>
                                <th class="text-left px-4 py-2">Cliente</th>
                                <th class="text-left px-4 py-2">Concepto</th>
                                <th class="text-right px-4 py-2">% aplicado</th>
                                <th class="text-right px-4 py-2">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($sec->lineas as $l)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-2 font-mono text-xs">
                                        @if ($l->proyecto)
                                            <a href="{{ route('oportunidades.show', $l->proyecto) }}" class="text-gpt-700 hover:underline">{{ $l->proyecto->cp_numero }}</a>
                                            @if ($l->proyecto->dn_numero) / <span class="text-emerald-700">{{ $l->proyecto->dn_numero }}</span> @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-xs text-slate-600">{{ $l->proyecto?->cliente?->razon_social ?? '—' }}</td>
                                    <td class="px-4 py-2 text-xs text-slate-500">{{ $l->observaciones }}</td>
                                    <td class="px-4 py-2 text-right font-mono text-xs">{{ number_format((float) $l->porcentaje_aplicado * 100, 2) }}%</td>
                                    <td class="px-4 py-2 text-right font-mono">${{ number_format((float) $l->monto, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>
        @endforeach
    </div>
</x-layouts.app>
