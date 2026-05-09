<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte Ejecutivo {{ $año }}</title>
<style>
    @page { margin: 50px 40px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5pt; color: #1f2937; }
    h1 { color: #b91c1c; font-size: 18pt; margin: 0 0 4pt 0; }
    h2 { font-size: 11pt; margin: 14pt 0 6pt 0; color: #1f2937; border-bottom: 1pt solid #cbd5e1; padding-bottom: 3pt; }
    .header { margin-bottom: 14pt; }
    .kpis { display: table; width: 100%; margin-bottom: 14pt; }
    .kpis .card { display: table-cell; width: 25%; vertical-align: top; padding: 0 4pt; }
    .kpi-box { padding: 10pt; border: 1pt solid #cbd5e1; border-radius: 4pt; text-align: center; }
    .kpi-box .label { font-size: 8pt; color: #6b7280; text-transform: uppercase; }
    .kpi-box .value { font-size: 16pt; font-weight: bold; color: #1f2937; margin-top: 4pt; }
    .kpi-box .sub { font-size: 8pt; color: #6b7280; margin-top: 2pt; }
    table.tab { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
    table.tab th { background: #1f2937; color: white; padding: 5pt; text-align: left; }
    table.tab td { padding: 5pt; border-bottom: 0.3pt solid #e2e8f0; }
    .num { text-align: right; font-family: DejaVu Sans Mono, monospace; }
    .alerta { padding: 10pt; background: #fef3c7; border-left: 3pt solid #b91c1c; font-size: 9pt; margin-top: 8pt; }
    .footer { position: fixed; bottom: -30pt; left: 0; right: 0; text-align: center; font-size: 7pt; color: #94a3b8; }
</style>
</head>
<body>
    <div class="header">
        <h1>Reporte Ejecutivo {{ $año }}</h1>
        <p style="margin: 0; font-size: 9pt; color: #6b7280;">GPT Services · Tech Energy Control S.A. de C.V. · {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <h2>KPIs principales</h2>
    <div class="kpis">
        <div class="card">
            <div class="kpi-box">
                <div class="label">Pipeline</div>
                <div class="value">${{ number_format((float) $kpis['pipeline_monto'], 0) }}</div>
                <div class="sub">{{ $kpis['pipeline_conteo'] }} proyectos</div>
            </div>
        </div>
        <div class="card">
            <div class="kpi-box">
                <div class="label">Adjudicado</div>
                <div class="value" style="color: #16a34a;">${{ number_format((float) $kpis['adjudicado_monto'], 0) }}</div>
                <div class="sub">{{ $kpis['adjudicado_conteo'] }} proyectos</div>
            </div>
        </div>
        <div class="card">
            <div class="kpi-box">
                <div class="label">Hit rate · monto</div>
                <div class="value">{{ number_format((float) $kpis['hit_rate_monto'] * 100, 1) }}%</div>
                <div class="sub">conteo: {{ number_format((float) $kpis['hit_rate_conteo'] * 100, 1) }}%</div>
            </div>
        </div>
        <div class="card">
            <div class="kpi-box">
                <div class="label">SEDENA share</div>
                <div class="value" style="color: {{ $kpis['sedena_share'] >= $kpis['umbral_alerta'] ? '#b91c1c' : '#1f2937' }};">{{ number_format((float) $kpis['sedena_share'] * 100, 1) }}%</div>
                <div class="sub">umbral: {{ number_format($kpis['umbral_alerta'] * 100, 0) }}%</div>
            </div>
        </div>
    </div>

    @if (! empty($kpis['alertas_concentracion']) && count($kpis['alertas_concentracion']) > 0)
        <div class="alerta">
            <strong>⚠ Alertas de concentración (clientes ≥ {{ number_format($kpis['umbral_alerta'] * 100, 0) }}% del pipeline):</strong>
            <ul style="margin: 4pt 0 0 16pt;">
                @foreach ($kpis['alertas_concentracion'] as $a)
                    <li><strong>{{ $a['cliente'] }}</strong> · ${{ number_format((float) $a['monto'], 2) }} · {{ number_format($a['porcentaje'] * 100, 2) }}%</li>
                @endforeach
            </ul>
        </div>
    @endif

    <h2>Concentración del pipeline por cliente</h2>
    <table class="tab">
        <thead><tr><th>Cliente</th><th class="num">Monto</th><th class="num">% del total</th></tr></thead>
        <tbody>
            @php $total = (float) $kpis['pipeline_monto']; @endphp
            @foreach ($kpis['concentracion_clientes'] as $alias => $monto)
                <tr>
                    <td><strong>{{ $alias }}</strong></td>
                    <td class="num">${{ number_format((float) $monto, 2) }}</td>
                    <td class="num">{{ $total > 0 ? number_format((float) $monto / $total * 100, 2).'%' : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Proyectos por estado</h2>
    @php
        $porEstado = $proyectos->groupBy('estado');
    @endphp
    <table class="tab">
        <thead>
            <tr><th>Estado</th><th class="num">Cantidad</th><th class="num">Monto total</th></tr>
        </thead>
        <tbody>
            @foreach ($porEstado as $estado => $grupo)
                <tr>
                    <td>{{ str_replace('_', ' ', $estado) }}</td>
                    <td class="num">{{ $grupo->count() }}</td>
                    <td class="num">${{ number_format((float) $grupo->sum('monto_preliminar'), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Top 10 proyectos por monto</h2>
    <table class="tab">
        <thead>
            <tr><th>CP/DN</th><th>Cliente</th><th>Estado</th><th class="num">Monto</th></tr>
        </thead>
        <tbody>
            @foreach ($proyectos->sortByDesc('monto_preliminar')->take(10) as $p)
                <tr>
                    <td style="font-family: monospace;">{{ $p->cp_numero }}@if ($p->dn_numero) / {{ $p->dn_numero }}@endif</td>
                    <td>{{ $p->cliente?->razon_social }}</td>
                    <td>{{ str_replace('_', ' ', $p->estado) }}</td>
                    <td class="num">${{ number_format((float) ($p->monto_preliminar ?? 0), 2) }} {{ $p->moneda }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">Reporte generado — {{ now()->format('d/m/Y H:i') }} · GPT Services</div>
</body>
</html>
