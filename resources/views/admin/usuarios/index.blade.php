<x-layouts.app title="Usuarios">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-slate-900">Usuarios</h1>
            <p class="text-sm text-slate-500">{{ $users->total() }} totales</p>
        </div>
    </div>

    <form method="GET" class="flex flex-wrap gap-2 mb-4">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar nombre o email" class="rounded-md border border-slate-200 px-3 py-1.5 text-sm w-72">
        <select name="rol" class="rounded-md border border-slate-200 px-3 py-1.5 text-sm">
            <option value="">Todos los roles</option>
            @foreach ($roles as $role)<option value="{{ $role }}" @selected(request('rol') === $role)>{{ $role }}</option>@endforeach
        </select>
        <select name="status" class="rounded-md border border-slate-200 px-3 py-1.5 text-sm">
            <option value="">Cualquier status</option>
            <option value="active" @selected(request('status') === 'active')>active</option>
            <option value="invited" @selected(request('status') === 'invited')>invited</option>
            <option value="suspended" @selected(request('status') === 'suspended')>suspended</option>
        </select>
        <button class="rounded-md bg-slate-900 text-white px-3 py-1.5 text-sm">Filtrar</button>
    </form>

    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Nombre</th>
                    <th class="px-4 py-3 text-left">Email</th>
                    <th class="px-4 py-3 text-left">Departamento</th>
                    <th class="px-4 py-3 text-left">Puesto</th>
                    <th class="px-4 py-3 text-left">Rol</th>
                    <th class="px-4 py-3 text-left">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse ($users as $user)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $user->departamento ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $user->puesto ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @foreach ($user->roles as $role)
                                <span class="inline-block rounded bg-gpt-50 text-gpt-700 px-2 py-0.5 text-xs">{{ $role->name }}</span>
                            @endforeach
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-block rounded px-2 py-0.5 text-xs
                                @class([
                                    'bg-emerald-50 text-emerald-700' => $user->status === 'active',
                                    'bg-amber-50 text-amber-700' => $user->status === 'invited',
                                    'bg-rose-50 text-rose-700' => $user->status === 'suspended',
                                ])">{{ $user->status }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Sin usuarios.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</x-layouts.app>
