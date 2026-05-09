<div class="grid lg:grid-cols-4 gap-4 h-[calc(100vh-180px)]" wire:poll.5s="refrescar">
    <aside class="bg-white border border-slate-200 rounded-xl overflow-hidden flex flex-col">
        <div class="px-4 py-3 border-b bg-slate-50">
            <h2 class="font-semibold text-sm text-slate-900">Canales</h2>
            <p class="text-xs text-slate-500">{{ $this->canales->count() }} canales</p>
        </div>
        <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
            @forelse ($this->canales as $c)
                @php
                    $tipoColor = match ($c->tipo) {
                        'proyecto' => 'text-emerald-700',
                        'departamento' => 'text-blue-700',
                        'direccion' => 'text-rose-700',
                        default => 'text-slate-700',
                    };
                @endphp
                <button type="button" wire:click="seleccionar({{ $c->id }})"
                        class="w-full text-left px-4 py-3 hover:bg-slate-50 {{ $canalId === $c->id ? 'bg-gpt-50 border-l-4 border-gpt-600' : '' }}">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-sm font-mono {{ $tipoColor }}">{{ $c->nombre }}</span>
                        @if ($c->no_leidos > 0)
                            <span class="rounded-full bg-rose-600 text-white text-xs px-2 py-0.5">{{ $c->no_leidos }}</span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 truncate mt-0.5">{{ $c->descripcion ?? str_replace('_', ' ', $c->tipo) }}</p>
                </button>
            @empty
                <p class="p-4 text-sm text-slate-500">No tienes canales asignados.</p>
            @endforelse
        </div>
    </aside>

    <div class="lg:col-span-3 bg-white border border-slate-200 rounded-xl overflow-hidden flex flex-col">
        @if ($this->canal)
            <div class="px-4 py-3 border-b bg-slate-50 flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-slate-900 font-mono">{{ $this->canal->nombre }}</h2>
                    <p class="text-xs text-slate-500">
                        {{ $this->canal->miembros->count() }} miembros · {{ str_replace('_', ' ', $this->canal->tipo) }}
                    </p>
                </div>
                <div class="text-xs text-slate-400">
                    @foreach ($this->canal->miembros->take(5) as $m)
                        <span title="{{ $m->name }}" class="inline-block h-7 w-7 -ml-2 rounded-full bg-gpt-200 text-gpt-800 text-center text-xs leading-7 ring-2 ring-white">{{ mb_substr($m->name, 0, 1) }}</span>
                    @endforeach
                    @if ($this->canal->miembros->count() > 5)
                        <span class="ml-1">+{{ $this->canal->miembros->count() - 5 }}</span>
                    @endif
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-4 space-y-3" id="mensajes-container">
                @forelse ($this->mensajes as $m)
                    <div class="flex gap-3" id="msg-{{ $m->id }}">
                        <div class="flex-shrink-0 h-8 w-8 rounded-full bg-gpt-200 text-gpt-800 text-center text-xs font-semibold leading-8">
                            {{ mb_substr($m->user?->name ?? '?', 0, 1) }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline gap-2">
                                <span class="text-sm font-semibold text-slate-900">{{ $m->user?->name ?? '—' }}</span>
                                <span class="text-xs text-slate-400">{{ $m->created_at?->diffForHumans() }}</span>
                                @if ($m->menciones->isNotEmpty())
                                    <span class="text-xs text-amber-600">@ {{ $m->menciones->pluck('user.name')->join(', ') }}</span>
                                @endif
                            </div>
                            <p class="text-sm text-slate-700 whitespace-pre-line">{!! $this->resaltarMenciones($m->contenido) !!}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 text-center py-8">Sin mensajes en este canal todavía.</p>
                @endforelse
            </div>

            <form wire:submit="enviar" class="border-t p-3 flex gap-2">
                <input wire:model="mensaje" type="text" placeholder="Escribe tu mensaje, usa @nombre para mencionar…" required minlength="1" maxlength="4000"
                       class="flex-1 rounded-md border border-slate-200 px-3 py-2 text-sm" />
                <button class="rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm px-4 py-2">Enviar</button>
            </form>
            @error('mensaje')
                <p class="text-xs text-rose-600 px-3 pb-2">{{ $message }}</p>
            @enderror
        @else
            <div class="flex-1 flex items-center justify-center">
                <p class="text-slate-400 text-sm">Selecciona un canal para empezar.</p>
            </div>
        @endif
    </div>

    <script>
        document.addEventListener('livewire:updated', () => {
            const container = document.getElementById('mensajes-container');
            if (container) container.scrollTop = container.scrollHeight;
        });
    </script>
</div>
