@php $user = auth()->user(); @endphp

<header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 lg:px-6">
    <div class="flex items-center gap-3">
        <button type="button" @click="sidebarOpen = !sidebarOpen" class="rounded-md p-2 hover:bg-slate-100" aria-label="Toggle sidebar">
            <svg class="h-5 w-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <input type="search" placeholder="Buscar..." class="hidden md:block rounded-md border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm w-64 focus:outline-none focus:ring-2 focus:ring-gpt-500/30 focus:border-gpt-500">
    </div>

    <div class="flex items-center gap-3">
        @if ($user && ($user->can('oportunidades.create') || $user->hasAnyRole(['comercial', 'director_dn', 'direccion_general'])))
            <a href="{{ route('oportunidades.create') }}" class="hidden md:inline-flex items-center gap-2 rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm font-medium px-3 py-1.5">
                + Nueva oportunidad
            </a>
        @endif

        <button class="relative rounded-md p-2 hover:bg-slate-100" aria-label="Notificaciones">
            <svg class="h-5 w-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14V11a6 6 0 00-12 0v3a2 2 0 01-.6 1.4L4 17h5m6 0a3 3 0 11-6 0"/></svg>
        </button>

        <button class="relative rounded-md p-2 hover:bg-slate-100" aria-label="Chat">
            <svg class="h-5 w-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.4-4 8-9 8a9.9 9.9 0 01-4-.8L3 21l1.8-5A8 8 0 013 12c0-4.4 4-8 9-8s9 3.6 9 8z"/></svg>
        </button>

        @if ($user)
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center gap-2 rounded-md p-1 hover:bg-slate-100">
                    @if ($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="" class="h-8 w-8 rounded-full">
                    @else
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gpt-100 text-gpt-700 font-semibold text-sm">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->name, 0, 1)) }}
                        </span>
                    @endif
                </button>

                <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 mt-2 w-56 rounded-md border border-slate-200 bg-white shadow-lg py-1 text-sm z-30">
                    <div class="px-3 py-2 border-b border-slate-200">
                        <p class="font-medium text-slate-900 truncate">{{ $user->name }}</p>
                        <p class="text-slate-500 truncate text-xs">{{ $user->email }}</p>
                    </div>
                    <a href="#" class="block px-3 py-2 hover:bg-slate-50">Mi perfil</a>
                    <a href="{{ route('perfil.mi-asignacion') }}" class="block px-3 py-2 hover:bg-slate-50">Mi asignación</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full text-left px-3 py-2 hover:bg-slate-50 text-rose-700">Cerrar sesión</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</header>
