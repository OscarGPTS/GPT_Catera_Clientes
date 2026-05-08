<x-layouts.app title="Oportunidades">
    @php
        $estadoBadge = [
            'en_revision' => 'bg-slate-100 text-slate-700',
            'cotizando' => 'bg-amber-100 text-amber-800',
            'cotizado' => 'bg-amber-100 text-amber-800',
            'presentado' => 'bg-blue-100 text-blue-800',
            'adjudicado_pendiente' => 'bg-emerald-50 text-emerald-700',
            'adjudicado_firmado' => 'bg-emerald-100 text-emerald-800',
            'en_ejecucion' => 'bg-emerald-200 text-emerald-900',
            'en_cierre' => 'bg-violet-100 text-violet-800',
            'cerrado' => 'bg-slate-200 text-slate-800',
            'cancelado' => 'bg-rose-100 text-rose-800',
            'perdido' => 'bg-rose-100 text-rose-800',
            'archivado' => 'bg-slate-100 text-slate-500',
        ];
    @endphp

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Status de ofertas</h1>
            <p class="text-sm text-slate-500">Pipeline comercial y proyectos en curso.</p>
        </div>
        @can('oportunidades.create')
            <a href="{{ route('oportunidades.create') }}" class="rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm font-medium px-4 py-2">
                + Nueva oportunidad
            </a>
        @endcan
    </div>

    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-xs text-slate-500 uppercase font-semibold">Total</p>
            <p class="text-2xl font-semibold text-slate-900 mt-1">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-xs text-slate-500 uppercase font-semibold">Pipeline</p>
            <p class="text-2xl font-semibold text-amber-700 mt-1">{{ $stats['pipeline'] }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-xs text-slate-500 uppercase font-semibold">Adjudicados</p>
            <p class="text-2xl font-semibold text-emerald-700 mt-1">{{ $stats['adjudicados'] }}</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-4">
            <p class="text-xs text-slate-500 uppercase font-semibold">Cerrados</p>
            <p class="text-2xl font-semibold text-slate-700 mt-1">{{ $stats['cerrados'] }}</p>
        </div>
    </div>

    <form method="GET" class="grid md:grid-cols-6 gap-2 mb-4">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="CP, DN, Tech ref, usuario final" class="md:col-span-2 rounded-md border border-slate-200 px-3 py-1.5 text-sm">
        <select name="año" class="rounded-md border border-slate-200 px-3 py-1.5 text-sm">
            <option value="">Año</option>
            @for ($y = now()->year - 2; $y <= now()->year + 1; $y++)
                <option value="{{ $y }}" @selected((int) request('año') === $y)>{{ $y }}</option>
            @endfor
        </select>
        <select name="sublinea" class="rounded-md border border-slate-200 px-3 py-1.5 text-sm">
            <option value="">Sublínea</option>
            @foreach ($sublineas as $s)
                <option value="{{ $s->codigo }}" @selected(request('sublinea') === $s->codigo)>{{ $s->codigo }} · {{ $s->nombre }}</option>
            @endforeach
        </select>
        <select name="cliente" class="rounded-md border border-slate-200 px-3 py-1.5 text-sm">
            <option value="">Cliente</option>
            @foreach ($clientes as $c)
                <option value="{{ $c->id }}" @selected((int) request('cliente') === $c->id)>{{ $c->alias_3letras }} — {{ $c->razon_social }}</option>
            @endforeach
        </select>
        <select name="estado" class="rounded-md border border-slate-200 px-3 py-1.5 text-sm">
            <option value="">Estado</option>
            @foreach ($estados as $key => $label)
                <option value="{{ $key }}" @selected(request('estado') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="gerente" class="rounded-md border border-slate-200 px-3 py-1.5 text-sm">
            <option value="">Gerente</option>
            @foreach ($gerentes as $g)
                <option value="{{ $g->id }}" @selected((int) request('gerente') === $g->id)>{{ $g->name }}</option>
            @endforeach
        </select>
        <button class="md:col-span-1 rounded-md bg-slate-900 hover:bg-slate-800 text-white text-sm px-3 py-1.5">Filtrar</button>
        <a href="{{ route('oportunidades.index') }}" class="md:col-span-1 text-center rounded-md border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm px-3 py-1.5">Limpiar</a>
    </form>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">CP / DN</th>
                    <th class="px-4 py-3 text-left">Cliente</th>
                    <th class="px-4 py-3 text-left">Sublínea</th>
                    <th class="px-4 py-3 text-left">Usuario final</th>
                    <th class="px-4 py-3 text-left">Estado</th>
                    <th class="px-4 py-3 text-right">Monto</th>
                    <th class="px-4 py-3 text-left">Líder</th>
                    <th class="px-4 py-3 text-left">Última act.</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse ($proyectos as $p)
                    <tr class="hover:bg-slate-50 cursor-pointer" onclick="window.location='{{ route('oportunidades.show', $p) }}'">
                        <td class="px-4 py-3">
                            <div class="font-mono text-slate-900 text-xs">{{ $p->cp_numero ?? '—' }}</div>
                            @if ($p->dn_numero)
                                <div class="font-mono text-emerald-700 text-xs">{{ $p->dn_numero }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-900">{{ $p->cliente?->alias_3letras }}</div>
                            <div class="text-xs text-slate-500 truncate max-w-xs">{{ $p->cliente?->razon_social }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs">{{ $p->sublinea?->codigo }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs truncate max-w-xs">{{ $p->usuario_final ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block rounded px-2 py-0.5 text-xs {{ $estadoBadge[$p->estado] ?? 'bg-slate-100' }}">
                                {{ $estados[$p->estado] ?? $p->estado }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right text-xs font-mono text-slate-700">
                            {{ $p->monto_preliminar ? '$'.number_format((float) $p->monto_preliminar, 0).' '.$p->moneda : '—' }}
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600">{{ $p->gerenteProyectos?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $p->updated_at->diffForHumans() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-12 text-center text-slate-500">Sin oportunidades. Crea la primera con el botón de arriba.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $proyectos->links() }}</div>
</x-layouts.app>
