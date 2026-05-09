<div class="relative" wire:poll.30s>
    <button type="button" wire:click="toggle" class="relative p-2 hover:bg-slate-100 rounded-lg">
        <svg class="h-5 w-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        @if ($this->totalNoLeidas > 0)
            <span class="absolute top-1 right-1 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white bg-rose-600 rounded-full">{{ $this->totalNoLeidas }}</span>
        @endif
    </button>

    @if ($abierto)
        <div class="absolute right-0 mt-2 w-96 bg-white border border-slate-200 rounded-xl shadow-lg z-50 overflow-hidden">
            <div class="px-4 py-3 border-b bg-slate-50 flex items-center justify-between">
                <h3 class="font-semibold text-sm text-slate-900">Notificaciones</h3>
                @if ($this->totalNoLeidas > 0)
                    <button wire:click="marcarTodoLeido" class="text-xs text-gpt-600 hover:underline">Marcar todo leído</button>
                @endif
            </div>
            <div class="max-h-96 overflow-y-auto divide-y divide-slate-100">
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

                @foreach ($this->notificaciones as $n)
                    @php
                        $data = $n->data ?? [];
                        $tipo = $data['tipo'] ?? 'sistema';
                        $icon = $tipoIcons[$tipo] ?? '🔔';
                    @endphp
                    <a href="{{ $data['url'] ?? '#' }}"
                       wire:click="marcarLeida('{{ $n->id }}')"
                       class="block px-4 py-3 hover:bg-slate-50">
                        <p class="text-xs text-slate-500">
                            {{ $icon }} <span class="font-mono">{{ str_replace('_', ' ', $tipo) }}</span>
                        </p>
                        <p class="text-sm font-semibold text-slate-900 mt-1">{{ $data['titulo'] ?? '—' }}</p>
                        @if (! empty($data['mensaje']))
                            <p class="text-xs text-slate-700 line-clamp-2 mt-0.5">{{ $data['mensaje'] }}</p>
                        @endif
                        <p class="text-xs text-slate-400 mt-1">{{ $n->created_at?->diffForHumans() }}</p>
                    </a>
                @endforeach

                @foreach ($this->menciones as $m)
                    <a href="{{ route('chat.show', $m->mensaje?->canal_id) }}" class="block px-4 py-3 hover:bg-slate-50">
                        <p class="text-xs text-slate-500">
                            💬 <strong>{{ $m->mensaje?->user?->name ?? '—' }}</strong>
                            te mencionó en <span class="font-mono text-gpt-700">{{ $m->mensaje?->canal?->nombre ?? '—' }}</span>
                        </p>
                        <p class="text-sm text-slate-700 mt-1 line-clamp-2">{{ $m->mensaje?->contenido }}</p>
                        <p class="text-xs text-slate-400 mt-1">{{ $m->created_at?->diffForHumans() }}</p>
                    </a>
                @endforeach

                @if ($this->notificaciones->isEmpty() && $this->menciones->isEmpty())
                    <p class="px-4 py-8 text-sm text-slate-500 text-center">Sin notificaciones nuevas.</p>
                @endif
            </div>
            <div class="px-4 py-2 border-t bg-slate-50 text-center">
                <a href="{{ route('notificaciones.index') }}" class="text-xs text-gpt-600 hover:underline">Ver todas</a>
            </div>
        </div>
    @endif
</div>
