# Pantalla 17 — Solicitud de Viáticos

**Ruta:** `/proyectos/{id}/viaticos`
**Módulo:** M7 Ejecución
**Hito:** H8
**Layout:** Sidebar + topbar + main

---

## Rol del usuario
`ingeniero_proyectos` (crea), `serv_generales` (Sergio Ordaz, primera aprobación), `direccion_general` (segunda aprobación). Replica `FO-GPT-SSGG-01-A`. Visto en `SOLICITUD_DE_VIATICOS_280226-060326.pdf`.

## Objetivo
Capturar solicitud formal de viáticos para movilización a sitio. Workflow de aprobación de 2 niveles. Generar PDF firmable.

## Componentes principales

**Header:**
- Título "Viáticos · DN-018/26" + subtítulo "Texmelucan, Puebla".
- Botón "+ Nueva solicitud".
- Tabs: "Pendientes (1)" | "Aprobadas (3)" | "Rechazadas (0)" | "Todas (4)".

**Lista de solicitudes (cards apiladas):**
Cada card muestra:
- Header:
  - Código `VTC-2025-018` + estado (Borrador / En aprobación nivel 1 / En aprobación nivel 2 / Aprobada / Rechazada).
  - Período (fechas inicio-fin).
  - Solicitante (avatar) → Sergio (Servs Grales) → Dirección.
- Body:
  - Personal asignado (avatares horizontales con días).
  - Total estimado USD: $5,847.50.
  - Justificación corta.
- Footer:
  - Indicador de progreso 2 pasos (nivel 1 / nivel 2).
  - Botones según estado: Editar, Aprobar, Rechazar, Ver PDF.

**Modal "Nueva solicitud":**
Form con secciones:

### Sección 1 — Datos generales
- Período: fecha inicio + fecha fin.
- Justificación (textarea, requerido, ej. "Movilización a Texmelucan para ejecución Hot Tap 30"x10"").
- Lugar de comisión (input).
- Centro de costo (auto-llenado del proyecto, read-only).

### Sección 2 — Personal
Tabla:
| Persona | Días | Categoría | Anticipo |
|---------|------|-----------|----------|
| Sergio Ordaz | 7 | Ingeniero | $X |
| Roberto Trainee | 7 | Trainee | $X |
| ... | | | |
- Multi-select de personas + auto-cálculo de días según período.
- Categoría afecta tabuladores.

### Sección 3 — Partidas
Tabla con conceptos por defecto:
- Hospedaje (monto/día × días × personas) editable.
- Alimentos (monto/día × días × personas) editable.
- Transporte aéreo (input).
- Transporte terrestre (input).
- Transporte local (input).
- Otros (peajes, estacionamientos, etc.).

Total automático abajo.

### Sección 4 — Adjuntos
- Boletos de avión (PDFs).
- Reservaciones de hotel.
- Otros documentos justificativos.

**Vista de detalle (drawer al hacer clic):**
- Datos completos + workflow visual de aprobación (timeline horizontal con pasos).
- Para `serv_generales`: botones Aprobar / Rechazar con campo razón.
- Para `direccion_general` (después de nivel 1): mismos botones.
- Botón "Generar PDF firmable" cuando aprobada.
- Si rechazada: muestra motivo y permite editar y reenviar.

## Datos de ejemplo (28-feb a 06-mar Texmelucan)
- VTC-2025-018 · Período 28-feb a 06-mar (7 días) · Personal: Sergio (IP) + Roberto (TR) + 2 soldadores · Total: $5,847.50 USD · Estado: Aprobada (nivel 1 OK por Sergio · nivel 2 OK por DG hace 2d).

## Estados
- **Borrador:** "Aún no enviada. Edita y envía a aprobación."
- **Nivel 1 pendiente:** badge amarillo "Esperando aprobación de Servs Generales".
- **Nivel 2 pendiente:** badge naranja "Esperando aprobación de Dirección".
- **Aprobada:** badge verde + botón "Descargar PDF firmable".
- **Rechazada:** badge rojo + motivo visible + CTA "Editar y reenviar".

## Responsive
- **Mobile:** cards a ancho completo, modal a 100%.
- **Desktop:** cards en lista vertical max 800px.

## Edge cases
- Si el monto excede el presupuesto del proyecto, warning amarillo: "Esta solicitud excede el 5% del presupuesto restante del proyecto. ¿Justificar?"
- Si el período no coincide con ventana de movilización planeada, warning: "El período no está dentro del cronograma del proyecto."
- Notificación automática a Sergio cuando hay solicitud pendiente nivel 1.
- Si Sergio rechaza, vuelve a borrador con motivo visible.

## Referencias visuales
- Workflow visual: estilo Asana approvals o Salesforce.
- Tabla de partidas: estilo expense report (Concur, Expensify).

## Notas técnicas
- Generación PDF replica `FO-GPT-SSGG-01-A` con DomPDF.
- Tabuladores configurables por categoría (admin).
- Tracking de gastos reales vs estimados al cierre del proyecto (post-mortem).
