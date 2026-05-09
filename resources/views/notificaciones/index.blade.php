<x-layouts.app title="Notificaciones">
    <div class="flex items-start justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Notificaciones</h1>
            <p class="text-slate-600 text-sm mt-1">{{ $notificaciones->total() }} notificaciones registradas.</p>
        </div>
        @if (auth()->user()->unreadNotifications->isNotEmpty())
            <form method="POST" action="{{ route('notificaciones.marcar-todo') }}">
                @csrf
                <button class="rounded-md border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm px-3 py-1.5">Marcar todo leído</button>
            </form>
        @endif
    </div>

    @php
        $tipoIcons = [
            'cp_aprobado' => '✅',
            'cp_asignado' => '📋',
            'cotizacion_emitida' => '💰',
            'oc_firmada' => '✍',
            'minuta_firmada' => '📝',
            'viaticos_aprobados' => '💳',
            'reporte_semanal_generado' => '📊',
            'post_mortem_requerido' => '⚠',
        ];
    @endphp

    @if ($notificaciones->isEmpty())
        <div class="bg-white border border-dashed border-slate-300 rounded-xl p-10 text-center">
            <p class="text-slate-500">Sin notificaciones todavía.</p>
        </div>
    @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden divide-y divide-slate-100">
            @foreach ($notificaciones as $n)
                @php
                    $data = $n->data ?? [];
                    $tipo = $data['tipo'] ?? 'sistema';
                    $icon = $tipoIcons[$tipo] ?? '🔔';
                @endphp
                <div class="px-5 py-3 flex items-start gap-3 {{ $n->read_at ? '' : 'bg-amber-50/40' }}">
                    <span class="text-2xl">{{ $icon }}</span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-900">
                                <a href="{{ $data['url'] ?? '#' }}" class="hover:underline">{{ $data['titulo'] ?? '—' }}</a>
                            </p>
                            <span class="text-xs text-slate-400">{{ $n->created_at?->diffForHumans() }}</span>
                        </div>
                        @if (! empty($data['mensaje']))
                            <p class="text-xs text-slate-700 mt-1">{{ $data['mensaje'] }}</p>
                        @endif
                        <p class="text-xs text-slate-400 mt-1 font-mono">{{ str_replace('_', ' ', $tipo) }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        @if (! $n->read_at)
                            <form method="POST" action="{{ route('notificaciones.marcar', $n->id) }}">
                                @csrf
                                @method('PATCH')
                                <button class="text-xs text-gpt-600 hover:underline">Marcar leída</button>
                            </form>
                        @else
                            <span class="text-xs text-slate-400">leída {{ $n->read_at->diffForHumans() }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <div class="mt-4">{{ $notificaciones->links() }}</div>
    @endif
</x-layouts.app>
