<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Post-Mortem · {{ $p->cp_numero }}</title>
<style>
    @page { margin: 60px 50px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1f2937; line-height: 1.4; }
    h1 { color: #b91c1c; font-size: 16pt; margin: 0 0 4pt 0; }
    h2 { font-size: 11pt; margin: 12pt 0 6pt 0; color: #1f2937; border-bottom: 1pt solid #cbd5e1; padding-bottom: 3pt; }
    .header { display: table; width: 100%; margin-bottom: 14pt; }
    .header .left, .header .right { display: table-cell; vertical-align: top; }
    .header .right { text-align: right; }
    table.meta { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 8pt; }
    table.meta td { padding: 3pt 6pt; border: 0.5pt solid #cbd5e1; }
    table.meta .label { background: #f1f5f9; font-weight: bold; width: 24%; }
    table.kpi { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 8pt; }
    table.kpi th { background: #1f2937; color: white; padding: 5pt; text-align: left; }
    table.kpi td { padding: 5pt; border-bottom: 0.3pt solid #e2e8f0; }
    .num { text-align: right; font-family: DejaVu Sans Mono, monospace; }
    .text-block { white-space: pre-wrap; padding: 8pt; background: #f8fafc; border-left: 3pt solid #6366f1; font-size: 9pt; }
    .footer { position: fixed; bottom: -40pt; left: 0; right: 0; text-align: center; font-size: 8pt; color: #94a3b8; }
</style>
</head>
<body>
    @php
        $sign = fn ($v) => $v > 0 ? '+' : '';
        $colorD = fn ($v) => $v > 0.05 ? '#b91c1c' : ($v < -0.05 ? '#16a34a' : '#1f2937');
    @endphp

    <div class="header">
        <div class="left">
            <h1>Post-Mortem</h1>
            <p style="margin: 0; font-size: 9pt; color: #6b7280;">Lecciones aprendidas y desviaciones</p>
        </div>
        <div class="right">
            <p style="margin: 0; font-size: 11pt;"><strong>{{ $p->cp_numero }}</strong>
                @if ($p->dn_numero) / <strong style="color:#064e3b">{{ $p->dn_numero }}</strong> @endif</p>
            <p style="margin: 4pt 0 0 0; font-size: 9pt;">{{ $pm->fecha_sesion?->format('d/m/Y') }}</p>
        </div>
    </div>

    <table class="meta">
        <tr><td class="label">Cliente</td><td>{{ $p->cliente?->razon_social }}</td></tr>
        <tr><td class="label">Proyecto</td><td>{{ $p->resumen_ejecutivo }}</td></tr>
    </table>

    <h2>KPIs y desviaciones</h2>
    <table class="kpi">
        <thead>
            <tr><th>Indicador</th><th>Planeado</th><th>Real</th><th>Desviación</th></tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Costo</strong></td>
                <td class="num">${{ number_format((float) ($pm->presupuesto_planeado ?? 0), 2) }}</td>
                <td class="num">${{ number_format((float) ($pm->presupuesto_real ?? 0), 2) }}</td>
                <td class="num" style="color: {{ $colorD((float) $pm->desviaciones_costo) }};">
                    @if ($pm->desviaciones_costo !== null)
                        {{ $sign((float) $pm->desviaciones_costo) }}{{ number_format((float) $pm->desviaciones_costo * 100, 2) }}%
                    @else — @endif
                </td>
            </tr>
            <tr>
                <td><strong>Tiempo</strong></td>
                <td class="num">{{ $p->fecha_inicio_planeada?->format('d/m/Y') }} → {{ $p->fecha_fin_planeada?->format('d/m/Y') }}</td>
                <td class="num">— </td>
                <td class="num" style="color: {{ $colorD((float) $pm->desviaciones_tiempo) }};">
                    @if ($pm->desviaciones_tiempo !== null)
                        {{ $sign((float) $pm->desviaciones_tiempo) }}{{ number_format((float) $pm->desviaciones_tiempo * 100, 2) }}%
                    @else — @endif
                </td>
            </tr>
            <tr>
                <td><strong>Calidad (dossier)</strong></td>
                <td class="num">100%</td>
                <td class="num">{{ number_format((float) $pm->desviaciones_calidad * 100, 1) }}%</td>
                <td class="num">— </td>
            </tr>
        </tbody>
    </table>

    @if (! empty($pm->participantes))
        <h2>Participantes</h2>
        <table class="kpi">
            @foreach ($pm->participantes as $part)
                <tr>
                    <td>{{ is_array($part) ? ($part['nombre'] ?? '') : $part }}</td>
                    <td style="color:#6b7280;">{{ is_array($part) ? ($part['rol'] ?? '') : '' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($pm->lecciones_aprendidas)
        <h2>Lecciones aprendidas</h2>
        <div class="text-block">{{ $pm->lecciones_aprendidas }}</div>
    @endif

    @if (! empty($pm->recomendaciones_mejora))
        <h2>Recomendaciones de mejora</h2>
        <ul>
            @foreach ($pm->recomendaciones_mejora as $rec)
                <li>
                    {{ is_array($rec) ? ($rec['texto'] ?? '') : $rec }}
                    @if (is_array($rec) && ! empty($rec['responsable']))
                        — <em style="color:#6b7280;">responsable: {{ $rec['responsable'] }}</em>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif

    <div class="footer">Documento generado automáticamente — {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
