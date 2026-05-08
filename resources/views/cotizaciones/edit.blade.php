<x-layouts.app :title="'Cotización v' . $cotizacion->version">
    <a href="{{ route('cotizaciones.index', $proyecto) }}" class="text-sm text-slate-500 hover:underline">← Versiones</a>

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">
                Cotización <span class="font-mono">v{{ $cotizacion->version }}</span>
                <span class="ml-2 inline-block rounded bg-slate-100 text-slate-700 text-xs px-2 py-0.5">borrador</span>
            </h1>
            <p class="text-slate-600 text-sm mt-1">
                <span class="font-mono">{{ $proyecto->cp_numero }}</span> · {{ $proyecto->cliente?->razon_social }} · {{ $proyecto->sublinea?->nombre }}
            </p>
        </div>
        <form method="POST" action="{{ route('cotizaciones.emitir', [$proyecto, $cotizacion]) }}"
              onsubmit="return confirm('Una vez emitida, la cotización quedará bloqueada. ¿Continuar?');">
            @csrf
            <button class="rounded-md bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 py-2 disabled:opacity-40"
                    @if ($cotizacion->partidas->isEmpty()) disabled @endif>
                Emitir cotización
            </button>
        </form>
    </div>

    <div x-data="cossEditor({
        partidas: @js($cotizacion->partidas->map(fn($p) => [
            'cantidad' => (float) $p->cantidad,
            'costo_unitario' => (float) $p->costo_unitario,
        ])->all()),
        factores: {
            indirectos: {{ (float) $cotizacion->factor_indirectos }},
            admin: {{ (float) $cotizacion->factor_admin }},
            utilidad: {{ (float) $cotizacion->factor_utilidad }},
        },
        precioFinal: {{ (float) $cotizacion->precio_venta_final }},
    })" class="grid lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-6">
            <section class="bg-white border border-slate-200 rounded-xl p-6">
                <h2 class="font-semibold text-slate-900 mb-3">Partidas (BOM)</h2>

                @if ($cotizacion->partidas->isEmpty())
                    <p class="text-sm text-slate-500 mb-4">Aún no hay partidas.</p>
                @else
                    <table class="w-full text-sm mb-4">
                        <thead class="text-xs uppercase text-slate-500 border-b">
                            <tr>
                                <th class="text-left py-2">#</th>
                                <th class="text-left py-2">Descripción</th>
                                <th class="text-right py-2">Cantidad</th>
                                <th class="text-left py-2">Unidad</th>
                                <th class="text-right py-2">$ Unit</th>
                                <th class="text-right py-2">Total</th>
                                <th class="py-2"></th>
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
                                    <td class="py-2 text-right">
                                        <form method="POST" action="{{ route('cotizaciones.partidas.destroy', [$proyecto, $cotizacion, $p]) }}"
                                              onsubmit="return confirm('¿Eliminar partida {{ $p->numero_partida }}?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-rose-600 hover:underline text-xs">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                <details class="mt-2">
                    <summary class="cursor-pointer text-sm text-gpt-600 hover:underline">+ Agregar partida</summary>
                    <form method="POST" action="{{ route('cotizaciones.partidas.store', [$proyecto, $cotizacion]) }}"
                          class="grid md:grid-cols-6 gap-3 mt-3 p-4 bg-slate-50 rounded-lg">
                        @csrf
                        <label class="md:col-span-3 block">
                            <span class="text-xs text-slate-600">Descripción *</span>
                            <input name="descripcion" required class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                        </label>
                        <label class="block">
                            <span class="text-xs text-slate-600">Cantidad *</span>
                            <input name="cantidad" type="number" step="0.0001" min="0.0001" required class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                        </label>
                        <label class="block">
                            <span class="text-xs text-slate-600">Unidad</span>
                            <input name="unidad" maxlength="20" placeholder="pza, m, hr…" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                        </label>
                        <label class="block">
                            <span class="text-xs text-slate-600">$ Unitario *</span>
                            <input name="costo_unitario" type="number" step="0.0001" min="0" required class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                        </label>
                        <div class="md:col-span-6">
                            <button class="rounded-md bg-slate-900 hover:bg-slate-800 text-white text-sm px-4 py-2">Agregar</button>
                        </div>
                    </form>
                </details>
            </section>

            <section class="bg-white border border-slate-200 rounded-xl p-6">
                <h2 class="font-semibold text-slate-900 mb-3">Factores y precio final</h2>
                <form method="POST" action="{{ route('cotizaciones.update', [$proyecto, $cotizacion]) }}" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div class="grid md:grid-cols-4 gap-3">
                        <label class="block">
                            <span class="text-xs text-slate-600">Moneda *</span>
                            <select name="moneda" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                                @foreach (['USD', 'MXN', 'EUR'] as $m)
                                    <option value="{{ $m }}" @selected($cotizacion->moneda === $m)>{{ $m }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-xs text-slate-600">Factor Indirectos</span>
                            <input name="factor_indirectos" type="number" step="0.0001" min="0" max="1"
                                   x-model.number="factores.indirectos"
                                   value="{{ (float) $cotizacion->factor_indirectos }}"
                                   class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono" />
                            <span class="text-[10px] text-slate-400">ej. 0.18 = 18%</span>
                        </label>
                        <label class="block">
                            <span class="text-xs text-slate-600">Factor Admin</span>
                            <input name="factor_admin" type="number" step="0.0001" min="0" max="1"
                                   x-model.number="factores.admin"
                                   value="{{ (float) $cotizacion->factor_admin }}"
                                   class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono" />
                        </label>
                        <label class="block">
                            <span class="text-xs text-slate-600">Factor Utilidad</span>
                            <input name="factor_utilidad" type="number" step="0.0001" min="0" max="1"
                                   x-model.number="factores.utilidad"
                                   value="{{ (float) $cotizacion->factor_utilidad }}"
                                   class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono" />
                        </label>
                    </div>
                    <label class="block">
                        <span class="text-xs text-slate-600">Precio de venta final (override)</span>
                        <input name="precio_venta_final" type="number" step="0.01" min="0"
                               x-model.number="precioFinal"
                               value="{{ (float) $cotizacion->precio_venta_final }}"
                               class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono" />
                        <span class="text-[10px] text-slate-400">Deja en 0 para usar el precio calculado.</span>
                    </label>
                    <label class="block">
                        <span class="text-xs text-slate-600">Observaciones</span>
                        <textarea name="observaciones" rows="3" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">{{ $cotizacion->observaciones }}</textarea>
                    </label>
                    <button class="rounded-md bg-slate-900 hover:bg-slate-800 text-white text-sm px-4 py-2">Guardar cambios</button>
                </form>
            </section>
        </div>

        <aside class="space-y-4">
            <div class="bg-white border border-slate-200 rounded-xl p-5 sticky top-4">
                <h3 class="text-xs uppercase font-semibold text-slate-500 mb-3">COSS — Cálculo en vivo</h3>
                <dl class="text-sm space-y-2 font-mono">
                    <div class="flex justify-between"><dt class="text-slate-500">Costo directo</dt><dd x-text="fmt(coss.cd)"></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Indirectos</dt><dd x-text="fmt(coss.ind)"></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Admin</dt><dd x-text="fmt(coss.adm)"></dd></div>
                    <div class="flex justify-between border-t pt-2"><dt class="text-slate-700 font-semibold">Base</dt><dd class="font-semibold" x-text="fmt(coss.base)"></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Utilidad</dt><dd x-text="fmt(coss.util)"></dd></div>
                    <div class="flex justify-between text-base mt-2 border-t pt-2">
                        <dt class="text-slate-900 font-semibold">Precio de venta</dt>
                        <dd class="text-emerald-700 font-semibold" x-text="fmt(coss.pv)"></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Margen neto</dt>
                        <dd x-text="(coss.margen * 100).toFixed(2) + '%'"></dd>
                    </div>
                </dl>
                <p class="text-[10px] text-slate-400 mt-3">
                    Recalculado en cliente al modificar factores. La fórmula del servidor es la fuente de verdad: presiona "Guardar" para sellar.
                </p>
            </div>
        </aside>
    </div>

    <script>
        function cossEditor({ partidas, factores, precioFinal }) {
            return {
                partidas, factores, precioFinal,
                get coss() {
                    const cd = this.partidas.reduce((s, p) => s + (p.cantidad * p.costo_unitario), 0);
                    const ind = cd * (this.factores.indirectos || 0);
                    const adm = (cd + ind) * (this.factores.admin || 0);
                    const base = cd + ind + adm;
                    const util = base * (this.factores.utilidad || 0);
                    const calc = base + util;
                    const pv = (this.precioFinal && this.precioFinal > 0) ? this.precioFinal : calc;
                    const margen = pv > 0 ? util / pv : 0;
                    return { cd, ind, adm, base, util, pv, margen };
                },
                fmt(n) {
                    return '$' + (n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },
            };
        }
    </script>
</x-layouts.app>
