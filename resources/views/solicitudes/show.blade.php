<x-layouts.app :title="'Solicitud · ' . $proyecto->cp_numero">
    <a href="{{ route('solicitudes.index', $proyecto) }}" class="text-sm text-slate-500 hover:underline">← Solicitudes</a>

    @php
        $estadoBadge = match ($solicitud->estado) {
            'borrador' => 'bg-slate-100 text-slate-700',
            'emitida' => 'bg-blue-100 text-blue-800',
            'en_proceso' => 'bg-amber-100 text-amber-800',
            'respondida' => 'bg-emerald-100 text-emerald-800',
            'cancelada' => 'bg-rose-100 text-rose-800',
        };
    @endphp

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">
                {{ $solicitud->tipo === 'requisicion_compras' ? 'Requisición a Compras' : 'OT a Ingeniería' }}
                <span class="ml-2 inline-block rounded text-xs px-2 py-0.5 {{ $estadoBadge }}">{{ $solicitud->estado }}</span>
            </h1>
            <p class="text-slate-600 text-sm mt-1">
                <span class="font-mono">{{ $proyecto->cp_numero }}</span> · {{ $proyecto->cliente?->razon_social }}
                · Solicitada por {{ $solicitud->solicitante?->name }} el {{ optional($solicitud->fecha_solicitud)->format('Y-m-d') }}
            </p>
        </div>
        <div class="flex gap-2">
            @if ($solicitud->estado === 'borrador')
                <form method="POST" action="{{ route('solicitudes.emitir', [$proyecto, $solicitud]) }}">
                    @csrf
                    <button class="rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm px-4 py-2">Emitir</button>
                </form>
            @endif
            @if ($solicitud->estado === 'emitida')
                <form method="POST" action="{{ route('solicitudes.tomar', [$proyecto, $solicitud]) }}">
                    @csrf
                    <button class="rounded-md bg-amber-600 hover:bg-amber-700 text-white text-sm px-4 py-2">Tomar</button>
                </form>
            @endif
            @if (! in_array($solicitud->estado, ['respondida', 'cancelada']))
                <form method="POST" action="{{ route('solicitudes.cancelar', [$proyecto, $solicitud]) }}"
                      onsubmit="return confirm('¿Cancelar solicitud?');">
                    @csrf
                    <input type="hidden" name="razon" value="cancelada por el solicitante" />
                    <button class="rounded-md border border-rose-200 hover:bg-rose-50 text-rose-700 text-sm px-4 py-2">Cancelar</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            @if ($solicitud->descripcion)
                <section class="bg-white border border-slate-200 rounded-xl p-6">
                    <h2 class="font-semibold text-slate-900 mb-3">Descripción</h2>
                    <p class="text-sm whitespace-pre-line">{{ $solicitud->descripcion }}</p>
                </section>
            @endif

            <section class="bg-white border border-slate-200 rounded-xl p-6">
                <h2 class="font-semibold text-slate-900 mb-3">Items</h2>
                @if ($solicitud->items->isEmpty())
                    <p class="text-sm text-slate-500">Sin items.</p>
                @else
                    <table class="w-full text-sm">
                        <thead class="text-xs uppercase text-slate-500 border-b">
                            <tr>
                                <th class="text-left py-2">Descripción</th>
                                <th class="text-right py-2">Cantidad</th>
                                <th class="text-left py-2">Unidad</th>
                                <th class="text-left py-2">Especificación</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($solicitud->items as $it)
                                <tr>
                                    <td class="py-2">{{ $it->descripcion }}</td>
                                    <td class="py-2 text-right font-mono text-xs">{{ rtrim(rtrim(number_format((float) $it->cantidad, 4), '0'), '.') }}</td>
                                    <td class="py-2 text-xs text-slate-500">{{ $it->unidad ?? '—' }}</td>
                                    <td class="py-2 text-xs text-slate-600">{{ $it->especificacion ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            @if (in_array($solicitud->estado, ['emitida', 'en_proceso']))
                <section class="bg-white border border-amber-200 rounded-xl p-6">
                    <h2 class="font-semibold text-slate-900 mb-3">Responder</h2>
                    <form method="POST" action="{{ route('solicitudes.responder', [$proyecto, $solicitud]) }}" class="space-y-3">
                        @csrf
                        <textarea name="respuesta" rows="4" required minlength="5" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"
                                  placeholder="Escribe la respuesta de Compras / Ingeniería…"></textarea>
                        <button class="rounded-md bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 py-2">Marcar como respondida</button>
                    </form>
                </section>
            @endif

            @if ($solicitud->respuesta)
                <section class="bg-white border border-emerald-200 rounded-xl p-6">
                    <h2 class="font-semibold text-slate-900 mb-3">Respuesta · {{ optional($solicitud->fecha_respuesta_real)->format('Y-m-d') }}</h2>
                    <p class="text-sm whitespace-pre-line">{{ $solicitud->respuesta }}</p>
                </section>
            @endif
        </div>

        <aside class="space-y-4">
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <h3 class="text-xs uppercase font-semibold text-slate-500 mb-3">Detalles</h3>
                <dl class="text-sm space-y-2">
                    <div><dt class="text-slate-500 text-xs">Tipo</dt><dd>{{ str_replace('_', ' ', $solicitud->tipo) }}</dd></div>
                    <div><dt class="text-slate-500 text-xs">Solicitante</dt><dd>{{ $solicitud->solicitante?->name }}</dd></div>
                    <div><dt class="text-slate-500 text-xs">Asignado</dt><dd>{{ $solicitud->asignado?->name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500 text-xs">Fecha solic.</dt><dd>{{ optional($solicitud->fecha_solicitud)->format('Y-m-d') }}</dd></div>
                    <div><dt class="text-slate-500 text-xs">Fecha req. respuesta</dt><dd>{{ optional($solicitud->fecha_respuesta_requerida)->format('Y-m-d') ?? '—' }}</dd></div>
                </dl>
            </div>
        </aside>
    </div>
</x-layouts.app>
