# Pantalla 19 — Reporte de Asignación con Heatmap

**Ruta:** `/proyectos/asignaciones`
**Módulo:** M9 Asignación
**Hito:** H10
**Layout:** Sidebar + topbar + main

---

## Rol del usuario
`gerente_proyectos`, `gerente_operaciones`, `direccion_general` ven todo el equipo. Cada persona ve su propia tarjeta en `/perfil/mi-asignacion`. Replica `FO-GPT-PYT-01-B`.

## Objetivo
Visualizar carga de trabajo del Departamento de Proyectos. Identificar sobrecarga. Tomar decisiones de redistribución. Exportar reporte mensual a Excel.

## Componentes principales

**Header:**
- Título "Reporte de Asignación · Q1 2026" + subtítulo "FO-GPT-PYT-01-B".
- Selectores: año (2024/2025/2026), trimestre (Q1/Q2/Q3/Q4), gerencia regional (Todas/GRC/GRS/GRN/DG/GPT-IM).
- Botón principal `gpt-600`: "Exportar Excel".

**KPIs en fila (4 cards):**
1. Equipo total: 7 personas (2 gerentes · 4 ingenieros · 1 trainee).
2. Proyectos activos: 34 (22 CP · 12 DN).
3. Carga promedio: 4.9 proyectos/persona.
4. **Sobrecarga: 2 personas** (>8 proyectos activos) — card en `gpt-red-50` con icono warning.

**Heatmap principal:**
Card grande full-width:
- Header: "Heatmap de carga por persona" + leyenda inline (4 niveles de color).
- Tabla:
  - Columna izquierda fija: avatar + nombre + (rol entre paréntesis).
  - Columnas: Enero, Febrero, Marzo (trimestre seleccionado).
  - Cada celda: número de proyectos activos en ese mes con código de color:
    - 0-4: `green-100` bg / `green-900` text.
    - 5-7: `amber-100` bg / `amber-900` text.
    - 8-9: `gpt-200` bg / `gpt-900` text.
    - 10+: `gpt-red-200` bg / `gpt-red-900` text.
  - Click en celda: muestra detalle (drawer con lista de proyectos de esa persona en ese mes).

**Detalle por persona (vista expandida al clickear una persona):**
Card grande:
- Header: avatar grande + nombre + posición + (años antigüedad).
- Tabs: "Asignación actual" | "Histórico" | "Datos RH".
- **Tab Asignación actual** (3 sub-cards):
  - Datos RH: posición, ingreso, antigüedad, formación.
  - Asignación del mes: CP asignados, ejecutados, remanentes, residual anterior.
  - Distribución: servicio, suministro, stand by, cerrados Q1.
- **Tab Histórico:** gráfica de líneas de carga últimos 12 meses + tabla detalle.

**Below the fold — Lista detallada:**
Tabla expandible:
| Persona | Posición | Gerencia | CP totales | DN totales | Servicio | Suministro | Cerrados Q | Carga actual |
|---------|----------|----------|-----------|-----------|----------|------------|-----------|--------------|

## Datos de ejemplo (equipo de 7 para Q1 2026)
- Fernando Basave (GP) — En:8 / Feb:11 / Mar:10 — Sobrecarga.
- Jesús Becerra (GO) — En:6 / Feb:7 / Mar:9 — Carga alta.
- Ana López (IC) — En:4 / Feb:5 / Mar:6 — Carga media.
- Erick Morales (IP) — En:3 / Feb:4 / Mar:5 — Normal.
- Guillermo Gutiérrez (IP) — En:5 / Feb:8 / Mar:9 — Carga alta.
- Sergio Ordaz (IP) — En:2 / Feb:4 / Mar:6 — Normal.
- Roberto Trainee (TR) — En:2 / Feb:2 / Mar:3 — Normal.

## Estados
- **Sin datos del mes:** "Snapshot del mes aún no generado. Próximo snapshot: 1° de abril 00:30."
- **Snapshot manual:** botón en admin "Generar snapshot ahora" (solo super_admin).
- **Persona sin actividad:** mostrar fila en gris con "Sin proyectos activos".

## Responsive
- **Mobile:** heatmap se vuelve scroll horizontal con primera columna sticky. Cards de KPI en columna única.
- **Tablet:** heatmap completo con scroll si necesario.
- **Desktop:** layout completo.

## Edge cases
- Si una persona no tiene `puesto_rh` asociado en `rh_role_mapping`, no aparece en el heatmap (warning para admin).
- Si un usuario es marcado como inactivo durante el período, su fila aparece atenuada.
- Filtro por gerencia regional: si no hay personas en esa gerencia, mostrar empty state.

## Referencias visuales
- Heatmap estilo GitHub contributions o Notion calendar.
- Tabla detalle: ver mockup ya generado en sesión previa.

## Notas técnicas
- Snapshot mensual generado por `GenerarSnapshotAsignacionesJob` (día 1, 00:30).
- Cálculo: cuenta de proyectos donde el usuario aparece como `gerente_proyectos`, `ingeniero_costos`, `ingeniero_proyectos`, `trainee_id`, o `gerente_operaciones`.
- Cache del heatmap por 1 hora (invalidable manualmente).
- Excel export replica template original `FO-GPT-PYT-01-B`.
