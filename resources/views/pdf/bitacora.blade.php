<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Bitácora · {{ $p->cp_numero }} · {{ $b->fecha->format('Y-m-d') }}</title>
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
    .text-block { white-space: pre-wrap; padding: 8pt; background: #f8fafc; border-left: 3pt solid #6366f1; font-size: 10pt; line-height: 1.45; }
    table.lista { width: 100%; border-collapse: collapse; font-size: 9pt; }
    table.lista td { padding: 4pt; border-bottom: 0.3pt solid #e2e8f0; }
    .vobo { margin-top: 14pt; padding: 10pt; border: 1pt solid #16a34a; background: #f0fdf4; }
    .footer { position: fixed; bottom: -40pt; left: 0; right: 0; text-align: center; font-size: 8pt; color: #94a3b8; }
</style>
</head>
<body>
    <div class="header">
        <div class="left">
            <h1>Bitácora Diaria</h1>
            <p style="margin: 0; font-size: 9pt; color: #6b7280;">GPT Services</p>
        </div>
        <div class="right">
            <p style="margin: 0; font-size: 11pt;"><strong>{{ $p->cp_numero }}</strong>
                @if ($p->dn_numero) / <strong style="color:#064e3b">{{ $p->dn_numero }}</strong> @endif</p>
            <p style="margin: 4pt 0 0 0; font-size: 11pt;"><strong>{{ $b->fecha->format('d/m/Y') }}</strong></p>
        </div>
    </div>

    <table class="meta">
        <tr><td class="label">Cliente</td><td>{{ $p->cliente?->razon_social }}</td></tr>
        <tr><td class="label">Proyecto</td><td>{{ $p->resumen_ejecutivo }}</td></tr>
        <tr><td class="label">Cargado por</td><td>{{ $b->cargadoPor?->name }} · {{ $b->created_at->format('d/m/Y H:i') }}</td></tr>
    </table>

    <h2>Relación de actividades</h2>
    <div class="text-block">{{ $b->relacion_actividades }}</div>

    @if (! empty($b->personal_gpt))
        <h2>Personal GPT en sitio</h2>
        <table class="lista">
            @foreach ($b->personal_gpt as $persona)
                <tr><td>{{ is_array($persona) ? ($persona['nombre'] ?? '') : $persona }}</td>
                    <td style="color:#6b7280;">{{ is_array($persona) ? ($persona['rol'] ?? '') : '' }}</td></tr>
            @endforeach
        </table>
    @endif

    @if (! empty($b->equipos_en_sitio))
        <h2>Equipos en sitio</h2>
        <table class="lista">
            @foreach ($b->equipos_en_sitio as $eq)
                <tr><td>{{ is_array($eq) ? ($eq['nombre'] ?? '') : $eq }}</td>
                    <td style="color:#6b7280;">{{ is_array($eq) ? ($eq['cantidad'] ?? '') : '' }}</td></tr>
            @endforeach
        </table>
    @endif

    @if (! empty($b->proveedores_subcontratistas))
        <h2>Proveedores y subcontratistas</h2>
        <table class="lista">
            @foreach ($b->proveedores_subcontratistas as $prov)
                <tr><td>{{ is_array($prov) ? ($prov['nombre'] ?? '') : $prov }}</td></tr>
            @endforeach
        </table>
    @endif

    @if ($b->vobo_cliente_nombre)
        <div class="vobo">
            <strong>Visto bueno del cliente</strong><br>
            <strong>Nombre:</strong> {{ $b->vobo_cliente_nombre }}<br>
            <strong>Organización:</strong> {{ $b->vobo_cliente_organizacion ?? '—' }}<br>
            <strong>Fecha:</strong> {{ optional($b->vobo_cliente_fecha)->format('d/m/Y') }}<br>
            @if ($b->firmado_at)
                <strong>Firmado:</strong> {{ $b->firmado_at->format('d/m/Y H:i') }}
            @endif
        </div>
    @endif

    <div class="footer">Documento generado automáticamente por la plataforma GPT — {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
