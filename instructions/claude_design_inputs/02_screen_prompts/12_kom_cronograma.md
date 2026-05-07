# Pantalla 12 — KOM y Cronograma del proyecto

**Ruta:** `/proyectos/{id}/kom`
**Módulo:** M5 Operaciones
**Hito:** H7
**Layout:** Sidebar + topbar + main

---

## Rol del usuario
`ingeniero_proyectos`, `gerente_proyectos`, `gerente_operaciones`. Programa Kick Off Meetings (interno y con cliente) e importa/visualiza el cronograma del proyecto.

## Objetivo
Documentar las dos KOM del procedimiento (interno entre Proyectos+Operaciones, y con cliente). Importar cronograma desde MS Project o Project Libre y visualizarlo como Gantt.

## Componentes principales

**Header:**
- Título "Planeación · DN-018/26 · Texmelucan" + subtítulo "KOM y Cronograma".
- Tabs: "**KOM Interno**" | "KOM Cliente" | "Cronograma".

### Tab 1 — KOM Interno
- Si no existe aún: estado vacío con CTA "Programar KOM Interno" (modal).
- Si existe: card con datos:
  - Fecha + hora + modalidad + lugar / link.
  - Participantes (avatares horizontales).
  - Agenda (lista con checkboxes que se marcan completos durante la reunión).
  - Botón "Cargar minuta firmada" → upload PDF.
  - Si minuta cargada: preview + botón ver/descargar.
  - Estado: Programado / Realizado / Cancelado.

### Tab 2 — KOM Cliente
Mismo formato que KOM Interno pero:
- Participantes adicionales: contactos del cliente (con email y organización).
- Campos adicionales: rectificación de alcance, dudas del cliente.
- Genera invitación calendario (.ics descargable).

### Tab 3 — Cronograma
**Header del tab:**
- Estado: "Sin cronograma cargado" / "v3 cargada hace 5 días".
- Botón "Importar cronograma" (drag&drop area abre, soporta `.mpp`, `.xml`, `.csv`).
- Botón "Crear desde plantilla" (plantillas por sublínea: HTP estándar, LSP estándar, etc.).
- Versión select.

**Visualización Gantt:**
- Filas: actividades del proyecto (jerarquía con expand/collapse para sub-actividades).
- Columnas izquierdas: código, nombre actividad, duración, predecesoras.
- Columnas derechas (timeline): barras horizontales por actividad. Hoy marcado con línea vertical roja.
- Hover en barra: tooltip con fecha inicio/fin, % avance, recursos.
- Click en actividad: drawer lateral con detalle editable (avance real, fechas reales, observaciones).

**Footer del cronograma:**
- Resumen: "32 actividades · Duración total 45 días · 22% completado".
- Botón "Exportar a Excel".
- Botón "Generar reporte de avance PDF".

## Datos de ejemplo (Texmelucan)
KOM Interno: 03-mar-2025 · 14:00-15:30 · Sala de juntas · Asistentes: Fernando, Jesús, Sergio, Roberto.

KOM Cliente: 05-mar-2025 · 10:00-11:00 · Virtual · Asistentes: + Ing. Juan Pérez (IGASAMEX) + Lic. María González (CENAGAS observadora).

Cronograma actividades muestra:
- A1 Movilización (5 días) → A2 Preparación sitio (3 días) → A3 Soldadura housing (4 días) → A4 Hot Tap (1 día) → A5 Línea derivación (2 días) → A6 Pruebas y entrega (2 días).

## Estados
- **Sin KOM:** "Aún no programas KOM Interno. Recomendado antes de movilizar al sitio."
- **Sin Cronograma:** ilustración + "Importa cronograma desde MS Project, Project Libre, o crea uno desde plantilla."
- **Cronograma con desviación:** indicador rojo en barra de actividad si fecha real > planeada.

## Responsive
- **Mobile:** Gantt se vuelve lista vertical con barras de progreso. KOMs como cards apiladas.
- **Tablet:** Gantt scroll horizontal.
- **Desktop:** Gantt completo.

## Edge cases
- Si la fecha real de una actividad excede planeada >20%, alerta automática al `gerente_proyectos` y registro en `proyecto_eventos` como `desviacion_reportada`.
- Si KOM Interno no se ha realizado y ya pasó la fecha programada, status cambia a "Vencido" en rojo.
- Importación con errores: modal "Se importaron 28 de 32 actividades. 4 con errores. ¿Ver detalles?"

## Referencias visuales
- Gantt: estilo TeamGantt, MS Project simplificado, o Linear cycles.

## Notas técnicas
- Parser de `.mpp` con librería externa.
- Visualización Gantt con ApexCharts timeline.
- Plantillas pre-cargadas por sublínea en seeders.
