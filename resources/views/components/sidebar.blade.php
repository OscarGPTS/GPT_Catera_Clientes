@php
    $user = auth()->user();
    $can = fn (string $perm) => $user && $user->can($perm);
    $hasRole = fn (string|array $r) => $user && $user->hasAnyRole((array) $r);

    $sections = [
        [
            'label' => 'Principal',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home', 'show' => $user !== null],
            ],
        ],
        [
            'label' => 'Comercial',
            'items' => [
                ['label' => 'Oportunidades', 'route' => 'oportunidades.index', 'icon' => 'briefcase', 'show' => $can('oportunidades.read')],
                ['label' => 'Clientes', 'route' => 'clientes.index', 'icon' => 'users', 'show' => $can('clientes.read')],
            ],
        ],
        [
            'label' => 'Proyectos',
            'items' => [
                ['label' => 'Proyectos', 'route' => 'proyectos.index', 'icon' => 'folder', 'show' => $can('proyectos.read')],
                ['label' => 'Asignaciones', 'route' => 'proyectos.asignaciones', 'icon' => 'calendar', 'show' => $can('asignaciones.read')],
            ],
        ],
        [
            'label' => 'Operaciones',
            'items' => [
                ['label' => 'Bitácoras', 'route' => 'bitacoras.index', 'icon' => 'clipboard', 'show' => $can('bitacoras.read') || $can('bitacoras.create')],
                ['label' => 'Suministros', 'route' => 'suministros.index', 'icon' => 'truck', 'show' => $can('suministros.read') || $can('suministros.update')],
            ],
        ],
        [
            'label' => 'Finanzas',
            'items' => [
                ['label' => 'Cuentas', 'route' => 'finanzas.cuentas', 'icon' => 'bank', 'show' => $can('finanzas.read')],
                ['label' => 'Cierres', 'route' => 'finanzas.cierres', 'icon' => 'chart', 'show' => $can('cierres.read')],
            ],
        ],
        [
            'label' => 'Ejecutivo',
            'items' => [
                ['label' => 'Vista Ejecutiva', 'route' => 'ejecutivo.index', 'icon' => 'star', 'show' => $can('ejecutivo.read')],
            ],
        ],
        [
            'label' => 'Admin',
            'items' => [
                ['label' => 'Usuarios', 'route' => 'admin.usuarios.index', 'icon' => 'cog', 'show' => $hasRole(['super_admin', 'direccion_general'])],
                ['label' => 'Socios', 'route' => 'admin.socios.index', 'icon' => 'cog', 'show' => $hasRole(['super_admin', 'direccion_general'])],
                ['label' => 'Mapeo RH→Rol', 'route' => 'admin.rh-mapping.index', 'icon' => 'cog', 'show' => $hasRole(['super_admin', 'direccion_general'])],
                ['label' => 'Roles & Permisos', 'route' => 'admin.roles.index', 'icon' => 'cog', 'show' => $hasRole(['super_admin', 'direccion_general'])],
            ],
        ],
    ];
@endphp

<aside class="bg-slate-900 text-slate-100 transition-all duration-200 flex-shrink-0"
       :class="sidebarOpen ? 'w-64' : 'w-16'">
    <div class="h-16 flex items-center gap-3 px-4 border-b border-slate-800">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-gpt-600 font-bold flex-shrink-0">G</span>
        <span x-show="sidebarOpen" x-cloak class="font-semibold whitespace-nowrap">GPT Services</span>
    </div>

    <nav class="py-4 space-y-6">
        @foreach ($sections as $section)
            @php
                $visibleItems = array_filter($section['items'], fn ($i) => $i['show'] ?? false);
                if (empty($visibleItems)) continue;
            @endphp
            <div class="px-3">
                <p x-show="sidebarOpen" x-cloak class="text-xs font-semibold uppercase tracking-wider text-slate-500 px-2 mb-2">{{ $section['label'] }}</p>
                <ul class="space-y-1">
                    @foreach ($visibleItems as $item)
                        @php
                            $active = request()->routeIs($item['route']) || request()->routeIs($item['route'] . '.*');
                            try { $url = route($item['route']); } catch (\Throwable) { $url = '#'; }
                        @endphp
                        <li>
                            <a href="{{ $url }}"
                               class="flex items-center gap-3 rounded-md px-2 py-2 text-sm transition {{ $active ? 'bg-gpt-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                                <span class="inline-block w-4 h-4 flex-shrink-0 rounded bg-slate-700/50"></span>
                                <span x-show="sidebarOpen" x-cloak class="whitespace-nowrap">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>
</aside>
