<x-layouts.app :title="'Cotización v' . $cotizacion->version">
    <a href="{{ route('cotizaciones.index', $proyecto) }}" class="text-sm text-slate-500 hover:underline">← Versiones</a>

    @php
        $statusBadge = match ($cotizacion->status) {
            'borrador' => 'bg-slate-100 text-slate-700',
            'interno_aprobado' => 'bg-amber-100 text-amber-800',
            'emitida' => 'bg-emerald-100 text-emerald-800',
            'cancelada' => 'bg-rose-100 text-rose-800',
            default => 'bg-slate-100 text-slate-700',
        };
    @endphp

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">
                Cotización <span class="font-mono">v{{ $cotizacion->version }}</span>
                <span class="ml-2 inline-block rounded text-xs px-2 py-0.5 {{ $statusBadge }}">{{ str_replace('_', ' ', $cotizacion->status) }}</span>
            </h1>
            <p class="text-slate-600 text-sm mt-1">
                <span class="font-mono">{{ $proyecto->cp_numero }}</span> · {{ $proyecto->cliente?->razon_social }} · {{ $proyecto->sublinea?->nombre }}
            </p>
            @if ($cotizacion->fecha_emision)
                <p class="text-xs text-slate-500 mt-1">Emitida {{ $cotizacion->fecha_emision->format('Y-m-d H:i') }} por {{ $cotizacion->generadoPor?->name ?? 'sistema' }}</p>
            @endif
        </div>
        <div class="flex gap-2">
            @if (in_array($cotizacion->status, ['emitida', 'interno_aprobado']))
                <a href="{{ route('cotizaciones.pdf', [$proyecto, $cotizacion]) }}"
                   class="rounded-md bg-rose-600 hover:bg-rose-700 text-white text-sm px-4 py-2">
                    Descargar PDF
                </a>
            @endif
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <section class="bg-white border border-slate-200 rounded-xl p-6">
                <h2 class="font-semibold text-slate-900 mb-3">Partidas</h2>
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500 border-b">
                        <tr>
                            <th class="text-left py-2">#</th>
                            <th class="text-left py-2">Descripción</th>
                            <th class="text-right py-2">Cantidad</th>
                            <th class="text-left py-2">Unidad</th>
                            <th class="text-right py-2">$ Unit</th>
                            <th class="text-right py-2">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($cotizacion->partidas as $p)
                            <tr>
                                <td class="py-2 font-mono text-slate-500">{{ $p->numero_partida }}</td>
                                <td class="py-2 text-slate-700">{{ $p->descripcion }}</td>
                                <td class="py-2 text-right font-mono">{{ rtrim(rtrim(number_format((float) $p->cantidad, 4), '0'), '.') }}</td>
                                <td class="py-2 text-slate-600 text-xs">{{ $p->unidad ?? '—' }}</td>
                                <td class="py-2 text-right font-mono">${{ number_format((float) $p->costo_unitario, 4) }}</td>
                                <td class="py-2 text-right font-mono font-semibold">${{ number_format((float) $p->costo_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            @if ($cotizacion->observaciones)
                <section class="bg-white border border-slate-200 rounded-xl p-6">
                    <h2 class="font-semibold text-slate-900 mb-3">Observaciones</h2>
                    <p class="text-sm text-slate-700 whitespace-pre-line">{{ $cotizacion->observaciones }}</p>
                </section>
            @endif
        </div>

        <aside class="space-y-4">
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <h3 class="text-xs uppercase font-semibold text-slate-500 mb-3">COSS</h3>
                <dl class="text-sm space-y-2 font-mono">
                    <div class="flex justify-between"><dt class="text-slate-500">Costo directo</dt><dd>${{ number_format((float) $cotizacion->costo_directo, 2) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Factor indirectos</dt><dd>{{ number_format((float) $cotizacion->factor_indirectos * 100, 2) }}%</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Factor admin</dt><dd>{{ number_format((float) $cotizacion->factor_admin * 100, 2) }}%</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Factor utilidad</dt><dd>{{ number_format((float) $cotizacion->factor_utilidad * 100, 2) }}%</dd></div>
                    <div class="flex justify-between border-t pt-2"><dt class="text-slate-500">Precio calculado</dt><dd>${{ number_format((float) $cotizacion->precio_venta_calculado, 2) }}</dd></div>
                    <div class="flex justify-between text-base">
                        <dt class="text-slate-900 font-semibold">Precio de venta</dt>
                        <dd class="text-emerald-700 font-semibold">${{ number_format((float) $cotizacion->precio_venta_final, 2) }} {{ $cotizacion->moneda }}</dd>
                    </div>
                    <div class="flex justify-between"><dt class="text-slate-500">Margen neto</dt><dd>{{ number_format((float) $cotizacion->margen_neto * 100, 2) }}%</dd></div>
                </dl>
            </div>
        </aside>
    </div>
</x-layouts.app>
