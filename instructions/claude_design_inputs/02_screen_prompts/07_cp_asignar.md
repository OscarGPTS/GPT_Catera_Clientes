# Pantalla 07 — Asignar CP al equipo

**Ruta:** `/proyectos/cp/{id}/asignar`
**Módulo:** M2 Comercial
**Hito:** H2
**Layout:** Sidebar + topbar + main

---

## Rol del usuario
`gerente_proyectos`. Después de que Comité Comercial aprueba el CP, el Gerente de Proyectos asigna a su equipo (Ingeniero de Costos, Ingeniero de Proyectos, Trainee).

## Objetivo
Distribuir la carga de trabajo del CP a las personas correctas considerando capacidad actual del equipo.

## Componentes principales

**Header:**
- Título "Asignar CP-024/26" + subtítulo "HT 30"x10" Texmelucan · IGASAMEX · $96,998.30 USD".
- Breadcrumb: Oportunidades / CP-024/26 / Asignar.
- Botón "Cancelar" ghost.
- Botón principal `gpt-600`: "Confirmar asignación".

**Layout 2 columnas:**

### Columna izquierda (60%) — Resumen del CP
Card con datos read-only:
- Tech Reference (placeholder hasta Ejecución)
- Cliente, sublínea, sector
- Resumen ejecutivo (texto del paso 2 de creación)
- Plazo estimado, monto preliminar, probabilidad
- Adjuntos (lista de PDFs subidos)
- Aprobado por: avatar + nombre del miembro del Comité Comercial + fecha aprobación
- Notas del Comité (si las hay)

### Columna derecha (40%) — Formulario de asignación
Card sticky en scroll:
- Selector "Ingeniero de Costos" (obligatorio):
  - Avatar + nombre + carga actual ("8 CP activos")
  - Mostrar 3-5 sugerencias automáticas ordenadas por menor carga.
  - Indicador visual de capacidad: barra horizontal verde/amarillo/naranja/rojo según heatmap del Reporte de Asignación.
- Selector "Ingeniero de Proyectos" (opcional para CP, obligatorio para DN):
  - Mismo formato.
- Selector "Trainee" (opcional):
  - Mismo formato. Footer "Recomendado solo para CP de baja complejidad".
- Fecha estimada de cierre del CP (datepicker, default = +5 días hábiles).
- Notas para el equipo (textarea).
- Checkbox: "Notificar por email además de in-app".

**Below the fold:**
- Mini heatmap horizontal del equipo de Costos+Proyectos+Trainee, mostrando carga del mes en curso. Click en persona → pre-rellena el selector correspondiente.

## Datos de ejemplo
- Sugerencias Ingeniero de Costos:
  - Ana López — 4 CP activos (verde) — sugerido
  - Erick Morales — 5 CP activos (amarillo)
  - Roberto Trainee — 7 CP activos (amarillo)
- Sugerencias Ingeniero de Proyectos:
  - Sergio Ordaz — 6 proyectos activos (amarillo)
  - Guillermo Gutiérrez — 9 proyectos activos (naranja)

## Estados
- **Loading:** spinner en sugerencias mientras se calcula carga.
- **Sin sugerencias disponibles:** "Todo el equipo está en sobrecarga. Considera replantear o esperar."
- **Sobrecarga al asignar:** warning amarillo "Esta persona quedará en 11 proyectos activos (sobrecarga). ¿Continuar?"
- **Éxito:** toast "CP asignado. Notificaciones enviadas." + redirect a `/oportunidades`.

## Responsive
- **Mobile:** una sola columna, primero resumen colapsable + formulario abajo.
- **Desktop:** 2 columnas como descrito.

## Edge cases
- Si el `gerente_proyectos` actual no tiene su equipo configurado en el sistema, mostrar warning: "No hay Ingenieros de Costos disponibles. Contacta a admin para sincronizar el equipo."
- Si asignas a alguien con rol `trainee_proyectos` como Ingeniero de Costos (no debería pasar), error: "Trainee no puede ser Ingeniero de Costos. Selecciona otro usuario."

## Referencias visuales
- Mini heatmap: ver mockup ya generado del Reporte de Asignación.
- Sugerencias inteligentes: GitHub assignee suggestions.

## Notas técnicas
- Sugerencias calculadas con scoring: peso 70% carga + 20% sublínea match + 10% historial.
- Notificación dispara `CpAsignadoNotification` con Reverb broadcast.
- Cambio de estado: `cp_aprobado` → `cp_asignado` en `proyecto_eventos`.
