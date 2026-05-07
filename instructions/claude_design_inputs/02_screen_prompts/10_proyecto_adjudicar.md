# Pantalla 10 — Adjudicar proyecto (CP → DN)

**Ruta:** `/proyectos/{id}/adjudicar`
**Módulo:** M4 Adjudicación
**Hito:** H5
**Layout:** Sidebar + topbar + main

---

## Rol del usuario
`director_dn` (notifica adjudicación), `gerente_proyectos` (ejecuta adjudicación). Cuando el cliente firma orden de compra y se acepta la cotización, se transforma el CP en DN (Desarrollo de Negocios).

## Objetivo
Capturar formalmente la adjudicación. Subir orden de compra del cliente. Asignar número DN atómicamente. Registrar hitos de pago definitivos. Disparar la creación de minuta de entrega (siguiente pantalla).

## Componentes principales

**Header:**
- Título "Adjudicar CP-003/25" + subtítulo "HT 30"x10" Texmelucan · IGASAMEX · Cotización v2 · $96,998.30 USD".
- Banner informativo `gpt-50` con icono info: "Al adjudicar, se asignará el DN automáticamente y se creará la minuta de entrega."
- Botón "Cancelar" ghost.
- Botón principal `gpt-600`: "Adjudicar y crear DN" (deshabilitado hasta llenar form).

**Layout 2 columnas:**

### Columna izquierda (60%) — Form de adjudicación

**Sección 1: Documento del cliente**
- Tipo de documento (radio): Orden de compra / Contrato / Pedido / Otro.
- Número de OC del cliente (input).
- Fecha de emisión (datepicker).
- Monto adjudicado USD (input, default = precio venta final de cotización).
- Moneda (selector con tipo de cambio si MXN).
- Adjuntar OC firmada (drag&drop, requerido) — PDF o imagen.

**Sección 2: Hitos de pago definitivos**
Repetidor de filas:
- % del monto · Descripción · Fecha estimada de cobro
- Default pre-llenado de la cotización: 50% anticipo / 50% al finalizar perforación.
- Editable. Validación: suma = 100%.

**Sección 3: Datos contractuales**
- Vigencia del contrato (días naturales).
- Fianza de cumplimiento requerida (switch + monto si activo).
- Fianza de anticipo (switch + monto).
- Garantía vicios ocultos (switch + duración).
- Penalizaciones por retraso (textarea).
- Notas contractuales (textarea).

**Sección 4: Equipo asignado al DN**
Sugerido pre-llenado de quien tomó el CP, editable:
- Director DN (auto del CP, read-only)
- Gerente de Proyectos (selector)
- Gerente de Operaciones (selector)
- Ingeniero de Proyectos (obligatorio para DN)
- Trainee (opcional)

### Columna derecha (40%) — Resumen y validaciones

Card sticky:
- Tech Reference confirmado.
- CP origen → DN destino: "CP-003/25 → **DN-018/26** (se asignará al guardar)".
- Validaciones en checklist:
  - ✓ Cotización aprobada
  - ✓ Documento del cliente cargado
  - ✓ Hitos suman 100%
  - ⚠ Falta asignar Ingeniero de Proyectos
  - ✓ Equipo de operaciones disponible
- Próximos pasos automáticos al adjudicar:
  1. Asignar DN-018/26 atómicamente.
  2. Cambiar estado a "Adjudicado pendiente firma".
  3. **Generar minuta de entrega CP→DN** (paso siguiente, obligatorio si setting activo).
  4. Abrir Libro de Proyecto con 10 secciones.
  5. Notificar equipo asignado.

## Datos de ejemplo (Texmelucan adjudicado)
- Cliente: IGASAMEX | OC #IGA-2025-1487 | Fecha: 28-feb-2025 | Monto: $96,998.30 USD.
- Hitos: 50% anticipo ($48,499.15) cobro mar-2025 / 50% al finalizar perforación ($48,499.15) cobro abr-2025.
- Equipo: GP=Fernando Basave / GO=Jesús Becerra / IP=Sergio Ordaz / TR=Roberto Trainee.

## Estados
- **Loading al guardar:** spinner con mensaje "Asignando DN, creando libro de proyecto, notificando equipo...".
- **Validación incompleta:** botón principal disabled + lista de items pendientes en columna derecha.
- **Éxito:** modal de confirmación con resumen + botón "Ir a Minuta de Entrega" o "Ir a la ficha del proyecto".

## Responsive
- **Mobile:** una sola columna, validaciones se mueven al final.
- **Desktop:** 2 columnas como descrito.

## Edge cases
- Si setting `minuta_entrega_obligatoria=true`, el botón cambia a "Adjudicar y proceder a Minuta" (forzando el siguiente paso).
- Si hitos no suman 100%, error inline: "Los hitos deben sumar 100%, suman 95%."
- Si el Ingeniero de Proyectos asignado tiene >10 proyectos activos, warning amarillo: "Sergio Ordaz tendrá 11 proyectos activos. ¿Continuar?"
- Si el monto adjudicado difiere >5% del precio venta de la cotización, warning: "El monto adjudicado tiene 8% de variación respecto a la cotización. Documenta la razón en notas."

## Referencias visuales
- Form con stepper integrado: estilo Stripe Connect onboarding.

## Notas técnicas
- Asignación atómica DN con `secuencias.lockForUpdate`.
- Disparar `MinutaEntregaCreatedEvent` que abre la siguiente pantalla.
- `AperturaLibroProyectoJob` se dispara async.
- Snapshot inicial en `proyecto_eventos` con tipo `cp_adjudicado`.
