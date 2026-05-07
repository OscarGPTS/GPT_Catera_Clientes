# Pantalla 22 — Dashboard ejecutivo de socios

**Ruta:** `/ejecutivo`
**Módulo:** M13 Vista Ejecutiva
**Hito:** H14
**Layout:** Sidebar + topbar + main

---

## Rol del usuario
`direccion_general` (Guillermo Gutiérrez), `socio` (5 socios identificados via cascada D2), `comite_socios`. Es la pantalla más importante del sistema: lo que ven los dueños del negocio cuando entran. **Acceso protegido por middleware `EnsureSocioAccess`.**

## Objetivo
En ≤30 segundos, los socios deben poder responder: ¿cómo va el pipeline? ¿qué oportunidades necesitan atención? ¿hay riesgo de concentración por cliente? ¿cómo va el hit rate? ¿el equipo tiene capacidad?

## Componentes principales

**Header:**
- Saludo personalizado: "Buenos días, Guillermo" (según hora del día y nombre del usuario).
- Subtítulo: "Vista ejecutiva · Q1 2026 · Datos al 06-may-2026 14:32".
- Selectores globales aplicables a todos los KPIs:
  - Período: Mes / Trimestre / Año / Custom (D8).
  - Cliente (multi-select).
  - Sublínea (multi-select).
  - **Toggle "Incluir SEDENA"** (importante: SEDENA representa 82.7%, sesga métricas).
- Botón "Descargar reporte mensual PDF".

**Banner de alerta de concentración (persistente si aplica):**
- Banner full-width `gpt-red-50` con icono `exclamation-triangle`:
- "Riesgo de concentración: SEDENA representa 82.7% del pipeline 2026. Considera revisar diversificación."
- Botón "Ver detalle" → expande tabla con concentración por cliente.

### Sección 1 — KPIs principales (4 cards)

Grid 4 columnas (2x2 en tablet, 1x4 en mobile):

**Card 1: Pipeline activo**
- Número grande: $54.7M USD.
- Subtítulo: "18 oportunidades activas".
- Mini-trend: "+12% vs Q4 2025".
- Color del trend: verde si positivo, rojo si negativo.
- Icono: `chart-pie`.

**Card 2: Adjudicado YTD**
- Número grande: $8.3M USD.
- Subtítulo: "4 proyectos firmados".
- Mini-trend: "Cumplimiento 67% de meta anual".
- Barra de progreso pequeña (gpt-600).

**Card 3: Hit rate**
- Dos números lado a lado (D7):
  - **22%** (por conteo) — "4 de 18 ofertas".
  - **15%** (por monto) — "$8.3M de $54.7M".
- Mini-trend: "Mejor que Q4 (18% / 12%)".

**Card 4: Concentración SEDENA**
- Número grande: 82.7%.
- Subtítulo: "$45.3M de $54.7M".
- Color de fondo: `gpt-red-50` (alerta visual).
- Mini-trend: "Sin cambios vs trimestre anterior".

### Sección 2 — Gráficas principales (grid 2 columnas)

**Card "Concentración por cliente":**
- Bar chart horizontal con clientes ordenados por monto.
- Top 5 clientes con barras.
- SEDENA en `gpt-red-600` (resaltada como riesgo).
- Otros clientes en `gpt-600`.
- Línea de referencia 50% en gris.

**Card "Pipeline por sublínea":**
- Donut chart con 5 sublíneas (HTP, LSP, VLV, SOL, SG).
- Colores diferenciados.
- Centro: "$54.7M" + "Total Q1".
- Leyenda con montos y % al lado.

**Card "Adjudicaciones mensuales 2026":**
- Line chart con 5 puntos (Enero a Mayo).
- Eje Y: monto USD.
- Datapoints clickeables → muestra detalle del mes.

**Card "Hit rate trimestral":**
- Line chart con 2 series (conteo y monto) últimos 4 trimestres.
- Tooltips con valores.

### Sección 3 — Tabla "Ofertas que requieren atención"

