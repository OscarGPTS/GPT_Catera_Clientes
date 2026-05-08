<x-layouts.app :title="'Cotizaciones · ' . ($proyecto->cp_numero ?? '')">
    <a href="{{ route('oportunidades.show', $proyecto) }}" class="text-sm text-slate-500 hover:underline">← Volver al CP</a>

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Cotizaciones</h1>
            <p class="text-slate-600 text-sm mt-1">
                <span class="font-mono">{{ $proyecto->cp_numero }}</span> · {{ $proyecto->cliente?->razon_social }} · {{ $proyecto->sublinea?->nombre }}
            </p>
        </div>
        @if (in_array($proyecto->estado, ['cotizando', 'cotizado', 'presentado']))
            <form method="POST" action="{{ route('cotizaciones.store', $proyecto) }}">
                @csrf
                <button class="rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm px-4 py-2">
                    + Nueva versión
                </button>
            </form>
        @endif
    </div>

    @if ($cotizaciones->isEmpty())
        <div class="bg-white border border-dashed border-slate-300 rounded-xl p-10 text-center">
            <p class="text-slate-500">Aún no hay cotizaciones para este CP.</p>
            @if ($proyecto->estado === 'cotizando')
                <form method="POST" action="{{ route('cotizaciones.store', $proyecto) }}" class="mt-4">
                    @csrf
                    <button class="rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm px-4 py-2">Crear primer borrador</button>
                </form>
            @endif
        </div>
    @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="text-left px-4 py-3">Versión</th>
                        <th class="text-left px-4 py-3">Status</th>
                        <th class="text-right px-4 py-3">Costo Directo</th>
                        <th class="text-right px-4 py-3">Precio Venta</th>
                        <th class="text-right px-4 py-3">Margen</th>
                        <th class="text-left px-4 py-3">Generado por</th>
                        <th class="text-left px-4 py-3">Emisión</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($cotizaciones as $c)
                        @php
                            $statusBadge = match ($c->status) {
                                'borrador' => 'bg-slate-100 text-slate-700',
                                'interno_aprobado' => 'bg-amber-100 text-amber-800',
                                'emitida' => 'bg-emerald-100 text-emerald-800',
                                'cancelada' => 'bg-rose-100 text-rose-800',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono">v{{ $c->version }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-block rounded px-2 py-0.5 text-xs {{ $statusBadge }}">
                                    {{ str_replace('_', ' ', $c->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono">${{ number_format((float) $c->costo_directo, 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono font-semibold">${{ number_format((float) $c->precio_venta_final, 2) }} {{ $c->moneda }}</td>
                            <td class="px-4 py-3 text-right font-mono">{{ number_format((float) $c->margen_neto * 100, 2) }}%</td>
                            <td class="px-4 py-3 text-slate-600">{{ $c->generadoPor?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600 text-xs">{{ optional($c->fecha_emision)->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                @if ($c->status === 'borrador')
                                    <a href="{{ route('cotizaciones.edit', [$proyecto, $c]) }}" class="text-gpt-600 hover:underline text-sm">Editar</a>
                                @else
                                    <a href="{{ route('cotizaciones.show', [$proyecto, $c]) }}" class="text-gpt-600 hover:underline text-sm">Ver</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-layouts.app>
