@php
    $pasos = [
        'cotizado' => ['titulo' => 'Cotizado', 'descripcion' => 'Cotización emitida'],
        'presentado' => ['titulo' => 'Presentado', 'descripcion' => 'Cotización enviada al cliente'],
        'adjudicado_pendiente' => ['titulo' => 'Adjudicado', 'descripcion' => 'Cliente confirma · falta firma OC'],
        'adjudicado_firmado' => ['titulo' => 'OC firmada', 'descripcion' => 'DN asignado · listo para iniciar'],
    ];
    $orden = array_keys($pasos);
    $pasoActualIdx = array_search($proyecto->estado, $orden, true);
@endphp

<section class="bg-white border border-slate-200 rounded-xl p-6">
    <h2 class="font-semibold text-slate-900 mb-4">Adjudicación CP→DN</h2>

    <ol class="grid grid-cols-4 gap-2 mb-6">
        @foreach ($orden as $idx => $estado)
            @php
                $completado = $pasoActualIdx !== false && $idx < $pasoActualIdx;
                $activo = $proyecto->estado === $estado;
            @endphp
            <li class="text-center">
                <div class="mx-auto h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold
                    {{ $completado ? 'bg-emerald-600 text-white' : ($activo ? 'bg-gpt-600 text-white' : 'bg-slate-100 text-slate-500') }}">
                    {{ $completado ? '✓' : $idx + 1 }}
                </div>
                <p class="mt-1 text-xs font-semibold {{ $activo ? 'text-slate-900' : 'text-slate-500' }}">{{ $pasos[$estado]['titulo'] }}</p>
                <p class="text-[10px] text-slate-400">{{ $pasos[$estado]['descripcion'] }}</p>
            </li>
        @endforeach
    </ol>

    @if ($proyecto->estado === 'cotizado')
        <div class="grid md:grid-cols-2 gap-4">
            <form method="POST" action="{{ route('adjudicacion.presentar', $proyecto) }}" class="space-y-2 p-4 bg-slate-50 rounded-lg">
                @csrf
                <p class="text-sm font-semibold text-slate-700">Marcar como presentado al cliente</p>
                <textarea name="comentario" rows="2" placeholder="Comentario (opcional)" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"></textarea>
                <button class="rounded-md bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2">Presentar al cliente</button>
            </form>
            <form method="POST" action="{{ route('adjudicacion.perdido', $proyecto) }}" class="space-y-2 p-4 bg-slate-50 rounded-lg"
                  onsubmit="return confirm('¿Marcar como perdido?');">
                @csrf
                <p class="text-sm font-semibold text-slate-700">Marcar como perdido</p>
                <textarea name="razon" rows="2" required minlength="5" placeholder="Razón (obligatoria, mínimo 5 caracteres)" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"></textarea>
                <button class="rounded-md border border-rose-300 hover:bg-rose-50 text-rose-700 text-sm px-4 py-2">Marcar perdido</button>
            </form>
        </div>
    @endif

    @if ($proyecto->estado === 'presentado')
        <div class="grid md:grid-cols-2 gap-4">
            <form method="POST" action="{{ route('adjudicacion.registrar', $proyecto) }}" class="space-y-3 p-4 bg-emerald-50 rounded-lg">
                @csrf
                <p class="text-sm font-semibold text-slate-700">Cliente adjudicó</p>
                <label class="block">
                    <span class="text-xs text-slate-600">Referencia OC *</span>
                    <input name="oc_referencia" required class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                </label>
                <div class="grid grid-cols-2 gap-2">
                    <label class="block">
                        <span class="text-xs text-slate-600">Fecha OC</span>
                        <input name="oc_fecha" type="date" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                    </label>
                    <label class="block">
                        <span class="text-xs text-slate-600">Monto OC</span>
                        <input name="oc_monto" type="number" step="0.01" min="0" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                    </label>
                </div>
                <button class="rounded-md bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 py-2">Registrar adjudicación</button>
            </form>
            <form method="POST" action="{{ route('adjudicacion.perdido', $proyecto) }}" class="space-y-2 p-4 bg-slate-50 rounded-lg"
                  onsubmit="return confirm('¿Marcar como perdido?');">
                @csrf
                <p class="text-sm font-semibold text-slate-700">Marcar como perdido</p>
                <textarea name="razon" rows="2" required minlength="5" placeholder="Razón" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"></textarea>
                <button class="rounded-md border border-rose-300 hover:bg-rose-50 text-rose-700 text-sm px-4 py-2">Marcar perdido</button>
            </form>
        </div>
    @endif

    @if ($proyecto->estado === 'adjudicado_pendiente')
        <form method="POST" action="{{ route('adjudicacion.firmar', $proyecto) }}" class="space-y-3 p-4 bg-emerald-50 rounded-lg">
            @csrf
            <p class="text-sm font-semibold text-slate-700">Registrar firma de OC (asigna DN)</p>
            <div class="grid md:grid-cols-2 gap-3">
                <label class="block">
                    <span class="text-xs text-slate-600">Fecha de firma *</span>
                    <input name="oc_firma_fecha" type="date" required value="{{ now()->toDateString() }}" class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                </label>
                <label class="block">
                    <span class="text-xs text-slate-600">Ruta documento OC firmada</span>
                    <input name="oc_path" placeholder="storage/ocs/oc-..." class="mt-1 w-full rounded-md border border-slate-200 px-3 py-2 text-sm" />
                </label>
            </div>
            <textarea name="comentario" rows="2" placeholder="Comentario (opcional)" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm"></textarea>
            <button class="rounded-md bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 py-2">Firmar OC y asignar DN</button>
        </form>
    @endif

    @if ($proyecto->estado === 'adjudicado_firmado')
        <div class="space-y-3">
            <p class="text-sm text-slate-600">
                DN asignado: <span class="font-mono font-semibold text-emerald-700">{{ $proyecto->dn_numero }}</span>.
                Antes de iniciar ejecución, levanta la <a href="{{ route('minutas.show', $proyecto) }}" class="text-gpt-600 hover:underline">minuta de entrega</a>.
            </p>
            <form method="POST" action="{{ route('adjudicacion.iniciar', $proyecto) }}"
                  onsubmit="return confirm('Iniciar ejecución abrirá el Libro de Proyecto y bloqueará nuevas modificaciones del CP. ¿Continuar?');">
                @csrf
                <button class="rounded-md bg-gpt-600 hover:bg-gpt-700 text-white text-sm px-4 py-2">Iniciar ejecución (abre Libro)</button>
            </form>
        </div>
    @endif
</section>
