<x-layouts.app title="Socios">
    <h1 class="text-xl font-semibold text-slate-900 mb-1">Socios</h1>
    <p class="text-sm text-slate-500 mb-6">Override manual de <code>es_socio</code> y allowlist por email (D2).</p>

    <div class="grid lg:grid-cols-2 gap-6">
        <section class="bg-white border border-slate-200 rounded-xl">
            <header class="px-4 py-3 border-b border-slate-200">
                <h2 class="font-semibold text-slate-900">Override por usuario</h2>
            </header>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
                        <tr><th class="px-4 py-2 text-left">Usuario</th><th class="px-4 py-2 text-left">Override</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($users as $user)
                            <tr>
                                <td class="px-4 py-2">
                                    <div class="font-medium text-slate-900">{{ $user->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $user->email }}</div>
                                </td>
                                <td class="px-4 py-2">
                                    <form method="POST" action="{{ route('admin.socios.toggle', $user) }}" class="inline">
                                        @csrf @method('PATCH')
                                        <select name="value" onchange="this.form.submit()" class="rounded border border-slate-200 px-2 py-1 text-xs">
                                            <option value="" @selected($user->es_socio_override === null)>auto (RH/allowlist)</option>
                                            <option value="1" @selected($user->es_socio_override === true)>forzar SÍ</option>
                                            <option value="0" @selected($user->es_socio_override === false)>forzar NO</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3">{{ $users->links() }}</div>
        </section>

        <section class="bg-white border border-slate-200 rounded-xl">
            <header class="px-4 py-3 border-b border-slate-200">
                <h2 class="font-semibold text-slate-900">Allowlist de socios</h2>
            </header>
            <form method="POST" action="{{ route('admin.socios.allowlist.store') }}" class="px-4 py-3 border-b border-slate-200 flex flex-wrap gap-2">
                @csrf
                <input name="email" type="email" required placeholder="email@ejemplo.com" class="flex-1 rounded-md border border-slate-200 px-3 py-1.5 text-sm">
                <input name="notes" type="text" placeholder="notas (opcional)" class="flex-1 rounded-md border border-slate-200 px-3 py-1.5 text-sm">
                <button class="rounded-md bg-gpt-600 text-white text-sm px-3 py-1.5">Agregar</button>
            </form>
            <ul class="divide-y divide-slate-200">
                @forelse ($allowlist as $entry)
                    <li class="px-4 py-2 flex items-center justify-between">
                        <div>
                            <div class="font-medium text-slate-900">{{ $entry->email }}</div>
                            <div class="text-xs text-slate-500">{{ $entry->notes ?? '—' }}</div>
                        </div>
                        <form method="POST" action="{{ route('admin.socios.allowlist.destroy', $entry) }}">@csrf @method('DELETE')
                            <button class="text-rose-600 text-xs hover:underline">Quitar</button>
                        </form>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-slate-500 text-sm">Allowlist vacía.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-layouts.app>
