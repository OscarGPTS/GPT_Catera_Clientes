<x-layouts.app title="Vista Ejecutiva">
    @php
        $fmt = fn ($n) => '$'.number_format((float) $n, 0);
        $pct = fn ($n) => number_format((float) $n * 100, 1).'%';
    @endphp

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Vista Ejecutiva · {{ $año }}</h1>
            <p class="text-sm text-slate-500">KPIs en tiempo real para Dirección y Socios.</p>
        </div>
        <form method="GET" class="flex gap-2">
            <select name="año" onchange="this.form.submit()" class="rounded-md border-slate-200 text-sm">
                @for ($y = now()->year - 2; $y <= now()->year + 1; $y++)
                    <option value="{{ $y }}" @selected($año === $y)>{{ $y }}</option>
                @endfor
            </select>
        </form>
    </div>

    @if (! empty($kpis['alertas_concentracion']) && count($kpis['alertas_concentracion']))
        <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-900">
            <strong class="font-semibold">Alerta de concentración por cliente</strong>
            <ul class="mt-1 text-sm space-y-0.5">
                @foreach ($kpis['alertas_concentracion'] as $alerta)
                    <li>{{ $alerta['cliente'] }}: {{ $fmt($alerta['monto']) }} · {{ $pct($alerta['porcentaje']) }} del pipeline (umbral {{ $pct($kpis['umbral_alerta']) }})</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-xs text-slate-500 uppercase font-semibold">Pipeline activo</p>
            <p class="text-2xl font-semibold text-slate-900 mt-1">{{ $fmt($kpis['pipeline_monto']) }}</p>
            <p class="text-xs text-slate-500">{{ $kpis['pipeline_conteo'] }} oportunidades</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-xs text-slate-500 uppercase font-semibold">Adjudicado YTD</p>
            <p class="text-2xl font-semibold text-slate-900 mt-1">{{ $fmt($kpis['adjudicado_monto']) }}</p>
            <p class="text-xs text-slate-500">{{ $kpis['adjudicado_conteo'] }} proyectos</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-xs text-slate-500 uppercase font-semibold">Hit rate · conteo</p>
            <p class="text-2xl font-semibold text-emerald-600 mt-1">{{ $pct($kpis['hit_rate_conteo']) }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-xs text-slate-500 uppercase font-semibold">Hit rate · monto</p>
            <p class="text-2xl font-semibold text-emerald-600 mt-1">{{ $pct($kpis['hit_rate_monto']) }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-xs text-slate-500 uppercase font-semibold">SEDENA del pipeline</p>
            <p class="text-2xl font-semibold {{ $kpis['sedena_share'] > 0.5 ? 'text-rose-600' : 'text-slate-900' }} mt-1">
                {{ $pct($kpis['sedena_share']) }}
            </p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-xs text-slate-500 uppercase font-semibold">Total {{ $año }}</p>
            <p class="text-2xl font-semibold text-slate-900 mt-1">{{ $kpis['total_proyectos_año'] }}</p>
            <p class="text-xs text-slate-500">proyectos</p>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <header class="px-4 py-3 border-b border-slate-200">
            <h2 class="font-semibold text-slate-900">Concentración por cliente (pipeline)</h2>
        </header>
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
                <tr>
                    <th class="px-4 py-2 text-left">Cliente</th>
                    <th class="px-4 py-2 text-right">Monto</th>
                    <th class="px-4 py-2 text-right">% del pipeline</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($kpis['concentracion_clientes'] as $alias => $monto)
                    @php $porc = $kpis['pipeline_monto'] > 0 ? $monto / $kpis['pipeline_monto'] : 0; @endphp
                    <tr>
                        <td class="px-4 py-2 font-medium text-slate-900">{{ $alias }}</td>
                        <td class="px-4 py-2 text-right">{{ $fmt($monto) }}</td>
                        <td class="px-4 py-2 text-right">{{ $pct($porc) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">Sin pipeline.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-slate-500 mt-4">
        TODO M13.1: agregar % dossier completo, % post-mortem, carga del equipo, y filtros adicionales (sublínea, con/sin SEDENA, periodo custom).
    </p>
</x-layouts.app>
