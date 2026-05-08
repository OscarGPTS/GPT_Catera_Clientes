<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>{{ $k->tipo === 'kom_interno' ? 'KOM Interno' : 'KOM con Cliente' }} · {{ $p->cp_numero }}</title>
<style>
    @page { margin: 60px 50px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1f2937; }
    h1 { color: #b91c1c; font-size: 18pt; margin: 0 0 4pt 0; }
    h2 { font-size: 11pt; margin: 14pt 0 6pt 0; color: #1f2937; border-bottom: 1pt solid #cbd5e1; padding-bottom: 3pt; }
    .header { display: table; width: 100%; margin-bottom: 18pt; }
    .header .left, .header .right { display: table-cell; vertical-align: top; }
    .header .right { text-align: right; }
    table.meta { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 8pt; }
    table.meta td { padding: 3pt 6pt; border: 0.5pt solid #cbd5e1; }
    table.meta .label { background: #f1f5f9; font-weight: bold; width: 22%; }
    table.parts { width: 100%; border-collapse: collapse; font-size: 9pt; margin-top: 6pt; }
    table.parts th { background: #1f2937; color: white; padding: 5pt 4pt; text-align: left; }
    table.parts td { padding: 5pt 4pt; border-bottom: 0.3pt solid #e2e8f0; }
    .text-block { white-space: pre-wrap; padding: 8pt; background: #f8fafc; border-left: 3pt solid #6366f1; font-size: 9pt; line-height: 1.4; }
    table.cron { width: 100%; border-collapse: collapse; font-size: 8pt; margin-top: 6pt; }
    table.cron th { background: #1e3a8a; color: white; padding: 4pt; text-align: left; }
    table.cron td { padding: 4pt; border-bottom: 0.3pt solid #e2e8f0; }
    .footer { position: fixed; bottom: -40pt; left: 0; right: 0; text-align: center; font-size: 8pt; color: #94a3b8; }
</style>
</head>
<body>
    <div class="header">
        <div class="left">
            <h1>{{ $k->tipo === 'kom_interno' ? 'KOM Interno' : 'KOM con Cliente' }}</h1>
            <p style="margin: 0; font-size: 9pt; color: #6b7280;">Kick-Off Meeting · GPT Services</p>
        </div>
        <div class="right">
            <p style="margin: 0; font-size: 11pt;"><strong>{{ $p->cp_numero }}</strong>
                @if ($p->dn_numero) / <span style="color: #064e3b;"><strong>{{ $p->dn_numero }}</strong></span> @endif</p>
            <p style="margin: 4pt 0 0 0; font-size: 9pt; color: #6b7280;">{{ $k->fecha?->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <h2>Datos del proyecto</h2>
    <table class="meta">
        <tr><td class="label">Cliente</td><td>{{ $p->cliente?->razon_social ?? '—' }} ({{ $p->cliente?->alias_3letras }})</td></tr>
        <tr><td class="label">Sublínea</td><td>{{ $p->sublinea?->codigo }} — {{ $p->sublinea?->nombre }}</td></tr>
        <tr><td class="label">Resumen</td><td>{{ $p->resumen_ejecutivo }}</td></tr>
    </table>

    @if ($k->agenda)
        <h2>Agenda</h2>
        <div class="text-block">{{ $k->agenda }}</div>
    @endif

    @if (! empty($k->participantes) && is_array($k->participantes))
        <h2>Participantes</h2>
        <table class="parts">
            <thead>
                <tr><th>Nombre</th><th>Rol</th><th>Empresa</th></tr>
            </thead>
            <tbody>
                @foreach ($k->participantes as $part)
                    <tr>
                        <td>{{ $part['nombre'] ?? '' }}</td>
                        <td style="color: #6b7280;">{{ $part['rol'] ?? '—' }}</td>
                        <td style="color: #6b7280;">{{ $part['empresa'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($k->minuta)
        <h2>Minuta · acuerdos</h2>
        <div class="text-block">{{ $k->minuta }}</div>
    @endif

    @if ($k->cronograma)
        <h2>Cronograma adjunto · v{{ $k->cronograma->version }}</h2>
        <table class="cron">
            <thead>
                <tr>
                    <th style="width: 8%;">Código</th>
                    <th>Actividad</th>
                    <th style="width: 14%;">Inicio</th>
                    <th style="width: 14%;">Fin</th>
                    <th style="width: 10%;">Avance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($k->cronograma->actividades as $act)
                    <tr>
                        <td style="font-family: monospace;">{{ $act->codigo ?? '—' }}</td>
                        <td>{{ $act->nombre }}</td>
                        <td>{{ optional($act->fecha_inicio_planeada)->format('d/m/Y') }}</td>
                        <td>{{ optional($act->fecha_fin_planeada)->format('d/m/Y') }}</td>
                        <td>{{ number_format((float) $act->porcentaje_avance, 0) }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Documento generado automáticamente por la plataforma GPT — {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
