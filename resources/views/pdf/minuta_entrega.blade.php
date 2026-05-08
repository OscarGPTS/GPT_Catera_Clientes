<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Minuta de Entrega CP→DN · {{ $p->cp_numero }}</title>
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
    .lista { padding-left: 16pt; }
    .lista li { margin-bottom: 4pt; }
    table.firmas { width: 100%; border-collapse: collapse; font-size: 9pt; margin-top: 14pt; }
    table.firmas th { background: #1f2937; color: white; padding: 5pt 4pt; text-align: left; }
    table.firmas td { padding: 6pt 4pt; border-bottom: 0.3pt solid #e2e8f0; }
    .badge { display: inline-block; padding: 1pt 4pt; border-radius: 2pt; font-size: 8pt; }
    .badge-firmada { background: #064e3b; color: white; }
    .badge-pendiente { background: #fef3c7; color: #92400e; }
    .footer { position: fixed; bottom: -40pt; left: 0; right: 0; text-align: center; font-size: 8pt; color: #94a3b8; }
</style>
</head>
<body>
    <div class="header">
        <div class="left">
            <h1>Minuta de Entrega</h1>
            <p style="margin: 0; font-size: 10pt; color: #6b7280;">Transición CP → DN</p>
        </div>
        <div class="right">
            <p style="margin: 0; font-size: 10pt;"><strong>{{ $p->cp_numero }}</strong></p>
            @if ($p->dn_numero)
                <p style="margin: 0; font-size: 10pt; color: #064e3b;"><strong>{{ $p->dn_numero }}</strong></p>
            @endif
            <p style="margin: 4pt 0 0 0; font-size: 9pt; color: #6b7280;">{{ $m->fecha_reunion?->format('d/m/Y') }}</p>
        </div>
    </div>

    <h2>Datos de la reunión</h2>
    <table class="meta">
        <tr><td class="label">Cliente</td><td>{{ $p->cliente?->razon_social }} ({{ $p->cliente?->alias_3letras }})</td></tr>
        <tr><td class="label">Proyecto</td><td>{{ $p->resumen_ejecutivo }}</td></tr>
        <tr><td class="label">Fecha / hora</td>
            <td>
                {{ $m->fecha_reunion?->format('d/m/Y') }}
                @if ($m->hora_inicio) · {{ $m->hora_inicio }} @endif
                @if ($m->hora_fin) – {{ $m->hora_fin }} @endif
            </td></tr>
        <tr><td class="label">Modalidad</td><td>{{ ucfirst($m->modalidad) }}</td></tr>
        <tr><td class="label">Estado</td>
            <td>
                @if ($m->status === 'firmada')
                    <span class="badge badge-firmada">FIRMADA</span> · {{ optional($m->firmado_at)->format('d/m/Y H:i') }}
                @else
                    <span class="badge badge-pendiente">BORRADOR / pendiente firmas</span>
                @endif
            </td></tr>
    </table>

    @if (! empty($m->orden_del_dia) && is_array($m->orden_del_dia))
        <h2>Orden del día</h2>
        <ol class="lista">
            @foreach ($m->orden_del_dia as $item)
                <li>{{ is_array($item) ? ($item['texto'] ?? '') : $item }}</li>
            @endforeach
        </ol>
    @endif

    @if (! empty($m->acuerdos) && is_array($m->acuerdos))
        <h2>Acuerdos</h2>
        <ol class="lista">
            @foreach ($m->acuerdos as $item)
                <li>
                    {{ is_array($item) ? ($item['texto'] ?? '') : $item }}
                    @if (is_array($item) && ! empty($item['responsable']))
                        <em style="color: #6b7280; font-size: 8pt;">— Responsable: {{ $item['responsable'] }}</em>
                    @endif
                    @if (is_array($item) && ! empty($item['fecha']))
                        <em style="color: #6b7280; font-size: 8pt;">· Fecha compromiso: {{ $item['fecha'] }}</em>
                    @endif
                </li>
            @endforeach
        </ol>
    @endif

    <h2>Participantes y firmas</h2>
    <table class="firmas">
        <thead>
            <tr>
                <th style="width: 35%;">Nombre</th>
                <th>Rol en la entrega</th>
                <th style="width: 22%;">Firma</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($m->participantes as $part)
                <tr>
                    <td>{{ $part->user?->name ?? '—' }}</td>
                    <td style="color: #6b7280;">{{ $part->rol_en_minuta ?? '—' }}</td>
                    <td>
                        @if ($part->firma_pendiente)
                            <span class="badge badge-pendiente">pendiente</span>
                        @else
                            <span class="badge badge-firmada">firmada {{ optional($part->firmado_at)->format('d/m/Y') }}</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Documento generado automáticamente por la plataforma GPT — {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
