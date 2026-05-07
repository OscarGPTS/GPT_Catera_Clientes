# Pantalla 06 — Nueva oportunidad (wizard)

**Ruta:** `/oportunidades/nueva`
**Módulo:** M2 Comercial
**Hito:** H2
**Layout:** Sidebar + topbar + main

---

## Rol del usuario
`comercial`, `director_dn`. Crea una nueva oportunidad que disparará la generación de un CP (Costo y Presupuesto) atómico.

## Objetivo
Capturar resumen ejecutivo (replica `FO-GPT-VTS-01-B`) y enviarlo al Comité Comercial para aprobación. Una vez aprobado, asigna automáticamente CP.

## Componentes principales

**Header:**
- Título "Nueva oportunidad" + subtítulo "Costo y Presupuesto se asignará al guardar".
- Botón "Cancelar" ghost.
- Botón "Guardar como borrador".
- Botón principal `gpt-600`: "Enviar a Comité Comercial".

**Stepper horizontal (4 pasos):**
1. Datos del cliente
2. Resumen ejecutivo
3. Asignación
4. Revisión

### Paso 1 — Datos del cliente
Form en columna única max 600px:
- Cliente (combobox con búsqueda en catálogo, opción "+ Nuevo cliente" abre modal lateral)
- Contacto principal (multi-select de contactos del cliente)
- Usuario final (texto libre, ej. "CENAGAS", "PEMEX TRI", "PEMEX EXP")
- Sector (select: Defensa, Transporte y distribución, E&P, Refinación, Generación, Otros)
- Sublínea (select: HTP, LSP, VLV, SOL, SG)
- Origen de la oportunidad (select: Licitación pública, Invitación directa, Recompra, Recomendación, Otros)

### Paso 2 — Resumen ejecutivo
- Descripción del alcance (textarea grande, mínimo 100 chars)
- Cantidad estimada de partidas (número)
- Monto preliminar estimado USD (input con formato moneda)
- Plazo estimado de ejecución (días naturales + selector mes inicio + mes fin para plurianualidad — D8)
- Probabilidad de adjudicación (slider 0-100% con valor)
- Hitos de pago propuestos (UI tipo "Agregar hito": % + descripción, ej. 50% anticipo + 50% al finalizar)
- Adjuntar bases de licitación (drag&drop file)

### Paso 3 — Asignación
- Director DN responsable (select de users con rol `director_dn`)
- Sugerir asignación inicial al equipo (opcional, puede dejarse para después de aprobación):
  - Gerente de Proyectos (select)
  - Ingeniero de Costos sugerido
- Notas internas (textarea opcional)

### Paso 4 — Revisión
- Resumen completo en read-only de los 3 pasos previos.
- Texto destacado: "Al enviar, se asignará el CP **CP-XXX/26** atómicamente y se notificará al Comité Comercial."
- Checkbox: "Confirmo que la información es correcta".
- Botón final "Enviar a Comité Comercial".

## Datos de ejemplo
Pre-llenar con caso Texmelucan al hacer "Demo data":
- Cliente: IGASAMEX | Contacto: Ing. Juan Pérez | Usuario final: CENAGAS
- Sector: Transporte y distribución | Sublínea: HTP
- Descripción: "Servicio de Hot Tapping en línea de 30" derivación 10" en Texmelucan, Puebla."
- Monto preliminar: $90,000 USD | Plazo: 45 días naturales | Probabilidad: 70%
- Hitos: 50% anticipo + 50% al finalizar perforación

## Estados
- **Loading al guardar:** spinner en botón.
- **Error de validación:** errores inline rojos por campo.
- **Éxito:** modal de confirmación "Oportunidad creada con CP-024/26 · Notificación enviada al Comité" + botón "Ir a /oportunidades" o "Crear otra".

## Responsive
- **Mobile:** stepper colapsa a "Paso N de 4 — Título". Form en columna única.
- **Desktop:** stepper visible siempre arriba, form centrado max 720px.

## Edge cases
- Si el monto excede $5M USD, modal warning: "Oportunidad de alto valor. Notificarás directamente a Dirección General."
- Si la sublínea SOL y cliente SEDENA, mostrar alerta amarilla: "Esta combinación tiene 82% del pipeline actual. Considera distribución."
- Si el cliente no existe, "+ Nuevo cliente" abre modal con form mínimo (razón social, alias 3 letras, RFC opcional, sector).

## Referencias visuales
- Wizard pattern: estilo Stripe onboarding.

## Notas técnicas
- Asignación atómica de CP usa tabla `secuencias` con `lockForUpdate`.
- Tech Reference NO se genera aquí, sino en fase Ejecución del CP (procedimiento 5.3.4).
