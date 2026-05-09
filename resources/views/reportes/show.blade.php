<x-layouts.app :title="'Reporte · ' . $reporte->semana_inicio->format('Y-m-d')">
    <a href="{{ route('reportes.index', $proyecto) }}" class="text-sm text-slate-500 hover:underline">← Reportes</a>

    <div class="flex items-start justify-between mt-2 mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Reporte semanal</h1>
            <p class="text-slate-600 text-sm mt-1">
                <span class="font-mono">{{ $proyecto->cp_numero }}</span>
                · {{ $reporte->semana_inicio->format('Y-m-d') }} → {{ $reporte->semana_fin->format('Y-m-d') }}
                · generado {{ optional($reporte->generado_at)->diffForHumans() }}
            </p>
        </div>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('reportes.regenerar', [$proyecto, $reporte]) }}">
                @csrf
                @method('PATCH')
                <button class="rounded-md bg-amber-600 hover:bg-amber-700 text-white text-sm px-4 py-2">Regenerar</button>
            </form>
            <a href="{{ route('reportes.pdf', [$proyecto, $reporte]) }}" class="rounded-md bg-rose-600 hover:bg-rose-700 text-white text-sm px-4 py-2">Descargar PDF</a>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl p-6 prose prose-sm max-w-none">
            {!! $reporte->contenido_html !!}
        </div>

        <aside class="space-y-4">
            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <h3 class="text-xs uppercase font-semibold text-slate-500 mb-3">Envío</h3>
                @if ($reporte->enviado_at)
                    <p class="text-sm text-emerald-700">Enviado {{ $reporte->enviado_at->format('Y-m-d H:i') }}</p>
                    <p class="text-xs text-slate-500 mt-1">Destinatarios: {{ implode(', ', $reporte->recipients ?? []) }}</p>
                @endif
                <form method="POST" action="{{ route('reportes.enviar', [$proyecto, $reporte]) }}" class="space-y-2 mt-3">
                    @csrf
                    <textarea name="recipients" rows="3" placeholder="email1@cliente.com, email2@gpt.com" class="w-full rounded-md border border-slate-200 px-3 py-2 text-sm">{{ implode(', ', $reporte->recipients ?? []) }}</textarea>
                    <button class="rounded-md bg-emerald-600 hover:bg-emerald-700 text-white text-sm px-4 py-2 w-full">{{ $reporte->enviado_at ? 'Reenviar' : 'Marcar enviado' }}</button>
                </form>
                <p class="text-[10px] text-slate-400 mt-2">TODO: integrar con mailer real (M10).</p>
            </div>
        </aside>
    </div>
</x-layouts.app>
