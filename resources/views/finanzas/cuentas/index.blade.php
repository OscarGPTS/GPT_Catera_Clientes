<x-layouts.app title="Cuentas bancarias">
    <div class="flex items-start justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Cuentas bancarias</h1>
            <p class="text-slate-600 text-sm mt-1">{{ $cuentas->count() }} cuentas registradas. Solo se almacenan números enmascarados.</p>
        </div>
    </div>

    <details class="bg-white border border-slate-200 rounded-xl p-5 mb-6">
        <summary class="cursor-pointer text-sm font-semibold text-gpt-600">+ Nueva cuenta</summary>
        <form method="POST" action="{{ route('finanzas.cuentas.store') }}" class="grid md:grid-cols-3 gap-3 mt-4">
            @csrf
            <label class="block">
                <span class="text-xs text-slate-600">Banco *</span>
                <select name="banco" required class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    @foreach (['bbva', 'banorte', 'banamex', 'santander', 'hsbc', 'otro'] as $b)
                        <option value="{{ $b }}">{{ ucfirst($b) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="text-xs text-slate-600">Alias</span>
                <input name="alias" maxlength="60" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
            </label>
            <label class="block">
                <span class="text-xs text-slate-600">Moneda *</span>
                <select name="moneda" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm">
                    <option value="MXN">MXN</option>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                </select>
            </label>
            <label class="block md:col-span-2">
                <span class="text-xs text-slate-600">Cuenta enmascarada *</span>
                <input name="numero_cuenta_enmascarado" required maxlength="30" placeholder="****1234" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono" />
            </label>
            <label class="block">
                <span class="text-xs text-slate-600">CLABE enmascarada</span>
                <input name="clabe_enmascarada" maxlength="30" placeholder="********9876" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm font-mono" />
            </label>
            <div class="md:col-span-3">
                <button class="rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm px-4 py-2">Crear cuenta</button>
            </div>
        </form>
    </details>

    @if ($cuentas->isEmpty())
        <div class="bg-white border border-dashed border-slate-300 rounded-xl p-10 text-center">
            <p class="text-slate-500">Aún no hay cuentas registradas.</p>
        </div>
    @else
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($cuentas as $c)
                <a href="{{ route('finanzas.estados.index', $c) }}" class="block bg-white border border-slate-200 rounded-xl p-5 hover:border-gpt-400">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold text-slate-900 uppercase">{{ $c->banco }}</h3>
                        @if ($c->activa)
                            <span class="rounded bg-emerald-100 text-emerald-800 text-xs px-2 py-0.5">activa</span>
                        @else
                            <span class="rounded bg-slate-100 text-slate-700 text-xs px-2 py-0.5">inactiva</span>
                        @endif
                    </div>
                    <p class="text-sm text-slate-700">{{ $c->alias ?? '—' }}</p>
                    <p class="text-xs font-mono text-slate-500 mt-1">{{ $c->numero_cuenta_enmascarado }} · {{ $c->moneda }}</p>
                    <p class="text-xs text-slate-500 mt-2">{{ $c->estados_cuenta_count }} estados de cuenta importados</p>
                </a>
            @endforeach
        </div>
    @endif
</x-layouts.app>
