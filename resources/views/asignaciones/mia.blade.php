<x-layouts.app title="Mi asignación">
    <h1 class="text-xl font-semibold text-slate-900 mb-1">Mi asignación · {{ $año }}</h1>
    <p class="text-sm text-slate-500 mb-6">Tarjetas mensuales con tu carga reportada por el sistema.</p>

    <div class="grid md:grid-cols-3 lg:grid-cols-4 gap-3">
        @php $mesesNombre = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre']; @endphp
        @foreach (range(1, 12) as $m)
            @php $snap = $snapshots->firstWhere('mes', $m); @endphp
            <div class="bg-white border border-slate-200 rounded-xl p-4">
                <p class="text-xs text-slate-500 uppercase font-semibold">{{ $mesesNombre[$m - 1] }}</p>
                @if ($snap)
                    <p class="text-2xl font-semibold text-slate-900">{{ $snap->totalProyectos() }} <span class="text-sm font-normal text-slate-500">proyectos</span></p>
                    <ul class="mt-2 text-xs text-slate-600 space-y-0.5">
                        <li>CP asignados: <span class="font-medium text-slate-900">{{ $snap->cp_asignados }}</span></li>
                        <li>DN activos: <span class="font-medium text-slate-900">{{ $snap->dn_activos }}</span></li>
                        <li>DN cerrados: <span class="font-medium text-slate-900">{{ $snap->dn_cerrados }}</span></li>
                    </ul>
                @else
                    <p class="text-slate-400 text-sm mt-2">Sin snapshot.</p>
                @endif
            </div>
        @endforeach
    </div>
</x-layouts.app>
