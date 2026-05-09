<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte semanal · {{ $p->cp_numero }} · {{ $r->semana_inicio->format('Y-m-d') }}</title>
<style>
    @page { margin: 60px 50px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1f2937; }
    h1 { color: #b91c1c; font-size: 16pt; margin: 0 0 4pt 0; }
    h2, h3, h4 { color: #1f2937; }
    h3 { font-size: 11pt; margin: 12pt 0 6pt 0; border-bottom: 1pt solid #cbd5e1; padding-bottom: 3pt; }
    h4 { font-size: 10pt; margin: 10pt 0 4pt 0; }
    .header { display: table; width: 100%; margin-bottom: 14pt; }
    .header .left, .header .right { display: table-cell; vertical-align: top; }
    .header .right { text-align: right; }
    p { line-height: 1.4; }
    ul { line-height: 1.4; }
    .footer { position: fixed; bottom: -40pt; left: 0; right: 0; text-align: center; font-size: 8pt; color: #94a3b8; }
</style>
</head>
<body>
    <div class="header">
        <div class="left">
            <h1>Reporte Semanal</h1>
            <p style="margin: 0; font-size: 9pt; color: #6b7280;">GPT Services</p>
        </div>
        <div class="right">
            <p style="margin: 0; font-size: 11pt;"><strong>{{ $p->cp_numero }}</strong>
                @if ($p->dn_numero) / <strong style="color:#064e3b">{{ $p->dn_numero }}</strong> @endif</p>
            <p style="margin: 4pt 0 0 0; font-size: 9pt; color: #6b7280;">{{ $r->semana_inicio->format('d/m/Y') }} – {{ $r->semana_fin->format('d/m/Y') }}</p>
        </div>
    </div>

    {!! $r->contenido_html !!}

    <div class="footer">Documento generado automáticamente — {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
