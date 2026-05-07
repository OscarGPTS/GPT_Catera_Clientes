<x-layouts.app title="Roles & Permisos">
    <h1 class="text-xl font-semibold text-slate-900 mb-1">Roles y permisos</h1>
    <p class="text-sm text-slate-500 mb-6">Matriz de los 22 roles (D13). Cambios de permisos van por código (RolesPermissionsSeeder).</p>

    <div class="bg-white border border-slate-200 rounded-xl overflow-x-auto">
        <table class="min-w-full text-xs">
            <thead class="bg-slate-50 sticky top-0">
                <tr>
                    <th class="px-3 py-2 text-left text-slate-600 sticky left-0 bg-slate-50">Permiso \ Rol</th>
                    @foreach ($roles as $role)
                        <th class="px-2 py-2 text-slate-600 font-medium whitespace-nowrap" style="writing-mode: vertical-rl; transform: rotate(180deg); height: 160px;">{{ $role->name }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($permissions as $perm)
                    <tr>
                        <td class="px-3 py-1.5 sticky left-0 bg-white font-mono text-[11px] text-slate-700 whitespace-nowrap">{{ $perm->name }}</td>
                        @foreach ($roles as $role)
                            <td class="px-2 py-1.5 text-center">
                                @if ($role->permissions->contains('id', $perm->id))
                                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                                @else
                                    <span class="inline-block h-2 w-2 rounded-full bg-slate-200"></span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-layouts.app>
