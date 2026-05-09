<x-layouts.app :title="'Bitácora · ' . $bitacora->fecha->format('Y-m-d')">
    <a href="{{ route('bitacoras.index', $proyecto) }}" class="text-sm text-slate-500 hover:underline">← Bitácoras</a>

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">
                Bitácora · <span class="font-mono">{{ $bitacora->fecha->format('Y-m-d') }}</span>
                @if ($bitacora->tieneDesviacion())
                    <span class="ml-2 inline-block rounded bg-rose-100 text-rose-800 text-xs px-2 py-0.5">⚠ posible desviación</span>
                @endif
                @if ($bitacora->firmado_at)
                    <span class="ml-2 inline-block rounded bg-emerald-100 text-emerald-800 text-xs px-2 py-0.5">✓ firmada</span>
                @else
                    <span class="ml-2 inline-block rounded bg-slate-100 text-slate-700 text-xs px-2 py-0.5">borrador</span>
                @endif
            </h1>
            <p class="text-slate-600 text-sm mt-1">
                <span class="font-mono">{{ $proyecto->cp_numero }}</span> · {{ $proyecto->cliente?->razon_social }}
                · cargada por {{ $bitacora->cargadoPor?->name ?? '—' }} {{ $bitacora->created_at->diffForHumans() }}
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('bitacoras.pdf', [$proyecto, $bitacora]) }}" class="rounded-md bg-rose-600 hover:bg-rose-700 text-white text-sm px-4 py-2">PDF</a>
            @if (! $bitacora->firmado_at)
                <form method="POST" action="{{ route('bitacoras.destroy', [$proyecto, $bitacora]) }}"
                      onsubmit="return confirm('¿Eliminar bitácora?');">
                    @csrf
                    @method('DELETE')
                    <button class="rounded-md border border-rose-200 hover:bg-rose-50 text-rose-700 text-sm px-4 py-2">Eliminar</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <section class="bg-white border border-slate-200 rounded-xl p-6">
                <h2 class="font-semibold text-slate-900 mb-3">Relación de actividades</h2>
                @if ($bitacora->firmado_at)
                    <p class="text-sm whitespace-pre-line">{{ $bitacora->relacion_actividades }}</p>
                @else
                    <form method="POST" action="{{ route('bitacoras.update', [$proyecto, $bitacora]) }}" class="space-y-2">
                        @csrf
                        @method('PATCH')
                        <textarea name="relacion_actividades" rows="10" required minlength="10" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono">{{ $bitacora->relacion_actividades }}</textarea>
                        <button class="rounded-md bg-slate-900 hover:bg-slate-800 text-white text-sm px-4 py-2">Guardar</button>
                    </form>
                @endif
            </section>

            @if (! empty($bitacora->personal_gpt))
                <section class="bg-white border border-slate-200 rounded-xl p-6">
                    <h2 class="font-semibold text-slate-900 mb-3">Personal en sitio</h2>
                    <ul class="text-sm space-y-1">
                        @foreach ($bitacora->personal_gpt as $p)
                            <li>{{ is_array($p) ? ($p['nombre'] ?? '') : $p }} <span class="text-slate-500 text-xs">{{ is_array($p) ? ($p['rol'] ?? '') : '' }}</span></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if (! empty($bitacora->equipos_en_sitio))
                <section class="bg-white border border-slate-200 rounded-xl p-6">
                    <h2 class="font-semibold text-slate-900 mb-3">Equipos en sitio</h2>
                    <ul class="text-sm space-y-1">
                        @foreach ($bitacora->equipos_en_sitio as $e)
                            <li>{{ is_array($e) ? ($e['nombre'] ?? '') : $e }} <span class="text-slate-500 text-xs">{{ is_array($e) ? ($e['cantidad'] ?? '') : '' }}</span></li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <aside class="space-y-4">
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <h3 class="text-xs uppercase font-semibold text-slate-500 mb-3">VoBo del cliente</h3>
                @if ($bitacora->firmado_at)
                    <p class="text-sm">{{ $bitacora->vobo_cliente_nombre }}</p>
                    <p class="text-xs text-slate-500">{{ $bitacora->vobo_cliente_organizacion ?? '—' }}</p>
                    <p class="text-xs text-slate-500">{{ optional($bitacora->vobo_cliente_fecha)->format('Y-m-d') }}</p>
                @else
                    <form method="POST" action="{{ route('bitacoras.vobo', [$proyecto, $bitacora]) }}" class="space-y-2">
                        @csrf
                        <input name="vobo_cliente_nombre" required maxlength="120" placeholder="Nombre del cliente" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                        <input name="vobo_cliente_organizacion" maxlength="120" placeholder="Organización" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                        <input name="vobo_cliente_fecha" type="date" value="{{ now()->toDateString() }}" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                        <button class="rounded-md bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 py-2 w-full">Registrar VoBo</button>
                    </form>
                @endif
            </div>
        </aside>
    </div>
</x-layouts.app>
