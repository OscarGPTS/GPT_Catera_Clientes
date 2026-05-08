<x-layouts.app title="Nueva oportunidad">
    <div class="max-w-3xl">
        <a href="{{ route('oportunidades.index') }}" class="text-sm text-slate-500 hover:underline">← Volver a oportunidades</a>
        <h1 class="text-xl font-semibold text-slate-900 mt-2 mb-1">Nueva oportunidad</h1>
        <p class="text-sm text-slate-500 mb-6">Al guardar se asignará un CP-XXX/{{ now()->format('y') }} de forma atómica.</p>

        <form method="POST" action="{{ route('oportunidades.store') }}" class="bg-white border border-slate-200 rounded-xl p-6 space-y-5">
            @csrf

            <div>
                <h2 class="text-sm font-semibold text-slate-700 mb-3">1 · Datos básicos</h2>
                <div class="grid md:grid-cols-2 gap-3">
                    <label class="block">
                        <span class="text-xs text-slate-600">Cliente *</span>
                        <select name="cliente_id" required class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                            <option value="">Seleccionar…</option>
                            @foreach ($clientes as $c)
                                <option value="{{ $c->id }}" @selected(old('cliente_id') == $c->id)>{{ $c->alias_3letras }} — {{ $c->razon_social }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-xs text-slate-600">Sublínea *</span>
                        <select name="sublinea_id" required class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                            <option value="">Seleccionar…</option>
                            @foreach ($sublineas as $s)
                                <option value="{{ $s->id }}" @selected(old('sublinea_id') == $s->id)>{{ $s->codigo }} · {{ $s->nombre }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-xs text-slate-600">Usuario final</span>
                        <input type="text" name="usuario_final" value="{{ old('usuario_final') }}" placeholder="Ej. CENAGAS"
                               class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    </label>
                    <label class="block">
                        <span class="text-xs text-slate-600">Sector</span>
                        <select name="sector" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                            <option value="">—</option>
                            @foreach ($sectores as $s)
                                <option value="{{ $s }}" @selected(old('sector') === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>

            <div>
                <h2 class="text-sm font-semibold text-slate-700 mb-3">2 · Resumen ejecutivo</h2>
                <textarea name="resumen_ejecutivo" required rows="4" minlength="20"
                          placeholder="Alcance, plazo estimado, contexto. Mínimo 20 caracteres."
                          class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">{{ old('resumen_ejecutivo') }}</textarea>
            </div>

            <div>
                <h2 class="text-sm font-semibold text-slate-700 mb-3">3 · Plazo y monto</h2>
                <div class="grid md:grid-cols-3 gap-3">
                    <label class="block">
                        <span class="text-xs text-slate-600">Inicio planeado</span>
                        <input type="date" name="fecha_inicio_planeada" value="{{ old('fecha_inicio_planeada') }}"
                               class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    </label>
                    <label class="block">
                        <span class="text-xs text-slate-600">Fin planeado</span>
                        <input type="date" name="fecha_fin_planeada" value="{{ old('fecha_fin_planeada') }}"
                               class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    </label>
                    <label class="block">
                        <span class="text-xs text-slate-600">Distribución plurianual *</span>
                        <select name="metodo_distribucion_plurianual" required class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                            <option value="dias_naturales" @selected(old('metodo_distribucion_plurianual', 'dias_naturales') === 'dias_naturales')>Por días naturales</option>
                            <option value="hitos" @selected(old('metodo_distribucion_plurianual') === 'hitos')>Por hitos</option>
                        </select>
                    </label>
                    <label class="block">
                        <span class="text-xs text-slate-600">Monto preliminar</span>
                        <input type="number" step="0.01" min="0" name="monto_preliminar" value="{{ old('monto_preliminar') }}"
                               class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    </label>
                    <label class="block">
                        <span class="text-xs text-slate-600">Moneda *</span>
                        <select name="moneda" required class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                            <option value="USD" @selected(old('moneda', 'USD') === 'USD')>USD</option>
                            <option value="MXN" @selected(old('moneda') === 'MXN')>MXN</option>
                            <option value="EUR" @selected(old('moneda') === 'EUR')>EUR</option>
                        </select>
                    </label>
                </div>
            </div>

            <div>
                <h2 class="text-sm font-semibold text-slate-700 mb-3">4 · Director DN responsable</h2>
                <select name="director_dn_id" required class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    <option value="">Seleccionar…</option>
                    @foreach ($directoresDn as $d)
                        <option value="{{ $d->id }}" @selected(old('director_dn_id') == $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-500 mt-1">El comité comercial aprobará/asignará al gerente_proyectos en el siguiente paso.</p>
            </div>

            <div class="flex justify-end gap-2 border-t border-slate-200 pt-4">
                <a href="{{ route('oportunidades.index') }}" class="rounded-md border border-slate-200 hover:bg-slate-50 px-4 py-2 text-sm">Cancelar</a>
                <button class="rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm font-medium px-4 py-2">Crear oportunidad y asignar CP</button>
            </div>
        </form>
    </div>
</x-layouts.app>