Card full-width con tabla:
- Header: "Top 5 oportunidades urgentes" + subtítulo "Ordenadas por urgencia (días sin actividad × valor × probabilidad)".
- Columnas:
  | CP | Tech Reference | Cliente | Monto | Probabilidad | Días sin actividad | Líder | Acción sugerida |
  |----|---------------|---------|-------|--------------|--------------------|-------|-----------------|
  | CP-007/26 | 260215-0-SDN-SOL... | SEDENA | $2.1M | 60% | 14 días | EM | Llamar a contacto |
  | CP-001/26 | 260105-0-ENG-HTP... | ENGIE | $58.9k | 70% | 8 días | FB | Confirmar fecha |

- Click en fila → drawer con detalles + acciones.

### Sección 4 — Salud operativa (3 mini-cards)

Grid 3 columnas:

**Card "Carga del equipo"**
- Número: "4.9 proyectos/persona promedio".
- Indicador: "2 personas en sobrecarga (>10 proyectos)".
- Link "Ver Reporte de Asignación →".

**Card "Dossiers en riesgo"**
- Número: "3 proyectos con dossier <50% al 70% del cronograma".
- Lista corta de los 3.
- Link "Ver detalle →".

**Card "Post-Mortems pendientes"**
- Número: "67% proyectos cerrados con post-mortem completado".
- Indicador: "2 cerrados sin post-mortem en últimos 30 días".
- Link "Generar pendientes →".

### Sección 5 — Cierre Gerencial Snapshot (mini-resumen)

Card destacado con borde `gpt-600`:
- Header: "Cierre Gerencial · Marzo 2026".
- 3 mini-stats horizontales:
  - SAT base: $187k
  - + Devengado: $138k
  - + Pipeline ponderado: $1.85M
  - = Total Gerencial: $2.18M
- Mini-bar chart waterfall.
- Link "Ver cierre completo en /finanzas →".

## Datos de ejemplo
Todos los datos del ejemplo son de Q1 2026, basados en el archivo `Status_ofertas_GPT_Services_2026.xlsx`. Los datos reales presentan:
- Pipeline total: $54.7M USD (18 ofertas).
- Adjudicado YTD: $8.3M USD (4 proyectos: SEDENA SOL grande $5.2M + Texmelucan IGA $97k + 2 más).
- Concentración SEDENA: 82.7%.
- Hit rate: 22% (conteo) / 15% (monto).

## Estados
- **Sin datos del período:** "Sin actividad en el período seleccionado. Ajusta filtros."
- **Sin acceso (rol no autorizado):** redirect a `/dashboard` con mensaje "Esta sección está restringida a Dirección y socios."
- **Datos cargando:** skeleton de cards.
- **Concentración baja:** banner cambia a verde "✓ Diversificación saludable" si <50%.

## Responsive
- **Mobile:** todas las cards se apilan vertical, gráficas ocupan 100% ancho.
- **Tablet:** grid 2x2 para KPIs y gráficas.
- **Desktop:** grid 4x1 KPIs, 2x2 gráficas, tabla full-width.

## Edge cases
- Toggle "Incluir SEDENA" hace recalcular todos los KPIs con/sin esa cuenta.
- Si concentración baja del 50% por primera vez en 12 meses, banner verde de celebración: "🎯 Concentración SEDENA por debajo del 50%. Diversificación lograda."
- Si pipeline cae >20% mes a mes, banner amarillo de atención: "Pipeline contraído 23% vs mes anterior".
- Filtros se persisten en localStorage por usuario.

## Referencias visuales
- Dashboard ejecutivo: estilo Mercury, Stripe Sigma, Linear Insights.
- KPI cards con trend: estilo Vercel analytics.
- Bar chart concentración: estilo financial concentration risk reports.
- Banner de alerta: estilo SaaS dashboards (Datadog, Grafana).

## Notas técnicas
- Acceso protegido por middleware `EnsureSocioAccess` que verifica `auth()->user()->es_socio === true` (cascada D2).
- KPIs cacheados en `kpi_snapshots` (regenerados cada hora o al cerrar mes).
- Generación PDF mensual con `ReporteEjecutivoPdfGenerator`.
- Filtros globales persistidos en query string (compartibles).
- Eventos de uso loggeados para entender qué KPIs son más consultados.
EOF