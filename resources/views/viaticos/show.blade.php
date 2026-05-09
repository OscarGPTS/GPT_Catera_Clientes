<x-layouts.app :title="'Viáticos · ' . $proyecto->cp_numero">
    <a href="{{ route('viaticos.index', $proyecto) }}" class="text-sm text-slate-500 hover:underline">← Solicitudes</a>

    @php
        $statusBadge = match ($solicitud->status) {
            'borrador' => 'bg-slate-100 text-slate-700',
            'pendiente_servgrales' => 'bg-blue-100 text-blue-800',
            'pendiente_direccion' => 'bg-amber-100 text-amber-800',
            'aprobada' => 'bg-emerald-100 text-emerald-800',
            'rechazada' => 'bg-rose-100 text-rose-800',
        };
        $estimadoTotal = $solicitud->partidas->sum('monto_estimado');
        $realTotal = $solicitud->partidas->sum('monto_real');
    @endphp

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">
                Solicitud #{{ $solicitud->id }}
                <span class="ml-2 inline-block rounded text-xs px-2 py-0.5 {{ $statusBadge }}">{{ str_replace('_', ' ', $solicitud->status) }}</span>
            </h1>
            <p class="text-slate-600 text-sm mt-1">
                <span class="font-mono">{{ $proyecto->cp_numero }}</span>
                · {{ $solicitud->periodo_inicio->format('Y-m-d') }} → {{ $solicitud->periodo_fin->format('Y-m-d') }}
                · solicitada por {{ $solicitud->solicitante?->name }}
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('viaticos.pdf', [$proyecto, $solicitud]) }}" class="rounded-md bg-rose-600 hover:bg-rose-700 text-white text-sm px-4 py-2">PDF</a>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            @if ($solicitud->justificacion)
                <section class="bg-white border border-slate-200 rounded-xl p-6">
                    <h2 class="font-semibold text-slate-900 mb-3">Justificación</h2>
                    <p class="text-sm whitespace-pre-line">{{ $solicitud->justificacion }}</p>
                </section>
            @endif

            <section class="bg-white border border-slate-200 rounded-xl p-6">
                <h2 class="font-semibold text-slate-900 mb-3">Personal beneficiario</h2>
                <table class="w-full text-sm">
                    <thead class="text-xs uppercase text-slate-500 border-b">
                        <tr><th class="text-left py-2">Nombre</th><th class="text-right py-2">Días</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($solicitud->personal as $p)
                            <tr><td class="py-2">{{ $p->user?->name ?? '—' }}</td><td class="py-2 text-right font-mono">{{ $p->dias }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </section>

            <section class="bg-white border border-slate-200 rounded-xl p-6">
                <h2 class="font-semibold text-slate-900 mb-3">Partidas</h2>
                @if ($solicitud->status === 'aprobada')
                    <form method="POST" action="{{ route('viaticos.reales', [$proyecto, $solicitud]) }}">
                        @csrf
                        @method('PATCH')
                        <table class="w-full text-sm">
                            <thead class="text-xs uppercase text-slate-500 border-b">
                                <tr>
                                    <th class="text-left py-2">Concepto</th>
                                    <th class="text-right py-2">Estimado</th>
                                    <th class="text-right py-2">Real</th>
                                    <th class="text-left py-2">Observaciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($solicitud->partidas as $part)
                                    <tr>
                                        <td class="py-2 capitalize">{{ $part->concepto }}</td>
                                        <td class="py-2 text-right font-mono">${{ number_format((float) $part->monto_estimado, 2) }}</td>
                                        <td class="py-2 text-right">
                                            <input name="partidas[{{ $part->id }}]" type="number" step="0.01" min="0" value="{{ $part->monto_real }}" class="w-32 rounded border border-slate-200 px-2 py-1 text-sm font-mono text-right" />
                                        </td>
                                        <td class="py-2 text-xs text-slate-600">{{ $part->observaciones }}</td>
                                    </tr>
                                @endforeach
                                <tr class="bg-slate-50 font-semibold">
                                    <td class="py-2">Total</td>
                                    <td class="py-2 text-right font-mono">${{ number_format((float) $estimadoTotal, 2) }}</td>
                                    <td class="py-2 text-right font-mono">${{ number_format((float) $realTotal, 2) }}</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                        <button class="mt-3 rounded-md bg-slate-900 hover:bg-slate-800 text-white text-sm px-4 py-2">Guardar montos reales</button>
                    </form>
                @else
                    <table class="w-full text-sm">
                        <thead class="text-xs uppercase text-slate-500 border-b">
                            <tr>
                                <th class="text-left py-2">Concepto</th>
                                <th class="text-right py-2">Estimado</th>
                                <th class="text-left py-2">Observaciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($solicitud->partidas as $part)
                                <tr>
                                    <td class="py-2 capitalize">{{ $part->concepto }}</td>
                                    <td class="py-2 text-right font-mono">${{ number_format((float) $part->monto_estimado, 2) }}</td>
                                    <td class="py-2 text-xs text-slate-600">{{ $part->observaciones }}</td>
                                </tr>
                            @endforeach
                            <tr class="bg-slate-50 font-semibold">
                                <td class="py-2">Total</td>
                                <td class="py-2 text-right font-mono">${{ number_format((float) $estimadoTotal, 2) }}</td>
                                <td></td>
                            </tr>
                        </tbody>
                    </table>
                @endif
            </section>
        </div>

        <aside class="space-y-4">
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <h3 class="text-xs uppercase font-semibold text-slate-500 mb-3">Aprobaciones</h3>
                <dl class="text-sm space-y-2">
                    <div><dt class="text-slate-500 text-xs">Servicios Generales</dt><dd>{{ $solicitud->aprobadorServGrales?->name ?? '— pendiente —' }}</dd></div>
                    <div><dt class="text-slate-500 text-xs">Dirección</dt><dd>{{ $solicitud->aprobadorDireccion?->name ?? '— pendiente —' }}</dd></div>
                    <div><dt class="text-slate-500 text-xs">Aprobado el</dt><dd>{{ optional($solicitud->aprobado_at)->format('Y-m-d H:i') ?? '—' }}</dd></div>
                </dl>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-2">
                @if ($solicitud->status === 'borrador')
                    <form method="POST" action="{{ route('viaticos.emitir', [$proyecto, $solicitud]) }}">
                        @csrf
                        <button class="w-full rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm px-4 py-2">Emitir a Serv. Generales</button>
                    </form>
                @endif

                @if ($solicitud->status === 'pendiente_servgrales' && auth()->user()->hasAnyRole(['serv_generales', 'super_admin']))
                    <form method="POST" action="{{ route('viaticos.aprobar.servgrales', [$proyecto, $solicitud]) }}">
                        @csrf
                        <button class="w-full rounded-md bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 py-2">Aprobar (Serv. Generales)</button>
                    </form>
                @endif

                @if ($solicitud->status === 'pendiente_direccion' && auth()->user()->hasAnyRole(['direccion_general', 'super_admin']))
                    <form method="POST" action="{{ route('viaticos.aprobar.direccion', [$proyecto, $solicitud]) }}">
                        @csrf
                        <button class="w-full rounded-md bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 py-2">Aprobar (Dirección)</button>
                    </form>
                @endif

                @if (in_array($solicitud->status, ['pendiente_servgrales', 'pendiente_direccion']))
                    <form method="POST" action="{{ route('viaticos.rechazar', [$proyecto, $solicitud]) }}" class="space-y-2">
                        @csrf
                        <input name="razon" required minlength="5" placeholder="Razón del rechazo" class="w-full rounded-md border border-slate-200 px-3 py-2 text-xs" />
                        <button class="w-full rounded-md border border-rose-200 hover:bg-rose-50 text-rose-700 text-sm px-4 py-2">Rechazar</button>
                    </form>
                @endif
            </div>
        </aside>
    </div>
</x-layouts.app>
