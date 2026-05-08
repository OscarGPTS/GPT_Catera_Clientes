@php
    $user = auth()->user();
    $can = fn (string $perm) => $user && $user->can($perm);
    $hasRole = fn (string|array $r) => $user && $user->hasAnyRole((array) $r);

    $icons = [
        'home'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />',
        'briefcase' => '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />',
        'users'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />',
        'folder'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" />',
        'calendar'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />',
        'clipboard' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />',
        'truck'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />',
        'bank'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" />',
        'chart'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />',
        'star'      => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />',
        'cog'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />',
    ];

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
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    {!! $icons[$item['icon']] ?? $icons['cog'] !!}
                                </svg>
                                <span x-show="sidebarOpen" x-cloak class="whitespace-nowrap">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>
</aside>
