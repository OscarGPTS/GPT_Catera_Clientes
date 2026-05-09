<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ficha · {{ $p->cp_numero }}</title>
<style>
    @page { margin: 50px 40px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9.5pt; color: #1f2937; }
    .header { display: table; width: 100%; margin-bottom: 14pt; border-bottom: 2pt solid #b91c1c; padding-bottom: 8pt; }
    .header .left, .header .right { display: table-cell; vertical-align: top; }
    .header .right { text-align: right; }
    h1 { color: #b91c1c; font-size: 16pt; margin: 0 0 4pt 0; }
    h2 { font-size: 10pt; margin: 12pt 0 4pt 0; color: #1f2937; text-transform: uppercase; letter-spacing: 0.5pt; }
    .badge { display: inline-block; padding: 2pt 6pt; border-radius: 2pt; font-size: 8pt; font-weight: bold; background: #f1f5f9; color: #1f2937; }
    table.kv { width: 100%; border-collapse: collapse; }
    table.kv td { padding: 4pt 6pt; border-bottom: 0.3pt solid #e2e8f0; vertical-align: top; }
    table.kv .label { background: #f8fafc; font-weight: bold; width: 28%; color: #6b7280; font-size: 8.5pt; }
    .grid-2 { display: table; width: 100%; }
    .grid-2 .col { display: table-cell; width: 49%; vertical-align: top; padding-right: 8pt; }
    .grid-2 .col + .col { padding-right: 0; padding-left: 8pt; }
    .resumen { background: #f8fafc; padding: 8pt; border-left: 3pt solid #b91c1c; font-size: 9pt; line-height: 1.4; }
    .num { text-align: right; font-family: DejaVu Sans Mono, monospace; }
    .timeline { font-size: 8.5pt; }
    .timeline .item { padding: 3pt 0; border-bottom: 0.3pt solid #e2e8f0; }
    .footer { position: fixed; bottom: -30pt; left: 0; right: 0; text-align: center; font-size: 7pt; color: #94a3b8; }
</style>
</head>
<body>
    <div class="header">
        <div class="left">
            <h1>{{ $p->cp_numero ?? '—' }}@if ($p->dn_numero) <span style="color: #064e3b;">/ {{ $p->dn_numero }}</span>@endif</h1>
            <p style="margin: 0; font-size: 11pt; color: #1f2937;">{{ $p->cliente?->razon_social ?? '—' }}@if ($p->usuario_final) · usuario final {{ $p->usuario_final }}@endif</p>
            <p style="margin: 0; font-size: 9pt; color: #6b7280;">{{ $p->sublinea?->codigo }} — {{ $p->sublinea?->nombre }} · {{ $p->sector }}</p>
        </div>
        <div class="right">
            <p style="margin: 0;"><span class="badge" style="background:#b91c1c; color:white;">{{ str_replace('_', ' ', $p->estado) }}</span></p>
            <p style="margin: 4pt 0 0 0; font-size: 8pt; color: #6b7280;">Año {{ $p->año }}</p>
            @if ($p->tech_reference)
                <p style="margin: 0; font-family: monospace; font-size: 8pt; color: #6b7280;">{{ $p->tech_reference }}</p>
            @endif
        </div>
    </div>

    <h2>Resumen ejecutivo</h2>
    <div class="resumen">{{ $p->resumen_ejecutivo ?? '—' }}</div>

    <div class="grid-2">
        <div class="col">
            <h2>Plazo y monto</h2>
            <table class="kv">
                <tr><td class="label">Inicio planeado</td><td>{{ optional($p->fecha_inicio_planeada)->format('d/m/Y') ?? '—' }}</td></tr>
                <tr><td class="label">Fin planeado</td><td>{{ optional($p->fecha_fin_planeada)->format('d/m/Y') ?? '—' }}</td></tr>
                <tr><td class="label">Distribución</td><td>{{ $p->metodo_distribucion_plurianual }}</td></tr>
                <tr><td class="label">Monto preliminar</td><td class="num"><strong>${{ number_format((float) ($p->monto_preliminar ?? 0), 2) }} {{ $p->moneda }}</strong></td></tr>
                @if ($cotizacion)
                    <tr><td class="label">Cotización vigente</td><td>v{{ $cotizacion->version }} · margen {{ number_format((float) $cotizacion->margen_neto * 100, 2) }}%</td></tr>
                @endif
            </table>
        </div>
        <div class="col">
            <h2>Equipo asignado</h2>
            <table class="kv">
                <tr><td class="label">Director DN</td><td>{{ $p->directorDn?->name ?? '—' }}</td></tr>
                <tr><td class="label">Gerente proyectos</td><td>{{ $p->gerenteProyectos?->name ?? '—' }}</td></tr>
                <tr><td class="label">Gerente operaciones</td><td>{{ $p->gerenteOperaciones?->name ?? '—' }}</td></tr>
                <tr><td class="label">Ing. costos</td><td>{{ $p->ingenieroCostos?->name ?? '—' }}</td></tr>
                <tr><td class="label">Ing. proyectos</td><td>{{ $p->ingenieroProyectos?->name ?? '—' }}</td></tr>
                <tr><td class="label">Trainee</td><td>{{ $p->trainee?->name ?? '—' }}</td></tr>
            </table>
        </div>
    </div>

    @if ($p->eventos->isNotEmpty())
        <h2>Timeline (últimos 15 eventos)</h2>
        <div class="timeline">
            @foreach ($p->eventos as $ev)
                <div class="item">
                    <strong>{{ $ev->created_at?->format('d/m/Y H:i') }}</strong>
                    · {{ str_replace('_', ' ', $ev->tipo) }}
                    · {{ $ev->user?->name ?? 'sistema' }}
                    @if ($ev->estado_anterior && $ev->estado_anterior !== $ev->estado_nuevo)
                        · <span style="color: #6b7280;">{{ $ev->estado_anterior }} → {{ $ev->estado_nuevo }}</span>
                    @endif
                    @if ($ev->comentario)
                        <br><span style="color: #6b7280; font-size: 8pt;">{{ $ev->comentario }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="footer">Ficha generada — {{ now()->format('d/m/Y H:i') }} · GPT Services</div>
</body>
</html>
