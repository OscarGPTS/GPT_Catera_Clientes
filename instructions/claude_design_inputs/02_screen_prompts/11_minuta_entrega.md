# Pantalla 11 — Minuta de Entrega CP→DN (stepper 5 pasos)

**Ruta:** `/proyectos/{id}/minuta-entrega`
**Módulo:** M4 Adjudicación
**Hito:** H5
**Layout:** Sidebar + topbar + main

---

## Rol del usuario
`director_dn` (owner), `gerente_proyectos`, `gerente_operaciones`, `ingeniero_proyectos`. Documenta formalmente la entrega del proyecto desde Comercial hacia Proyectos. Replica `FO-GPT-PYT-01-A`. Esta minuta es **opcional por default pero configurable como obligatoria** (decisión D10).

## Objetivo
Crear acta firmable que documenta orden del día, acuerdos con responsables, y participantes. Genera PDF al firmar.

## Componentes principales

**Header:**
- Título "Minuta de Entrega · DN-018/26" + subtítulo "HT 30"x10" Texmelucan · CP-003/25 → DN-018/26".
- Indicador del setting: badge "Minuta opcional" (gris) o "Minuta obligatoria" (gpt-600) según `system_settings.minuta_entrega_obligatoria`.
- Botón "Saltar minuta" (visible solo si opcional, pequeño y secundario).
- Auto-save indicator: "Guardado hace 8s".

**Stepper horizontal (5 pasos):**
1. Datos básicos
2. Orden del día
3. Acuerdos
4. Participantes
5. Preview & firma

Estilo de stepper:
- Pasos completados: círculo `gpt-600` con check.
- Paso actual: círculo `gpt-600` con número, anillo de focus.
- Pasos pendientes: círculo `slate-200` con número en `slate-500`.

### Paso 1 — Datos básicos
Form columna única max 600px:
- Fecha de la reunión (datepicker, default = hoy).
- Hora inicio · Hora fin (time pickers).
- Modalidad (radio): Presencial / Virtual / Mixta.
- Si Virtual o Mixta: link de la reunión (input opcional).
- Lugar (input si Presencial o Mixta).
- Convocada por (auto = `director_dn` del proyecto, editable).

### Paso 2 — Orden del día
Repetidor de items (drag&drop reorder):
- Por cada punto: título corto + descripción opcional.
- Default pre-llenado con 4 puntos típicos:
  1. Presentación del proyecto adjudicado
  2. Revisión de alcance, tiempo y costo
  3. Definición de hitos de ejecución
  4. División de responsabilidades entre áreas
- Botón "+ Agregar punto".
- Al lado de cada punto, botón "Agregar acuerdo asociado" → pre-rellena en paso 3.

### Paso 3 — Acuerdos
Tabla editable de acuerdos:
| # | Acuerdo (descripción) | Responsable | Fecha compromiso | Punto OD |
|---|----------------------|-------------|-------------------|----------|
| 1 | Confirmar disponibilidad de equipo T-1200 | Jesús Becerra | 05-mar | 4 |
| 2 | Solicitar permisos a CENAGAS | Sergio Ordaz | 10-mar | 3 |
| 3 | Movilización personal a sitio | Sergio Ordaz | 15-mar | 4 |

Cada fila editable. Botón "+ Agregar acuerdo". Filtro por punto del orden del día.

### Paso 4 — Participantes
Multi-selector de usuarios:
- Pre-llenado: equipo del DN (Director DN, Gerente Proyectos, Gerente Operaciones, Ing. Proyectos, Trainee).
- Permite agregar invitados adicionales (otros usuarios del sistema o externos por email).
- Por cada participante:
  - Avatar + nombre + rol en el proyecto.
  - Switch "Requiere firma" (default ON para internos).
  - Si externo: input de email.

### Paso 5 — Preview & firma
- **Vista previa renderizada del PDF** (replica de `FO-GPT-PYT-01-A`):
  - Membretado GPT Services + logo.
  - Título "MINUTA DE ENTREGA DE PROYECTO" + código formato.
  - Datos del proyecto, fecha, modalidad.
  - Orden del día numerado.
  - Tabla de acuerdos.
  - Espacios de firma para cada participante.
- Botones:
  - "Volver a editar" (regresa a paso anterior).
  - "Guardar borrador" (no firma, queda en estado borrador).
  - "Firmar y emitir" (firma digital del usuario actual, dispara solicitud de firmas a otros participantes).

## Datos de ejemplo (Texmelucan)
- Reunión: 02-mar-2025 · 10:00-11:30 · Modalidad Mixta · Sala juntas + Zoom.
- Convocada por: Director DN (Director comercial fictional).
- Participantes: Director DN, Fernando Basave (GP), Jesús Becerra (GO), Sergio Ordaz (IP), Roberto (Trainee).

## Estados
- **Borrador (auto-save):** indicador "Guardado hace Ns", versión draft persiste.
- **Firmada parcial:** badge "3 de 5 firmas" + lista de pendientes.
- **Firmada completa:** badge verde "Minuta completa" + lock (read-only) + botón "Descargar PDF".
- **Bloqueada por setting obligatorio:** banner amarillo arriba "Esta minuta es obligatoria. No se puede pasar a En Ejecución sin firmar."

## Responsive
- **Mobile:** stepper colapsa a "Paso N/5". Form en columna única.
- **Desktop:** stepper visible siempre arriba.

## Edge cases
- Si un participante interno no tiene firma digital configurada, mostrar prompt para configurarla antes de firmar.
- Si un externo (cliente IGASAMEX por ejemplo) está en la lista, generar link único de firma (token) que se envía por email.
- Si la minuta lleva >5 días sin cerrarse (todas las firmas), notificación al `director_dn`.

## Referencias visuales
- Stepper: estilo Stripe onboarding o Linear setup.
- Preview de PDF: similar a DocuSign preview.

## Notas técnicas
- Auto-save cada 30s en `minutas_entrega` con status=borrador.
- Generación PDF replica `FO-GPT-PYT-01-A` con DomPDF.
- Firmas con timestamps en `minuta_entrega_participantes.firmado_at`.
- Si setting `minuta_entrega_obligatoria=true`, transición de estado a `en_ejecucion` se bloquea hasta firma completa.
