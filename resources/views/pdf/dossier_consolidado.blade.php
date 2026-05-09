<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Dossier consolidado · {{ $proyecto->cp_numero }}</title>
<style>
    @page { margin: 50px 40px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9pt; color: #1f2937; }
    h1 { color: #b91c1c; font-size: 18pt; margin: 0; }
    h2 { font-size: 12pt; margin: 16pt 0 6pt 0; color: #1f2937; border-bottom: 1pt solid #cbd5e1; padding-bottom: 3pt; page-break-after: avoid; }
    h3 { font-size: 10pt; margin: 10pt 0 4pt 0; color: #1f2937; }
    .portada { text-align: center; padding-top: 80pt; padding-bottom: 60pt; page-break-after: always; }
    .portada h1 { font-size: 28pt; margin-bottom: 12pt; }
    .portada .meta { margin-top: 30pt; font-size: 11pt; color: #6b7280; line-height: 1.6; }
    .indice { page-break-after: always; }
    .indice table { width: 100%; border-collapse: collapse; }
    .indice td { padding: 6pt 8pt; border-bottom: 0.3pt solid #e2e8f0; }
    table.checklist { width: 100%; border-collapse: collapse; font-size: 8.5pt; margin-top: 4pt; }
    table.checklist th { background: #1f2937; color: white; padding: 4pt; text-align: left; }
    table.checklist td { padding: 4pt; border-bottom: 0.3pt solid #e2e8f0; }
    table.checklist .check { width: 5%; text-align: center; }
    .docs { font-size: 8.5pt; margin-top: 6pt; }
    .docs li { padding: 2pt 0; border-bottom: 0.3pt dotted #e2e8f0; }
    .seccion { page-break-inside: avoid; margin-bottom: 12pt; }
    .progress { width: 100%; height: 6pt; background: #e2e8f0; border-radius: 2pt; overflow: hidden; }
    .progress .bar { background: #16a34a; height: 100%; }
    .footer { position: fixed; bottom: -30pt; left: 0; right: 0; text-align: center; font-size: 7pt; color: #94a3b8; }
</style>
</head>
<body>
    <div class="portada">
        <h1>Dossier de Proyecto</h1>
        <p style="font-size: 16pt; color: #1f2937; margin: 8pt 0;">{{ $proyecto->cp_numero }}@if ($proyecto->dn_numero) / <span style="color: #064e3b;">{{ $proyecto->dn_numero }}</span>@endif</p>
        <p style="font-size: 13pt; color: #1f2937; margin: 4pt 0;">{{ $proyecto->cliente?->razon_social }}</p>
        <p style="font-size: 11pt; color: #6b7280; margin: 4pt 0;">{{ $proyecto->sublinea?->codigo }} — {{ $proyecto->sublinea?->nombre }}</p>

        <div class="meta">
            <p>Apertura: <strong>{{ optional($libro->fecha_apertura)->format('d/m/Y') }}</strong></p>
            <p>Cierre estimado: <strong>{{ optional($libro->fecha_cierre_estimado)->format('d/m/Y') ?? '—' }}</strong></p>
            <p>Avance global: <strong style="color: #16a34a; font-size: 16pt;">{{ number_format((float) $libro->porcentaje_avance_global, 1) }}%</strong></p>
            <p style="margin-top: 20pt; font-size: 9pt;">{{ $libro->bloqueado_para_cierre ? '⚠ Cierre bloqueado (D11)' : '✓ Listo para cierre' }}</p>
        </div>
    </div>

    <div class="indice">
        <h1 style="color: #b91c1c; margin-bottom: 16pt;">Índice de secciones</h1>
        <table>
            <thead>
                <tr>
                    <th style="text-align: left; padding: 6pt 8pt; border-bottom: 1pt solid #1f2937;">Sección</th>
                    <th style="text-align: left; padding: 6pt 8pt; border-bottom: 1pt solid #1f2937;">Items</th>
                    <th style="text-align: left; padding: 6pt 8pt; border-bottom: 1pt solid #1f2937;">Docs</th>
                    <th style="text-align: right; padding: 6pt 8pt; border-bottom: 1pt solid #1f2937;">Avance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($libro->secciones as $sec)
                    <tr>
                        <td><strong>{{ $sec->codigo }}.</strong> {{ $sec->nombre }}</td>
                        <td>{{ $sec->checklist->where('completado', true)->count() }}/{{ $sec->checklist->count() }}</td>
                        <td>{{ $sec->documentos->count() }}</td>
                        <td style="text-align: right;">{{ number_format((float) $sec->porcentaje_avance, 1) }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @foreach ($libro->secciones as $sec)
        <div class="seccion">
            <h2>{{ $sec->codigo }}. {{ $sec->nombre }}</h2>
            <p style="margin: 0 0 6pt 0; font-size: 8.5pt; color: #6b7280;">
                Responsable: <strong>{{ $sec->responsable?->name ?? '—' }}</strong> ·
                Estado: <strong>{{ $sec->estado }}</strong> ·
                {{ $sec->checklist->where('completado', true)->count() }}/{{ $sec->checklist->count() }} items
            </p>
            <div class="progress"><div class="bar" style="width: {{ (float) $sec->porcentaje_avance }}%;"></div></div>

            @if ($sec->checklist->isNotEmpty())
                <h3>Checklist</h3>
                <table class="checklist">
                    <thead>
                        <tr>
                            <th class="check">✓</th>
                            <th>Item</th>
                            <th style="width: 22%;">Completado por</th>
                            <th style="width: 22%;">Evidencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sec->checklist as $item)
                            <tr>
                                <td class="check">{{ $item->completado ? '✓' : '✗' }}</td>
                                <td>{{ $item->item_descripcion }}</td>
                                <td style="font-size: 8pt; color: #6b7280;">
                                    @if ($item->completado && $item->completadoPor)
                                        {{ $item->completadoPor->name }}<br>{{ optional($item->completado_at)->format('d/m/Y') }}
                                    @endif
                                </td>
                                <td style="font-size: 8pt; color: #6b7280;">
                                    @if ($item->evidencia)
                                        📎 {{ $item->evidencia->nombre }} <small>v{{ $item->evidencia->version }}</small>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if ($sec->documentos->isNotEmpty())
                <h3>Documentos cargados ({{ $sec->documentos->count() }})</h3>
                <ul class="docs">
                    @foreach ($sec->documentos as $doc)
                        <li>
                            📎 <strong>{{ $doc->nombre }}</strong> v{{ $doc->version }}
                            <span style="color: #6b7280;">
                                · {{ $doc->subidoPor?->name ?? 'sistema' }}
                                · {{ optional($doc->subido_at)->format('d/m/Y H:i') }}
                                @if ($doc->tamaño)
                                    · {{ number_format($doc->tamaño / 1024, 1) }} KB
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($sec->observaciones)
                <p style="font-size: 8.5pt; color: #6b7280; margin-top: 6pt;"><em>Observaciones: {{ $sec->observaciones }}</em></p>
            @endif
        </div>
    @endforeach

    <div class="footer">Dossier generado — {{ now()->format('d/m/Y H:i') }} · GPT Services</div>
</body>
</html>
