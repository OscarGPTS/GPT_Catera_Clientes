<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Solicitud de viáticos · {{ $p->cp_numero }} · #{{ $s->id }}</title>
<style>
    @page { margin: 60px 50px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1f2937; }
    h1 { color: #b91c1c; font-size: 16pt; margin: 0 0 4pt 0; }
    h2 { font-size: 11pt; margin: 12pt 0 6pt 0; color: #1f2937; border-bottom: 1pt solid #cbd5e1; padding-bottom: 3pt; }
    .header { display: table; width: 100%; margin-bottom: 14pt; }
    .header .left, .header .right { display: table-cell; vertical-align: top; }
    .header .right { text-align: right; }
    table.meta { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 8pt; }
    table.meta td { padding: 3pt 6pt; border: 0.5pt solid #cbd5e1; }
    table.meta .label { background: #f1f5f9; font-weight: bold; width: 22%; }
    table.tabla { width: 100%; border-collapse: collapse; font-size: 9pt; }
    table.tabla th { background: #1f2937; color: white; padding: 5pt; text-align: left; }
    table.tabla td { padding: 5pt; border-bottom: 0.3pt solid #e2e8f0; }
    .num { text-align: right; font-family: DejaVu Sans Mono, monospace; }
    table.firmas { width: 100%; margin-top: 24pt; }
    table.firmas td { width: 33%; text-align: center; padding: 8pt; vertical-align: bottom; }
    table.firmas .linea { border-top: 0.5pt solid #1f2937; padding-top: 4pt; font-size: 8pt; }
    .footer { position: fixed; bottom: -40pt; left: 0; right: 0; text-align: center; font-size: 8pt; color: #94a3b8; }
</style>
</head>
<body>
    @php
        $estimadoTotal = $s->partidas->sum('monto_estimado');
        $realTotal = $s->partidas->sum('monto_real');
    @endphp

    <div class="header">
        <div class="left">
            <h1>Solicitud de Viáticos</h1>
            <p style="margin: 0; font-size: 9pt; color: #6b7280;">FO-GPT-PYT-VIA-01</p>
        </div>
        <div class="right">
            <p style="margin: 0; font-size: 11pt;"><strong>{{ $p->cp_numero }}</strong>
                @if ($p->dn_numero) / <strong style="color:#064e3b">{{ $p->dn_numero }}</strong> @endif</p>
            <p style="margin: 4pt 0 0 0; font-size: 9pt; color: #6b7280;">Solicitud #{{ $s->id }} · status: <strong>{{ $s->status }}</strong></p>
        </div>
    </div>

    <table class="meta">
        <tr><td class="label">Cliente / Proyecto</td><td>{{ $p->cliente?->razon_social }} — {{ $p->resumen_ejecutivo }}</td></tr>
        <tr><td class="label">Periodo</td><td>{{ $s->periodo_inicio->format('d/m/Y') }} – {{ $s->periodo_fin->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Solicitante</td><td>{{ $s->solicitante?->name }}</td></tr>
        @if ($s->justificacion)
            <tr><td class="label">Justificación</td><td>{{ $s->justificacion }}</td></tr>
        @endif
    </table>

    <h2>Personal beneficiario</h2>
    <table class="tabla">
        <thead><tr><th>Nombre</th><th class="num">Días</th></tr></thead>
        <tbody>
            @foreach ($s->personal as $p2)
                <tr><td>{{ $p2->user?->name ?? '—' }}</td><td class="num">{{ $p2->dias }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Partidas presupuestadas</h2>
    <table class="tabla">
        <thead><tr><th>Concepto</th><th class="num">Estimado</th><th class="num">Real</th><th>Observaciones</th></tr></thead>
        <tbody>
            @foreach ($s->partidas as $part)
                <tr>
                    <td>{{ ucfirst($part->concepto) }}</td>
                    <td class="num">${{ number_format((float) $part->monto_estimado, 2) }}</td>
                    <td class="num">{{ $part->monto_real !== null ? '$'.number_format((float) $part->monto_real, 2) : '—' }}</td>
                    <td style="color:#6b7280;">{{ $part->observaciones ?? '' }}</td>
                </tr>
            @endforeach
            <tr style="background:#1f2937;color:white;font-weight:bold;">
                <td>TOTAL</td>
                <td class="num">${{ number_format((float) $estimadoTotal, 2) }}</td>
                <td class="num">{{ $realTotal > 0 ? '$'.number_format((float) $realTotal, 2) : '—' }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <table class="firmas">
        <tr>
            <td>
                <div class="linea">
                    Solicitante<br>{{ $s->solicitante?->name }}
                </div>
            </td>
            <td>
                <div class="linea">
                    Servicios Generales<br>
                    {{ $s->aprobadorServGrales?->name ?? '— pendiente —' }}
                </div>
            </td>
            <td>
                <div class="linea">
                    Dirección<br>
                    {{ $s->aprobadorDireccion?->name ?? '— pendiente —' }}
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">Documento generado automáticamente — {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
