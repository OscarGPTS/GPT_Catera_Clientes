<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Cotización {{ $p->cp_numero }} v{{ $c->version }}</title>
<style>
    @page { margin: 60px 50px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1f2937; }
    h1 { color: #b91c1c; font-size: 18pt; margin: 0 0 4pt 0; }
    h2 { font-size: 11pt; margin: 14pt 0 6pt 0; color: #1f2937; border-bottom: 1pt solid #cbd5e1; padding-bottom: 3pt; }
    .header { display: table; width: 100%; margin-bottom: 18pt; }
    .header .left, .header .right { display: table-cell; vertical-align: top; }
    .header .right { text-align: right; }
    .meta-table { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 8pt; }
    .meta-table td { padding: 3pt 6pt; border: 0.5pt solid #cbd5e1; }
    .meta-table .label { background: #f1f5f9; font-weight: bold; width: 22%; }
    table.partidas { width: 100%; border-collapse: collapse; font-size: 9pt; margin-top: 6pt; }
    table.partidas th { background: #1f2937; color: white; padding: 5pt 4pt; text-align: left; font-weight: 600; }
    table.partidas td { padding: 5pt 4pt; border-bottom: 0.3pt solid #e2e8f0; }
    table.partidas .num { text-align: right; font-family: DejaVu Sans Mono, monospace; }
    table.totales { width: 50%; margin-left: 50%; margin-top: 12pt; border-collapse: collapse; font-size: 9pt; }
    table.totales td { padding: 4pt 8pt; border-bottom: 0.3pt solid #e2e8f0; }
    table.totales .label { background: #f1f5f9; font-weight: 600; }
    table.totales .num { text-align: right; font-family: DejaVu Sans Mono, monospace; }
    table.totales .grand { background: #064e3b; color: white; font-size: 11pt; font-weight: bold; }
    .observaciones { margin-top: 14pt; padding: 10pt; background: #f8fafc; border-left: 3pt solid #b91c1c; font-size: 9pt; }
    .footer { position: fixed; bottom: -40pt; left: 0; right: 0; text-align: center; font-size: 8pt; color: #94a3b8; }
</style>
</head>
<body>
    <div class="header">
        <div class="left">
            <h1>GPT Services</h1>
            <p style="margin: 0; font-size: 9pt; color: #6b7280;">Tech Energy Control S.A. de C.V.</p>
        </div>
        <div class="right">
            <p style="margin: 0; font-size: 14pt; color: #1f2937;"><strong>COTIZACIÓN</strong></p>
            <p style="margin: 0; font-size: 10pt; color: #6b7280;">{{ $p->cp_numero }} · v{{ $c->version }}</p>
            @if ($c->fecha_emision)
                <p style="margin: 0; font-size: 9pt; color: #6b7280;">Emitida {{ $c->fecha_emision->format('d/m/Y') }}</p>
            @endif
        </div>
    </div>

    <h2>Datos del proyecto</h2>
    <table class="meta-table">
        <tr><td class="label">Cliente</td><td>{{ $p->cliente?->razon_social ?? '—' }} ({{ $p->cliente?->alias_3letras }})</td></tr>
        @if ($p->usuario_final)
            <tr><td class="label">Usuario final</td><td>{{ $p->usuario_final }}</td></tr>
        @endif
        <tr><td class="label">Sublínea</td><td>{{ $p->sublinea?->codigo }} — {{ $p->sublinea?->nombre }}</td></tr>
        <tr><td class="label">Sector</td><td>{{ $p->sector ?? '—' }}</td></tr>
        <tr><td class="label">Resumen</td><td>{{ $p->resumen_ejecutivo }}</td></tr>
    </table>

    <h2>Desglose de partidas</h2>
    <table class="partidas">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th>Descripción</th>
                <th style="width: 10%;">Unidad</th>
                <th style="width: 10%; text-align: right;">Cantidad</th>
                <th style="width: 14%; text-align: right;">$ Unitario</th>
                <th style="width: 14%; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($c->partidas as $partida)
                <tr>
                    <td class="num">{{ $partida->numero_partida }}</td>
                    <td>{{ $partida->descripcion }}</td>
                    <td>{{ $partida->unidad ?? '—' }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format((float) $partida->cantidad, 4), '0'), '.') }}</td>
                    <td class="num">${{ number_format((float) $partida->costo_unitario, 4) }}</td>
                    <td class="num">${{ number_format((float) $partida->costo_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totales">
        <tr><td class="label">Costo directo</td><td class="num">${{ number_format((float) $c->costo_directo, 2) }}</td></tr>
        <tr><td class="label">Indirectos ({{ number_format((float) $c->factor_indirectos * 100, 2) }}%)</td>
            <td class="num">${{ number_format((float) $c->costo_directo * (float) $c->factor_indirectos, 2) }}</td></tr>
        <tr><td class="label">Admin ({{ number_format((float) $c->factor_admin * 100, 2) }}%)</td>
            <td class="num">${{ number_format(((float) $c->costo_directo + (float) $c->costo_directo * (float) $c->factor_indirectos) * (float) $c->factor_admin, 2) }}</td></tr>
        <tr><td class="label">Utilidad ({{ number_format((float) $c->factor_utilidad * 100, 2) }}%)</td>
            <td class="num">${{ number_format((float) $c->precio_venta_final - ((float) $c->precio_venta_final / (1 + (float) $c->factor_utilidad)), 2) }}</td></tr>
        <tr class="grand"><td>Precio de venta</td><td class="num">${{ number_format((float) $c->precio_venta_final, 2) }} {{ $c->moneda }}</td></tr>
        <tr><td class="label">Margen neto</td><td class="num">{{ number_format((float) $c->margen_neto * 100, 2) }}%</td></tr>
    </table>

    @if ($c->observaciones)
        <div class="observaciones">
            <strong>Observaciones:</strong><br>
            {!! nl2br(e($c->observaciones)) !!}
        </div>
    @endif

    <div class="footer">
        Documento generado automáticamente por la plataforma GPT — {{ now()->format('d/m/Y H:i') }} · {{ $c->generadoPor?->name ?? 'sistema' }}
    </div>
</body>
</html>
