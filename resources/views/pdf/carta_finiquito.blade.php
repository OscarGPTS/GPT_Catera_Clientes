<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Carta Finiquito · {{ $p->cp_numero }}</title>
<style>
    @page { margin: 70px 60px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #1f2937; line-height: 1.5; }
    h1 { color: #b91c1c; font-size: 18pt; margin: 0 0 4pt 0; text-align: center; }
    h2 { font-size: 11pt; margin: 14pt 0 6pt 0; color: #1f2937; border-bottom: 1pt solid #cbd5e1; padding-bottom: 3pt; }
    .header { text-align: center; margin-bottom: 18pt; }
    table.meta { width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 8pt; }
    table.meta td { padding: 3pt 6pt; border: 0.5pt solid #cbd5e1; }
    table.meta .label { background: #f1f5f9; font-weight: bold; width: 22%; }
    table.lista { width: 100%; border-collapse: collapse; font-size: 9pt; }
    table.lista td { padding: 4pt; border-bottom: 0.3pt solid #e2e8f0; }
    table.firmas { width: 100%; margin-top: 28pt; }
    table.firmas td { width: 50%; text-align: center; padding: 14pt; vertical-align: bottom; }
    table.firmas .linea { border-top: 0.7pt solid #1f2937; padding-top: 6pt; font-size: 9pt; }
    .observaciones { padding: 8pt; background: #f8fafc; border-left: 3pt solid #6366f1; font-size: 9pt; }
    .footer { position: fixed; bottom: -50pt; left: 0; right: 0; text-align: center; font-size: 8pt; color: #94a3b8; }
</style>
</head>
<body>
    <div class="header">
        <h1>Carta Finiquito</h1>
        <p style="margin: 0; font-size: 9pt; color: #6b7280;">FO-GPT-PYT-CF-01 · GPT Services</p>
        <p style="margin: 4pt 0 0 0;"><strong>{{ $p->cp_numero }}</strong>
            @if ($p->dn_numero) / <strong style="color:#064e3b">{{ $p->dn_numero }}</strong> @endif</p>
    </div>

    <table class="meta">
        <tr><td class="label">Cliente</td><td>{{ $p->cliente?->razon_social }} ({{ $p->cliente?->alias_3letras }})</td></tr>
        <tr><td class="label">Proyecto</td><td>{{ $p->resumen_ejecutivo }}</td></tr>
        <tr><td class="label">Sublínea</td><td>{{ $p->sublinea?->codigo }} — {{ $p->sublinea?->nombre }}</td></tr>
        <tr><td class="label">Fecha emisión</td><td>{{ $c->fecha_emision?->format('d/m/Y') }}</td></tr>
    </table>

    <p>
        Por la presente <strong>GPT Services (Tech Energy Control S.A. de C.V.)</strong> y <strong>{{ $p->cliente?->razon_social }}</strong>
        manifiestan que las actividades del proyecto referenciado han concluido satisfactoriamente.
        Ambas partes liberan al personal y equipos asignados, dejando constancia de que no quedan reclamaciones pendientes.
    </p>

    @if (! empty($c->personal_liberado))
        <h2>Personal liberado</h2>
        <table class="lista">
            @foreach ($c->personal_liberado as $persona)
                <tr><td>{{ is_array($persona) ? ($persona['nombre'] ?? '') : $persona }}</td>
                    <td style="color:#6b7280;">{{ is_array($persona) ? ($persona['rol'] ?? '') : '' }}</td></tr>
            @endforeach
        </table>
    @endif

    @if (! empty($c->equipos_liberados))
        <h2>Equipos liberados</h2>
        <table class="lista">
            @foreach ($c->equipos_liberados as $eq)
                <tr><td>{{ is_array($eq) ? ($eq['nombre'] ?? '') : $eq }}</td></tr>
            @endforeach
        </table>
    @endif

    @if ($c->observaciones)
        <h2>Observaciones</h2>
        <div class="observaciones">{{ $c->observaciones }}</div>
    @endif

    <table class="firmas">
        <tr>
            <td>
                <div class="linea">
                    Por GPT Services<br>
                    <span style="color:#6b7280; font-size:8pt;">{{ $c->firmado_gpt_at ? 'firmada ' . $c->firmado_gpt_at->format('d/m/Y H:i') : 'pendiente de firma' }}</span>
                </div>
            </td>
            <td>
                <div class="linea">
                    Por el Cliente<br>
                    <span style="color:#6b7280; font-size:8pt;">{{ $c->firmado_cliente_at ? 'firmada ' . $c->firmado_cliente_at->format('d/m/Y H:i') : 'pendiente de firma' }}</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">Documento generado automáticamente — {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
