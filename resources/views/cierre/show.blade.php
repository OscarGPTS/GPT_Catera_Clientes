<x-layouts.app :title="'Cierre · ' . $proyecto->cp_numero">
    <a href="{{ route('oportunidades.show', $proyecto) }}" class="text-sm text-slate-500 hover:underline">← Volver al CP</a>

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Cierre del proyecto</h1>
            <p class="text-slate-600 text-sm mt-1">
                <span class="font-mono">{{ $proyecto->cp_numero }}</span>
                @if ($proyecto->dn_numero) / <span class="font-mono text-emerald-700">{{ $proyecto->dn_numero }}</span> @endif
                · {{ $proyecto->cliente?->razon_social }}
                · estado: <strong>{{ $proyecto->estado }}</strong>
            </p>
        </div>
    </div>

    @php
        $carta = $proyecto->cartaFiniquito;
        $pm = $proyecto->postMortem;
        $cartaFirmada = $carta && $carta->firmado_gpt_at && $carta->firmado_cliente_at;
        $pasos = [
            ['titulo' => 'Libro al 100%', 'ok' => ! $bloqueoLibro['bloqueado']],
            ['titulo' => 'Carta Finiquito creada', 'ok' => (bool) $carta],
            ['titulo' => 'Firmada GPT + Cliente', 'ok' => $cartaFirmada],
            ['titulo' => 'Post-Mortem registrado', 'ok' => (bool) $pm],
            ['titulo' => 'Proyecto cerrado', 'ok' => $proyecto->estado === 'cerrado'],
        ];
    @endphp

    <ol class="grid grid-cols-5 gap-2 mb-6">
        @foreach ($pasos as $i => $paso)
            <li class="text-center">
                <div class="mx-auto h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold {{ $paso['ok'] ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-500' }}">
                    {{ $paso['ok'] ? '✓' : $i + 1 }}
                </div>
                <p class="mt-1 text-xs font-semibold {{ $paso['ok'] ? 'text-emerald-700' : 'text-slate-500' }}">{{ $paso['titulo'] }}</p>
            </li>
        @endforeach
    </ol>

    @if ($bloqueoLibro['bloqueado'])
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 mb-6">
            <p class="text-sm font-semibold text-amber-900">D11 · Libro de proyecto incompleto</p>
            <p class="text-xs text-amber-800 mt-1">No puedes firmar la carta finiquito hasta completar el libro:</p>
            <ul class="list-disc pl-5 mt-2 text-xs text-amber-900 space-y-0.5">
                @foreach ($bloqueoLibro['razones'] as $razon)
                    <li>{{ $razon }}</li>
                @endforeach
            </ul>
            <p class="mt-2 text-xs"><a href="{{ route('libro.show', $proyecto) }}" class="text-amber-900 underline font-semibold">Ir al libro →</a></p>
        </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Carta Finiquito --}}
        <section class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold text-slate-900">📄 Carta Finiquito</h2>
                @if ($carta)
                    <a href="{{ route('cierre.carta.pdf', $proyecto) }}" class="text-xs text-rose-600 hover:underline">PDF →</a>
                @endif
            </div>

            <form method="POST" action="{{ route('cierre.carta.store', $proyecto) }}"
                  x-data="{ personal: {{ Js::from(array_values((array) ($carta->personal_liberado ?? []))) }}, equipos: {{ Js::from(array_values((array) ($carta->equipos_liberados ?? []))) }} }"
                  class="space-y-3">
                @csrf
                <fieldset @if ($cartaFirmada) disabled @endif class="space-y-3">
                    <label class="block">
                        <span class="text-xs text-slate-600">Fecha emisión</span>
                        <input name="fecha_emision" type="date" value="{{ $carta?->fecha_emision?->format('Y-m-d') ?? now()->toDateString() }}" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                    </label>
                    <label class="block">
                        <span class="text-xs text-slate-600">Observaciones</span>
                        <textarea name="observaciones" rows="3" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">{{ $carta?->observaciones }}</textarea>
                    </label>

                    <div>
                        <p class="text-xs text-slate-600 font-semibold mb-1">Personal liberado</p>
                        <template x-for="(p, idx) in personal" :key="idx">
                            <div class="flex gap-1 mb-1">
                                <input :name="`personal_liberado[${idx}][nombre]`" x-model="personal[idx].nombre" placeholder="Nombre" class="flex-1 rounded border border-slate-200 px-2 py-1 text-xs" />
                                <input :name="`personal_liberado[${idx}][rol]`" x-model="personal[idx].rol" placeholder="Rol" class="flex-1 rounded border border-slate-200 px-2 py-1 text-xs" />
                                <button type="button" @click="personal.splice(idx, 1)" class="text-rose-600 text-xs">−</button>
                            </div>
                        </template>
                        <button type="button" @click="personal.push({nombre: '', rol: ''})" class="text-xs text-gpt-600 hover:underline">+ Agregar</button>
                    </div>

                    <div>
                        <p class="text-xs text-slate-600 font-semibold mb-1">Equipos liberados</p>
                        <template x-for="(e, idx) in equipos" :key="idx">
                            <div class="flex gap-1 mb-1">
                                <input :name="`equipos_liberados[${idx}][nombre]`" x-model="equipos[idx].nombre" placeholder="Equipo" class="flex-1 rounded border border-slate-200 px-2 py-1 text-xs" />
                                <button type="button" @click="equipos.splice(idx, 1)" class="text-rose-600 text-xs">−</button>
                            </div>
                        </template>
                        <button type="button" @click="equipos.push({nombre: ''})" class="text-xs text-gpt-600 hover:underline">+ Agregar</button>
                    </div>

                    <button class="rounded-md bg-slate-900 hover:bg-slate-800 text-white text-sm px-4 py-2">Guardar carta</button>
                </fieldset>
            </form>

            <hr class="my-4">

            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm">Firma GPT</span>
                    @if ($carta?->firmado_gpt_at)
                        <span class="text-xs text-emerald-700">✓ {{ $carta->firmado_gpt_at->format('Y-m-d H:i') }}</span>
                    @else
                        <form method="POST" action="{{ route('cierre.carta.firmar.gpt', $proyecto) }}">
                            @csrf
                            <button @if (! $carta || $bloqueoLibro['bloqueado']) disabled @endif class="text-xs rounded-md bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 disabled:opacity-40">Firmar como GPT</button>
                        </form>
                    @endif
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-sm">Firma Cliente</span>
                    @if ($carta?->firmado_cliente_at)
                        <span class="text-xs text-emerald-700">✓ {{ $carta->firmado_cliente_at->format('Y-m-d H:i') }}</span>
                    @else
                        <form method="POST" action="{{ route('cierre.carta.firmar.cliente', $proyecto) }}" class="flex gap-1">
                            @csrf
                            <input name="cliente_nombre" required placeholder="Nombre cliente" class="rounded border border-slate-200 px-2 py-1 text-xs" />
                            <button @if (! $carta?->firmado_gpt_at) disabled @endif class="text-xs rounded-md bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1 disabled:opacity-40">Firmar cliente</button>
                        </form>
                    @endif
                </div>
            </div>

            @if ($cartaFirmada && $proyecto->estado === 'en_cierre')
                <hr class="my-4">
                <form method="POST" action="{{ route('cierre.cerrar', $proyecto) }}"
                      onsubmit="return confirm('Cerrar el proyecto. ¿Continuar?');">
                    @csrf
                    <button class="w-full rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm px-4 py-2">Cerrar proyecto formalmente</button>
                </form>
            @endif
        </section>

        {{-- Post-Mortem --}}
        <section class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-3">
                <h2 class="font-semibold text-slate-900">📋 Post-Mortem</h2>
                @if ($pm)
                    <a href="{{ route('cierre.postmortem.pdf', $proyecto) }}" class="text-xs text-rose-600 hover:underline">PDF →</a>
                @endif
            </div>

            <form method="POST" action="{{ route('cierre.postmortem.store', $proyecto) }}"
                  x-data="{ participantes: {{ Js::from(array_values((array) ($pm->participantes ?? []))) }}, recomendaciones: {{ Js::from(array_values((array) ($pm->recomendaciones_mejora ?? []))) }} }"
                  class="space-y-3">
                @csrf
                <label class="block">
                    <span class="text-xs text-slate-600">Fecha sesión</span>
                    <input name="fecha_sesion" type="date" value="{{ $pm?->fecha_sesion?->format('Y-m-d') ?? now()->toDateString() }}" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                </label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="block">
                        <span class="text-xs text-slate-600">Presupuesto planeado</span>
                        <input name="presupuesto_planeado" type="number" step="0.01" min="0" value="{{ $pm?->presupuesto_planeado ?? $proyecto->monto_preliminar }}" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono" />
                    </label>
                    <label class="block">
                        <span class="text-xs text-slate-600">Presupuesto real</span>
                        <input name="presupuesto_real" type="number" step="0.01" min="0" value="{{ $pm?->presupuesto_real }}" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono" />
                    </label>
                </div>

                @if ($pm)
                    <div class="grid grid-cols-3 gap-2 text-xs">
                        <div class="bg-slate-50 rounded p-2">
                            <p class="text-slate-500">Δ Costo</p>
                            <p class="font-mono font-semibold {{ (float) $pm->desviaciones_costo > 0.05 ? 'text-rose-700' : 'text-emerald-700' }}">
                                {{ $pm->desviaciones_costo !== null ? sprintf('%+.2f%%', (float) $pm->desviaciones_costo * 100) : '—' }}
                            </p>
                        </div>
                        <div class="bg-slate-50 rounded p-2">
                            <p class="text-slate-500">Δ Tiempo</p>
                            <p class="font-mono font-semibold {{ (float) $pm->desviaciones_tiempo > 0.05 ? 'text-rose-700' : 'text-emerald-700' }}">
                                {{ $pm->desviaciones_tiempo !== null ? sprintf('%+.2f%%', (float) $pm->desviaciones_tiempo * 100) : '—' }}
                            </p>
                        </div>
                        <div class="bg-slate-50 rounded p-2">
                            <p class="text-slate-500">Calidad</p>
                            <p class="font-mono font-semibold text-slate-900">{{ number_format((float) $pm->desviaciones_calidad * 100, 1) }}%</p>
                        </div>
                    </div>
                @endif

                <label class="block">
                    <span class="text-xs text-slate-600">Lecciones aprendidas</span>
                    <textarea name="lecciones_aprendidas" rows="5" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">{{ $pm?->lecciones_aprendidas }}</textarea>
                </label>

                <div>
                    <p class="text-xs text-slate-600 font-semibold mb-1">Participantes</p>
                    <template x-for="(p, idx) in participantes" :key="idx">
                        <div class="flex gap-1 mb-1">
                            <input :name="`participantes[${idx}][nombre]`" x-model="participantes[idx].nombre" placeholder="Nombre" class="flex-1 rounded border border-slate-200 px-2 py-1 text-xs" />
                            <input :name="`participantes[${idx}][rol]`" x-model="participantes[idx].rol" placeholder="Rol" class="flex-1 rounded border border-slate-200 px-2 py-1 text-xs" />
                            <button type="button" @click="participantes.splice(idx, 1)" class="text-rose-600 text-xs">−</button>
                        </div>
                    </template>
                    <button type="button" @click="participantes.push({nombre: '', rol: ''})" class="text-xs text-gpt-600 hover:underline">+ Agregar</button>
                </div>

                <div>
                    <p class="text-xs text-slate-600 font-semibold mb-1">Recomendaciones de mejora</p>
                    <template x-for="(r, idx) in recomendaciones" :key="idx">
                        <div class="flex gap-1 mb-1">
                            <input :name="`recomendaciones_mejora[${idx}][texto]`" x-model="recomendaciones[idx].texto" placeholder="Recomendación" class="flex-1 rounded border border-slate-200 px-2 py-1 text-xs" />
                            <input :name="`recomendaciones_mejora[${idx}][responsable]`" x-model="recomendaciones[idx].responsable" placeholder="Responsable" class="w-32 rounded border border-slate-200 px-2 py-1 text-xs" />
                            <button type="button" @click="recomendaciones.splice(idx, 1)" class="text-rose-600 text-xs">−</button>
                        </div>
                    </template>
                    <button type="button" @click="recomendaciones.push({texto: '', responsable: ''})" class="text-xs text-gpt-600 hover:underline">+ Agregar</button>
                </div>

                <button class="rounded-md bg-slate-900 hover:bg-slate-800 text-white text-sm px-4 py-2">Guardar post-mortem</button>
            </form>
        </section>
    </div>
</x-layouts.app>
