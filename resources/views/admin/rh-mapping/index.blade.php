<x-layouts.app title="Mapeo RH → Rol">
    <h1 class="text-xl font-semibold text-slate-900 mb-1">Mapeo RH → Rol del sistema (D13)</h1>
    <p class="text-sm text-slate-500 mb-6">Reglas que asignan rol según el puesto reportado por la API RH al hacer login.</p>

    <section class="bg-white border border-slate-200 rounded-xl mb-6">
        <header class="px-4 py-3 border-b border-slate-200">
            <h2 class="font-semibold text-slate-900">Nueva regla</h2>
        </header>
        <form method="POST" action="{{ route('admin.rh-mapping.store') }}" class="grid md:grid-cols-5 gap-2 p-4">
            @csrf
            <input name="puesto_rh" required placeholder="%Gerente de Proyectos%" class="rounded-md border border-slate-200 px-3 py-1.5 text-sm">
            <select name="rol_sistema" required class="rounded-md border border-slate-200 px-3 py-1.5 text-sm">
                <option value="">Rol</option>
                @foreach ($roles as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach
            </select>
            <input name="prioridad" type="number" min="0" max="100" value="50" class="rounded-md border border-slate-200 px-3 py-1.5 text-sm">
            <input name="departamento_filter" placeholder="dept (opcional)" class="rounded-md border border-slate-200 px-3 py-1.5 text-sm">
            <button class="rounded-md bg-gpt-600 text-white text-sm px-3 py-1.5">Agregar</button>
        </form>
    </section>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
                <tr>
                    <th class="px-4 py-2 text-left">Patrón puesto RH</th>
                    <th class="px-4 py-2 text-left">Rol</th>
                    <th class="px-4 py-2 text-left">Prioridad</th>
                    <th class="px-4 py-2 text-left">Filtro depto</th>
                    <th class="px-4 py-2 text-left">Activo</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @foreach ($rules as $rule)
                    <tr>
                        <form method="POST" action="{{ route('admin.rh-mapping.update', $rule) }}">
                            @csrf @method('PATCH')
                            <td class="px-4 py-2"><input name="puesto_rh" value="{{ $rule->puesto_rh }}" class="w-full rounded border-slate-200 text-xs"></td>
                            <td class="px-4 py-2">
                                <select name="rol_sistema" class="rounded border-slate-200 text-xs">
                                    @foreach ($roles as $r)<option value="{{ $r }}" @selected($rule->rol_sistema === $r)>{{ $r }}</option>@endforeach
                                </select>
                            </td>
                            <td class="px-4 py-2"><input name="prioridad" type="number" value="{{ $rule->prioridad }}" class="w-16 rounded border-slate-200 text-xs"></td>
                            <td class="px-4 py-2"><input name="departamento_filter" value="{{ $rule->departamento_filter }}" class="w-full rounded border-slate-200 text-xs"></td>
                            <td class="px-4 py-2"><input type="checkbox" name="activo" value="1" @checked($rule->activo)></td>
                            <td class="px-4 py-2 flex gap-2">
                                <button class="text-xs text-emerald-700 hover:underline">Guardar</button>
                        </form>
                                <form method="POST" action="{{ route('admin.rh-mapping.destroy', $rule) }}" onsubmit="return confirm('¿Eliminar regla?')">@csrf @method('DELETE')
                                    <button class="text-xs text-rose-700 hover:underline">Eliminar</button>
                                </form>
                            </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.app>
