# Pantalla 09 — Solicitudes internas (Compras / Ingeniería)

**Ruta:** `/proyectos/{id}/solicitudes-internas`
**Módulo:** M3 Cotización
**Hito:** H3
**Layout:** Sidebar + topbar + main

---

## Rol del usuario
`ingeniero_costos` (crea), `compras` (responde requisiciones), `ingenieria_diseño` (responde órdenes de trabajo). Durante la fase Planificación de un CP, se solicita información a otras áreas: cotizaciones a Compras (`RE-GPT-COM-01-A`) y especificaciones a Ingeniería (`FO-GPT-ING-01-A`).

## Objetivo
Solicitar y dar seguimiento a información de Compras (precios de materiales/equipos) e Ingeniería (factibilidad técnica, dimensiones, especificaciones). Estas respuestas alimentan después las partidas de la cotización.

## Componentes principales

**Header:**
- Título "Solicitudes internas · CP-003/25" + subtítulo "Texmelucan · Pendientes: 2 · Resueltas: 5".
- Tabs: "Todas (7)" | "Compras (4)" | "Ingeniería (3)".
- Botón principal `gpt-600`: "+ Nueva solicitud" (abre dropdown: Requisición a Compras / Orden de Trabajo a Ingeniería).

**Lista de solicitudes (cards apiladas verticales):**
Cada card muestra:
- Header del card:
  - Badge tipo: "Requisición Compras" (azul) o "Orden de Trabajo Ingeniería" (naranja).
  - Código formato: `RE-GPT-COM-01-A · #SOL-024` o similar.
  - Estado: badge (Pendiente / En revisión / Respondida / Cancelada).
  - Fecha solicitud + fecha respuesta requerida (con dot rojo si vencida).
  - Solicitante (avatar) → Asignado a (avatar).
- Body:
  - Título (resumen).
  - Tabla pequeña de items: descripción, cantidad, unidad, especificación (read-only para solicitante, editable para asignado).
- Footer:
  - Si pendiente: "Esperando respuesta hace 2 días".
  - Si respondida: pequeño preview de la respuesta + botón "Ver respuesta completa".
  - Acciones: Editar (solo solicitante), Cancelar, Marcar como respondida (asignado).

**Modal "Nueva Requisición a Compras":**
Form con:
- Título (input).
- Items dinámicos: descripción, cantidad, unidad (select), especificación detallada (textarea), observaciones (opcional).
- Fecha respuesta requerida (datepicker).
- Asignar a (multi-select de usuarios con rol `compras`).
- Adjuntar especificaciones técnicas o planos (drag&drop file).
- Notas para el responsable (textarea).

**Modal "Nueva Orden de Trabajo a Ingeniería":**
Similar al anterior pero con campos específicos:
- Tipo de trabajo (select: Diseño, Cálculo, Especificación, Validación).
- Disciplina (select: Mecánico, Eléctrico, Civil, Procesos).
- Resto idéntico.

**Drawer al hacer clic en una solicitud (o Modal "Ver respuesta completa"):**
Side drawer 600px desde derecha mostrando:
- Datos completos de la solicitud.
- Form de respuesta (visible solo para asignado y `gerente_proyectos`):
  - Por cada item: precio unitario, tiempo de entrega, proveedor sugerido, observaciones.
  - Adjuntos de respuesta (cotizaciones de proveedores escaneadas, especificaciones).
  - Notas finales.
  - Botón "Marcar como respondida" cierra y notifica al solicitante.

## Datos de ejemplo
- Requisición #SOL-024 · "Cotización válvulas 30" y 10" para Hot Tap" · 4 items · pendiente · solicitada hace 3 días, vence en 1 día.
- Orden de Trabajo #OT-008 · "Cálculo de procedimiento Hot Tap línea 30"x10"" · respondida ayer.

## Estados
- **Vacío:** "No hay solicitudes internas todavía. Crea la primera para alimentar tu cotización."
- **Vencida:** badge rojo "Vencida hace N días" + dot rojo en lista.
- **Respondida sin revisar:** badge dorado "Nueva respuesta — clic para ver" + dot.

## Responsive
- **Mobile:** cards a ancho completo, drawer ocupa 100%.
- **Desktop:** cards en lista vertical max 800px, drawer 600px.

## Edge cases
- Si una solicitud no tiene asignado, badge "Sin asignar" en rojo y CTA en card "Asignar ahora".
- Si una solicitud lleva >7 días sin respuesta, escalación visual: borde izquierdo rojo + notificación al `gerente_proyectos`.

## Referencias visuales
- Cards de solicitud: estilo Linear comments o GitHub issues compactos.
- Drawer de respuesta: estilo Slack thread.

## Notas técnicas
- Generación PDF de respuesta replica los formatos `RE-GPT-COM-01-A` y `FO-GPT-ING-01-A`.
- Notificaciones automáticas al solicitante cuando hay respuesta.
- Las solicitudes resueltas alimentan las partidas de cotización con un click "Importar a partida".
