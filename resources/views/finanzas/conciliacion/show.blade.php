<x-layouts.app title="Conciliación de movimiento">
    <a href="{{ route('finanzas.estados.show', [$cuenta, $estado]) }}" class="text-sm text-slate-500 hover:underline">← Estado de cuenta</a>

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Movimiento #{{ $mov->id }}</h1>
            <p class="text-slate-600 text-sm mt-1">{{ $mov->fecha->format('Y-m-d') }} · {{ $mov->descripcion }}</p>
        </div>
        <div class="text-right">
            <p class="text-2xl font-mono font-semibold {{ $mov->tipo === 'ingreso' ? 'text-emerald-700' : 'text-rose-700' }}">
                {{ $mov->tipo === 'ingreso' ? '+' : '−' }}${{ number_format((float) $mov->monto, 2) }}
            </p>
            <p class="text-xs text-slate-500 uppercase">{{ $mov->tipo }} · {{ $cuenta->moneda }}</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            @if ($sugerencias->isEmpty())
                <section class="bg-white border border-slate-200 rounded-xl p-6">
                    <p class="text-sm text-slate-500">Sin sugerencias automáticas. Captura manual abajo.</p>
                </section>
            @else
                <section class="bg-white border border-slate-200 rounded-xl p-6">
                    <h2 class="font-semibold text-slate-900 mb-3">Sugerencias automáticas</h2>
                    <div class="space-y-2">
                        @foreach ($sugerencias as $s)
                            <form method="POST" action="{{ route('finanzas.conciliacion.conciliar', [$cuenta, $estado, $mov]) }}"
                                  class="flex items-center justify-between gap-3 p-3 border border-slate-200 rounded-lg hover:border-gpt-400">
                                @csrf
                                <input type="hidden" name="proyecto_id" value="{{ $s['proyecto']->id }}" />
                                <div class="flex-1 min-w-0">
                                    <p class="font-mono text-sm">{{ $s['proyecto']->cp_numero }}
                                        @if ($s['proyecto']->dn_numero) / <span class="text-emerald-700">{{ $s['proyecto']->dn_numero }}</span> @endif
                                    </p>
                                    <p class="text-xs text-slate-500">{{ $s['proyecto']->cliente?->razon_social }}</p>
                                    <p class="text-xs text-slate-400 mt-1">{{ implode(' · ', $s['razones']) }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold {{ $s['score'] >= 100 ? 'text-emerald-700' : ($s['score'] >= 60 ? 'text-amber-700' : 'text-slate-500') }}">{{ $s['score'] }}</p>
                                    <p class="text-[10px] text-slate-400">score</p>
                                </div>
                                <button class="rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm px-3 py-1.5">Conciliar</button>
                            </form>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="bg-white border border-slate-200 rounded-xl p-6">
                <h2 class="font-semibold text-slate-900 mb-3">Conciliación manual</h2>
                <form method="POST" action="{{ route('finanzas.conciliacion.conciliar', [$cuenta, $estado, $mov]) }}" class="grid md:grid-cols-2 gap-3">
                    @csrf
                    <label class="block">
                        <span class="text-xs text-slate-600">Buscar proyecto</span>
                        <input list="proyectos-list" name="proyecto_input" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono"
                               placeholder="CP-XXX/AA o DN-XXX/AA"
                               onchange="document.querySelector('input[name=proyecto_id]').value = this.value.split('|')[1] || ''" />
                        <input type="hidden" name="proyecto_id" />
                    </label>
                    <label class="block">
                        <span class="text-xs text-slate-600">Folio factura</span>
                        <input name="factura" maxlength="60" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                    </label>
                    <div class="md:col-span-2">
                        <button class="rounded-md bg-slate-900 hover:bg-slate-800 text-white text-sm px-4 py-2">Conciliar manualmente</button>
                    </div>
                </form>
            </section>

            @if ($mov->conciliado_at)
                <section class="bg-emerald-50 border border-emerald-200 rounded-xl p-6">
                    <h2 class="font-semibold text-emerald-900 mb-2">✓ Conciliado</h2>
                    <p class="text-sm text-slate-700">
                        @if ($mov->proyecto)
                            Proyecto: <strong>{{ $mov->proyecto->cp_numero }}</strong>
                        @endif
                        @if ($mov->conciliado_con_factura)
                            · Factura: <strong>{{ $mov->conciliado_con_factura }}</strong>
                        @endif
                    </p>
                    <p class="text-xs text-slate-500 mt-1">{{ $mov->conciliado_at->format('Y-m-d H:i') }}</p>
                    <form method="POST" action="{{ route('finanzas.conciliacion.desconciliar', [$cuenta, $estado, $mov]) }}" class="mt-3"
                          onsubmit="return confirm('¿Remover conciliación?');">
                        @csrf
                        @method('DELETE')
                        <button class="rounded-md border border-rose-200 hover:bg-rose-50 text-rose-700 text-sm px-3 py-1.5">Desconciliar</button>
                    </form>
                </section>
            @endif
        </div>

        <aside class="space-y-4">
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <h3 class="text-xs uppercase font-semibold text-slate-500 mb-3">Detalle</h3>
                <dl class="text-sm space-y-2">
                    <div><dt class="text-slate-500 text-xs">Banco</dt><dd class="uppercase">{{ $cuenta->banco }}</dd></div>
                    <div><dt class="text-slate-500 text-xs">Periodo</dt><dd>{{ str_pad($estado->mes, 2, '0', STR_PAD_LEFT) }}/{{ $estado->año }}</dd></div>
                    <div><dt class="text-slate-500 text-xs">Fecha</dt><dd>{{ $mov->fecha->format('Y-m-d') }}</dd></div>
                    <div><dt class="text-slate-500 text-xs">Descripción</dt><dd class="text-xs">{{ $mov->descripcion }}</dd></div>
                </dl>
            </div>
        </aside>
    </div>

    <datalist id="proyectos-list">
        @foreach (\App\Models\Proyecto::whereNotNull('cp_numero')->orderByDesc('id')->limit(50)->get(['id', 'cp_numero']) as $p)
            <option value="{{ $p->cp_numero }}|{{ $p->id }}">{{ $p->cp_numero }}</option>
        @endforeach
    </datalist>
</x-layouts.app>
