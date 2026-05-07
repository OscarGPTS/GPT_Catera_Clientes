# Pantalla 14 — Listado de Suministros (FO-GPT-PYT-01-C)

**Ruta:** `/proyectos/{id}/suministros`
**Módulo:** M5 Operaciones
**Hito:** H7
**Layout:** Sidebar + topbar + main

---

## Rol del usuario
`ingeniero_proyectos`, `gerente_proyectos`, `compras`. Tracking dedicado al `Listado de Suministros` del procedimiento (`FO-GPT-PYT-01-C`), distinto del BOM/BOE general porque enfoca el % de avance por etapa según procedimiento.

## Objetivo
Visualizar % de avance global del proyecto basado en estado de cada suministro, según las 4 etapas del procedimiento.

## Componentes principales

**Header:**
- Título "Listado de Suministros · DN-018/26".
- Subtítulo "Avance global: 67% · 12 de 18 items completados".
- Botón "Exportar Excel FO-GPT-PYT-01-C".

**Hero card de avance global:**
Card grande full-width con:
- Barra de progreso `gpt-600` con animación, mostrando 67%.
- Subtítulo de las 4 etapas con conteos:
  - Etapa 1 (0-25%): Definición / Cotización / OC — "2 items"
  - Etapa 2 (26-50%): Revisión Ingeniería / Fabricación iniciada — "1 item"
  - Etapa 3 (51-75%): Fabricación / FAT — "3 items"
  - Etapa 4 (76-100%): Embarque / Dossier — "12 items"
- Indicador de fecha de movilización: "Movilización planeada en 14 días"

**Tabla principal:**
| Col | Descripción |
|-----|-------------|
| # | Número |
| Descripción | Item |
| Cantidad / Unidad | |
| Fecha requerida | Datepicker inline |
| Etapa actual | Indicador visual (4 dots horizontales, llenos hasta etapa actual) |
| % Avance | Barra horizontal pequeña + número |
| Responsable | Avatar |
| Última actualización | Relativa |
| Acciones | Editar, Avanzar etapa, Notas |

**Vista alternativa (toggle): Kanban por etapa**
4 columnas con cards arrastrables:
- Etapa 1 (Definición/Cotización/OC)
- Etapa 2 (Revisión Ing./Fabricación)
- Etapa 3 (Fabricación/FAT)
- Etapa 4 (Embarque/Dossier)

Cada card: descripción + cantidad + responsable + fecha requerida + barra de avance interno de la etapa.

Drag&drop entre columnas avanza al item de etapa.

## Datos de ejemplo
- Housing 30"x10" — 1 pza — req 12-mar — Etapa 3 (FAT) — 65% — Manufactura — actualizado hace 2d.
- Válvula 30" ANSI — 1 pza — req 10-mar — Etapa 4 (Dossier) — 90% — Compras — actualizado hace 1d.
- Bridas 10" — 4 pza — req 18-mar — Etapa 1 (OC emitida) — 20% — Compras — actualizado hace 5d.
- Empaques — 12 pza — req 18-mar — Etapa 1 (Cotización) — 10% — Compras — actualizado hace 7d.

## Estados
- **Vacío:** "No hay suministros listados. Importa desde BOM o agrega manualmente."
- **Avance crítico:** si <50% y faltan <14 días para movilización, banner rojo "Riesgo: avance insuficiente para movilización".

## Responsive
- **Mobile:** tabla → cards. Vista Kanban: tabs horizontales con scroll por columna.
- **Desktop:** tabla y Kanban completos.

## Edge cases
- Avance global se calcula automáticamente como avg(% items).
- Si un item está en Etapa 4 (76-100%) pero la `solicitudes_internas` asociada sigue abierta, warning amarillo.

## Referencias visuales
- Hero card de avance: estilo Linear cycle progress.
- Kanban: estilo Trello o Linear board.
- Indicador 4 dots: estilo Stripe steps.

## Notas técnicas
- Etapas y % son rangos definidos en el procedimiento, no se pueden modificar (configuración hardcoded).
- Avance individual por item se actualiza por el responsable.
- Auto-update de `proyecto_eventos` cuando un item cambia de etapa.
