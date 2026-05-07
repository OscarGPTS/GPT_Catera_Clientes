# Pantalla 08 — Editor de cotización (COSS)

**Ruta:** `/proyectos/{id}/cotizacion`
**Módulo:** M3 Cotización
**Hito:** H3
**Layout:** Sidebar + topbar + main

---

## Rol del usuario
`ingeniero_costos` y `ingeniero_proyectos`. Captura las partidas COSS (Costo / Indirectos / Admin / Utilidad) que conforman la cotización del proyecto. Replica `FO-GPT-VTS-01-F`.

## Objetivo
Generar una cotización al centavo igual al Excel original. Reproducir caso Texmelucan: $61,717.61 → $96,998.30 con margen 41.59%.

## Componentes principales

**Header:**
- Título "Cotización CP-003/25 · v2" + subtítulo "HT 30"x10" Texmelucan · IGASAMEX · Borrador".
- Breadcrumb: Oportunidades / CP-003/25 / Cotización.
- Selector versión: dropdown "v1 (presentada) | v2 (en edición) | + Nueva versión".
- Botón "Vista previa PDF".
- Botón "Guardar borrador".
- Botón principal `gpt-600`: "Marcar como lista para revisión".

**Layout 3 columnas:**

### Columna izquierda (15%) — Mini-nav de secciones
Sticky scrollspy:
- Datos generales
- Partidas (19)
- Factores
- Resumen
- Condiciones comerciales

### Columna central (60%) — Editor de partidas

**Sección "Partidas" (la principal):**
Tabla editable con filas dinámicas:
| # | Descripción | Cantidad | Unidad | Costo unitario USD | Costo total USD | Acciones |
|---|-------------|----------|--------|--------------------|-----------------|----------|
| 1 | Movilización equipos a Texmelucan | 1 | Servicio | $4,500.00 | $4,500.00 | ⋮ |
| 2 | Hot Tap 30"x10" línea... | 1 | Servicio | $52,000.00 | $52,000.00 | ⋮ |
| ... | (19 partidas total para Texmelucan) | | | | | |
|   | **Subtotal costo directo** | | | | **$61,717.61** | |

Cada fila editable inline con cálculo automático. Botón "+ Agregar partida". Botón duplicar y eliminar por fila. Drag handle para reordenar.

**Sección "Factores":**
Sliders con valor numérico:
- Indirectos: 15% [slider] = $9,257.64
- Administración: 8% [slider] = $5,678.02
- Utilidad: 22% [slider] = $16,955.99
- Tipo de cambio (si aplica): 17.50 MXN/USD

Cada factor muestra impacto en vivo.

**Sección "Resumen":**
Tabla compacta read-only:
- Costo directo: $61,717.61
- Indirectos (15%): $9,257.64
- Administración (8%): $5,678.02
- **Subtotal con admin: $76,653.27**
- Utilidad (22%): $16,955.99
- **Precio venta calculado: $93,609.26**
- Ajuste manual: + $3,389.04 (input editable)
- **Precio venta final: $96,998.30**
- Margen neto: 41.59% (badge verde si >30%, amarillo si 15-30%, rojo si <15%)

**Sección "Condiciones comerciales":**
- Validez (días, default 30)
- Forma de pago (textarea con plantilla "50% anticipo + 50% al finalizar perforación")
- Tiempo de entrega (días naturales)
- Lugar de entrega (texto)
- Tipo de cambio (info)
- Garantía (textarea)
- Observaciones (textarea)

### Columna derecha (25%) — Resumen pegajoso (sticky)
Card sticky en scroll:
- Tech Reference (badge si ya generado, botón "Generar Tech Reference" si no)
- Mini stats: Costo $61.7k · Precio $97.0k · Margen 41.59%
- Indicador de validación: "✓ Cálculos OK contra fixture Texmelucan" o "⚠ Discrepancia detectada"
- Botón "Generar PDF"
- Botón "Generar Ficha de Proyecto Excel"
- Historial reciente (timeline mini): "Erick editó partida 5 hace 2h"

## Datos de ejemplo
Pre-llenar con las 19 partidas del caso Texmelucan documentadas en `RE-GPT-CP-003-25_Ficha_de_proyecto_HT_30X10_Texmelucan.xlsx`.

## Estados
- **Loading:** skeleton del editor.
- **Cálculo en vivo:** indicador verde "Cálculos actualizados" tras cada cambio.
- **Discrepancia:** badge rojo si los cálculos no coinciden con fórmula esperada.
- **Read-only:** versiones anteriores se ven en read-only con banner "Versión histórica · v1 presentada el 21-ene-2025".

## Responsive
- **Mobile:** una sola columna, mini-nav se vuelve dropdown arriba. Tabla de partidas con scroll horizontal.
- **Tablet:** 2 columnas (editor + resumen). Mini-nav colapsa.
- **Desktop:** 3 columnas como descrito.

## Edge cases
- Si margen <15%, banner rojo persistente: "Margen bajo. Considera ajustar factores o partidas."
- Si la cotización tiene >2 versiones, mostrar comparador "v1 vs v2" con deltas.
- Auto-save cada 30 segundos en estado borrador. Indicador "Guardado hace 12s".

## Referencias visuales
- Editor de tabla densa: estilo Notion database o Airtable.
- Stats card derecha: Stripe pricing calculator.

## Notas técnicas
- **CRÍTICO**: validación obligatoria contra fixture Texmelucan en suite de tests. Si los cálculos divergen del Excel original al centavo, bloqueo de deploy.
- Cálculos se hacen tanto client-side (Livewire reactive) como server-side (autoritativo).
- Generación PDF replica `FO-GPT-VTS-01-E` con DomPDF.
- Generación Excel replica `FO-GPT-VTS-01-F` con PhpSpreadsheet.
