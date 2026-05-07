<x-layouts.app title="Dashboard">
    <div class="max-w-6xl">
        <h1 class="text-2xl font-semibold text-slate-900">Hola, {{ $user->name }}</h1>
        <p class="text-slate-500 text-sm mt-1">{{ $user->puesto ?? 'Usuario' }} · {{ $user->departamento ?? '—' }}</p>

        <div class="grid md:grid-cols-3 gap-4 mt-6">
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-xs text-slate-500 uppercase font-semibold">Tu rol</p>
                <p class="text-xl font-semibold text-slate-900 mt-1">{{ $user->getRoleNames()->first() ?? 'Sin rol' }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-xs text-slate-500 uppercase font-semibold">Permisos asignados</p>
                <p class="text-xl font-semibold text-slate-900 mt-1">{{ $user->getAllPermissions()->count() }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="text-xs text-slate-500 uppercase font-semibold">Último login</p>
                <p class="text-xl font-semibold text-slate-900 mt-1">{{ optional($user->last_login_at)->diffForHumans() ?? 'Ahora' }}</p>
            </div>
        </div>

        <div class="mt-8 rounded-xl border border-slate-200 bg-white p-6">
            <h2 class="font-semibold text-slate-900">Roadmap de implementación</h2>
            <p class="text-sm text-slate-500 mt-1">Estado de los 14 módulos del plan ejecutable.</p>
            <ol class="mt-4 grid md:grid-cols-2 gap-2 text-sm">
                <li class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>M0 · Fundamentos</li>
                <li class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>M1 · Auth + Roles + RH</li>
                <li class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-400"></span>M2 · Catálogos + CP</li>
                <li class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-400"></span>M3 · Cotización COSS</li>
                <li class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-400"></span>M4 · Adjudicación + Minuta + DN</li>
                <li class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-400"></span>M5 · KOM + Cronograma + BOM</li>
                <li class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-400"></span>M6 · Libro de Proyecto</li>
                <li class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-400"></span>M7 · Bitácora + Viáticos</li>
                <li class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-400"></span>M8 · Cierre + Post-Mortem</li>
                <li class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-400"></span>M9 · Reporte de Asignación</li>
                <li class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-400"></span>M10 · Notificaciones + Chat</li>
                <li class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-400"></span>M11 · Finanzas</li>
                <li class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-400"></span>M12 · Cierres SAT/Gerencial</li>
                <li class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-400"></span>M13 · Vista Ejecutiva</li>
            </ol>
        </div>
    </div>
</x-layouts.app>
