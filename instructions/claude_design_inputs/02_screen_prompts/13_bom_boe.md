# Pantalla 13 — BOM/BOE (Bill of Materials / Equipment)

**Ruta:** `/proyectos/{id}/bom-boe`
**Módulo:** M5 Operaciones
**Hito:** H7
**Layout:** Sidebar + topbar + main

---

## Rol del usuario
`ingeniero_proyectos`, `compras`, `almacen`, `manufactura`. Tracking de materiales (BOM) y equipos (BOE) del proyecto, con seguimiento de status (en almacén, por fabricar, por comprar, etc.).

## Objetivo
Saber en qué status está cada componente del proyecto y qué falta antes de movilizar. Los archivos `FO-GPT_3.xlsx LIB 4.1km` muestran este caso real.

## Componentes principales

**Header:**
- Título "BOM/BOE · DN-018/26 · Texmelucan" + subtítulo "Materiales y equipos del proyecto".
- Stats inline: "BOM: 47 items · BOE: 12 equipos · 78% disponible · 5 críticos pendientes".
- Tabs: "BOM (47)" | "BOE (12)" | "Resumen".
- Botones: "+ Agregar item", "Importar Excel", "Exportar".

**Tabs BOM y BOE: tabla densa similar:**

Columnas:
| Col | Descripción |
|-----|-------------|
| # | Numeración |
| Código | SKU interno o ref proveedor |
| Descripción | Texto corto + tooltip con detalles |
| Cantidad | Numérico |
| Unidad | pza, kg, m, m³, etc. |
| Status | Badge color: En almacén (verde) / Por afilar (azul) / Por fabricar (naranja) / Por comprar (rojo) / En tránsito (amarillo) / Entregado (verde oscuro) |
| Responsable | Avatar |
| Fecha requerida | Datepicker inline |
| Fecha disponibilidad | Real, con indicador adelantado/a tiempo/atrasado |
| Acciones | Editar, Notas, Eliminar |

Filtros arriba:
- Status (multi-select).
- Responsable.
- Search por código o descripción.
- Toggle "Solo críticos" (requeridos en próximos 7 días sin disponibilidad).

**Tab "Resumen":**
- Pie chart de status del BOM (47 items).
- Pie chart de status del BOE (12 equipos).
- Lista de items críticos (próximos 7 días sin entregar) con CTA "Escalar a Compras" o "Mover de almacén".
- Línea de tiempo de entregas próximas (próximos 30 días).

## Datos de ejemplo (Texmelucan basado en LIB 4.1km)
BOM:
- Housing 30"x10" — 1 pza — Por fabricar — Manufactura interna — req 12-mar — disp 14-mar (+2d).
- Válvula 30" ANSI 600 — 1 pza — En almacén — Almacén — req 10-mar — disp lista.
- Broca HT 10" — 1 pza — En almacén — Almacén — req 15-mar — disp lista.
- Bridas 10" — 4 pza — Por comprar — Compras — req 18-mar — disp pendiente.
- Empaques — 12 pza — Por comprar — Compras — req 18-mar — disp pendiente (rojo, crítico).

BOE:
- Equipo Hot Tap T-1200 — 1 — En almacén — Servs Técnicos — disp.
- Soldadora MIG/TIG — 2 — En almacén — Soldadura — disp.
- Camión grúa — 1 — Por contratar — Servs Generales — req 14-mar.

## Estados
- **Vacío:** "No hay items registrados. Importa desde Excel del cronograma o agrega manualmente."
- **Crítico:** banner persistente arriba "5 items críticos pendientes — riesgo de retraso de movilización".

## Responsive
- **Mobile:** tabla → cards apiladas. Cada card muestra los campos clave.
- **Desktop:** tabla densa con todas las columnas.

## Edge cases
- Si un item está "Por fabricar" pero no tiene Orden de Trabajo a Ingeniería asociada, warning amarillo en el item.
- Si un item está "Por comprar" pero no tiene Requisición a Compras asociada, similar warning.
- Si la fecha requerida pasa sin disponibilidad, color rojo y notificación automática.

## Referencias visuales
- Tabla densa: estilo Notion table o Airtable grid view.
- Status pills: estilo Linear status indicators.

## Notas técnicas
- Importación Excel con templates pre-definidos.
- Validación cruzada con `solicitudes_internas` y `cronograma_actividades`.
- Auto-update de status al recibir notificación de Compras o Almacén.
