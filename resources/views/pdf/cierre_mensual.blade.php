<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cierre {{ $c->tipo }} · {{ $c->periodoLabel() }}</title>
<style>
    @page { margin: 50px 40px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1f2937; }
    h1 { color: #b91c1c; font-size: 16pt; margin: 0 0 4pt 0; }
    h2 { font-size: 11pt; margin: 14pt 0 6pt 0; color: #1f2937; border-bottom: 1pt solid #cbd5e1; padding-bottom: 3pt; }
    .header { display: table; width: 100%; margin-bottom: 14pt; }
    .header .left, .header .right { display: table-cell; vertical-align: top; }
    .header .right { text-align: right; }
    table.meta { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 6pt; }
    table.meta td { padding: 3pt 6pt; border: 0.5pt solid #cbd5e1; }
    table.meta .label { background: #f1f5f9; font-weight: bold; width: 16%; }
    table.lineas { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
    table.lineas th { background: #1f2937; color: white; padding: 4pt; text-align: left; }
    table.lineas td { padding: 4pt; border-bottom: 0.3pt solid #e2e8f0; }
    .num { text-align: right; font-family: DejaVu Sans Mono, monospace; }
    .total { background: #f1f5f9; font-weight: bold; }
    .grand { background: #064e3b; color: white; font-size: 11pt; font-weight: bold; }
    .footer { position: fixed; bottom: -30pt; left: 0; right: 0; text-align: center; font-size: 7pt; color: #94a3b8; }
</style>
</head>
<body>
    <div class="header">
        <div class="left">
            <h1>Cierre {{ $c->tipo === 'contable_sat' ? 'Contable SAT' : 'Gerencial · Avance D1' }}</h1>
            <p style="margin: 0; font-size: 9pt; color: #6b7280;">GPT Services · Tech Energy Control S.A. de C.V.</p>
        </div>
        <div class="right">
            <p style="margin: 0; font-size: 12pt;"><strong>{{ $c->periodoLabel() }}</strong></p>
            <p style="margin: 4pt 0 0 0; font-size: 9pt;">status: <strong>{{ $c->status }}</strong></p>
        </div>
    </div>

    <table class="meta">
        <tr><td class="label">Tipo</td><td>{{ str_replace('_', ' ', $c->tipo) }}</td>
            <td class="label">Fecha corte</td><td>{{ optional($c->fecha_corte)->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Generado</td><td>{{ $c->generadoPor?->name ?? '—' }}</td>
            <td class="label">Aprobado</td><td>{{ $c->aprobadoPor?->name ?? '—' }} {{ $c->aprobado_at ? '· '.$c->aprobado_at->format('d/m/Y H:i') : '' }}</td></tr>
    </table>

    @php
        $seccionLabels = [
            'sat_base' => 'Sección A · Facturado / SAT (movimientos conciliados con factura)',
            'devengado' => 'Sección B · Devengado por avance (proyectos firmados, prorrateo días naturales)',
            'pipeline_ponderado' => 'Sección C · Pipeline ponderado (proyectos no firmados × probabilidad)',
        ];
    @endphp

    @foreach ($c->secciones as $sec)
        <h2>{{ $seccionLabels[$sec->codigo] ?? $sec->codigo }}</h2>
        @if ($sec->lineas->isEmpty())
            <p style="font-size: 9pt; color: #6b7280;">Sin líneas para este periodo.</p>
        @else
            <table class="lineas">
                <thead>
                    <tr>
                        <th>Proyecto</th>
                        <th>Cliente</th>
                        <th>Concepto</th>
                        <th class="num">% aplicado</th>
                        <th class="num">Monto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sec->lineas as $l)
                        <tr>
                            <td style="font-family: monospace;">{{ $l->proyecto?->cp_numero ?? '—' }}@if ($l->proyecto?->dn_numero) / {{ $l->proyecto->dn_numero }}@endif</td>
                            <td>{{ $l->proyecto?->cliente?->razon_social ?? '—' }}</td>
                            <td style="color: #6b7280;">{{ $l->observaciones }}</td>
                            <td class="num">{{ number_format((float) $l->porcentaje_aplicado * 100, 2) }}%</td>
                            <td class="num">${{ number_format((float) $l->monto, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total">
                        <td colspan="4">Total {{ str_replace('_', ' ', $sec->codigo) }}</td>
                        <td class="num">${{ number_format((float) $sec->total, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif
    @endforeach

    <table class="lineas" style="margin-top: 16pt;">
        <tr class="grand">
            <td colspan="4" style="padding: 6pt;">TOTAL GENERAL</td>
            <td class="num" style="padding: 6pt;">${{ number_format((float) $c->totalGeneral(), 2) }}</td>
        </tr>
    </table>

    @if ($c->observaciones)
        <h2>Observaciones</h2>
        <div style="padding: 8pt; background: #f8fafc; border-left: 3pt solid #6366f1; font-size: 9pt;">{{ $c->observaciones }}</div>
    @endif

    <div class="footer">Documento generado automáticamente — {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
