# Pantalla 16 — Bitácora Diaria (mobile-first)

**Ruta:** `/proyectos/{id}/bitacora-diaria`
**Módulo:** M7 Ejecución
**Hito:** H8
**Layout:** Mobile-first, sidebar+topbar en desktop pero diseño optimizado para captura en sitio

---

## Rol del usuario
`ingeniero_proyectos` (en sitio con celular o tablet). Captura diaria de actividades en obra (replica `FO-GPT-PYT-01-D`). Usado durante movilización a sitio (Texmelucan, Atasta, plataformas marinas, etc.).

## Objetivo
Capturar rápido (en <3 minutos) la bitácora del día con personal, equipos en sitio, actividades y V°B° del cliente. Funcional offline-first idealmente.

## Componentes principales

**Header (mobile):**
- Botón back.
- Título "Bitácora · DN-018/26".
- Fecha de la bitácora (datepicker, default = hoy).
- Indicador status: Borrador / Firmada.

**Stepper vertical (en mobile, horizontal en desktop):**
1. Personal en sitio
2. Equipos en sitio
3. Actividades del día
4. V°B° del cliente
5. Submit

### Paso 1 — Personal en sitio
- Lista pre-cargada del equipo asignado al proyecto + opción "+ Agregar persona".
- Por cada persona: switch "Presente hoy" + horas trabajadas (input numérico) + horas extra (opcional).
- Sub-contratistas: input nombre + empresa + horas.

### Paso 2 — Equipos en sitio
- Lista pre-cargada del BOE del proyecto + opción "+ Agregar equipo".
- Por cada equipo: switch "En sitio" + horómetro/lectura (opcional) + status (operativo/falla/mantenimiento).

### Paso 3 — Actividades del día
- Textarea grande con placeholder "Describe lo realizado hoy (puedes usar shortcuts: /soldadura, /htap, /pruebas, /movilizacion)".
- Shortcuts insertables que abren mini-templates.
- Botón "Agregar foto" (acceso a cámara o galería).
- Galería de fotos del día (preview).
- Toggle "Reportar desviación" — si activo, abre form: tipo (técnica/seguridad/calidad/cliente) + severidad (baja/media/alta) + descripción.

### Paso 4 — V°B° del cliente
- Input nombre del representante del cliente.
- Input organización.
- Datepicker fecha (default hoy).
- **Captura de firma** (canvas para firmar con dedo en mobile o mouse).
- O alternativa: "Subir foto de firma física".

### Paso 5 — Submit
- Resumen completo de la bitácora.
- Checkbox "Confirmo que la información es correcta".
- Botón principal `gpt-600` ancho completo: "Firmar y enviar".
- Botón secundario: "Guardar borrador".

**Vista de listado de bitácoras (cuando entras a la sección):**
Lista de fechas en orden cronológico con:
- Fecha + estado (firmada/borrador) + responsable que firmó.
- Preview corto de actividades.
- Tap para ver completa o editar (si borrador).

## Datos de ejemplo (Texmelucan día 12-mar-2025)
- Personal: Sergio Ordaz (8h), Roberto Trainee (8h), 2 soldadores subcontratados (10h).
- Equipos: T-1200 operativo, soldadora MIG operativa, camión grúa.
- Actividades: "Soldadura de housing en línea 30". Preparación bridas 10". Inspección NDT realizada con éxito. Sin desviaciones."
- 3 fotos adjuntas.
- V°B°: Ing. Juan Pérez (IGASAMEX) firmó.

## Estados
- **Sin bitácora del día:** "Aún no capturas la bitácora de hoy" + CTA "Iniciar bitácora".
- **Borrador:** indicador "Guardado hace 5min" + alerta "No olvides firmar al final del día".
- **Firmada:** lock + "Firmada por Sergio Ordaz a las 18:30 · V°B° Ing. Juan Pérez".
- **Bitácora con desviación:** badge rojo "Desviación reportada" en lista.

## Responsive
- **Mobile (prioritario):** layout vertical full-screen con stepper grande, inputs grandes para captura con dedo, botones de 48px+ height. Cámara nativa para fotos. Canvas de firma a ancho completo.
- **Tablet:** similar pero con padding lateral.
- **Desktop:** layout en 2 columnas (form izquierda, resumen derecha) sin perder funcionalidad mobile.

## Edge cases
- Si no hay conexión: guardar localmente y sync al regresar.
- Si pasa día sin bitácora, notificación push al `ingeniero_proyectos`.
- Si bitácora reporta desviación, notificación inmediata al `gerente_proyectos`.
- Si V°B° del cliente es el mismo del día anterior, sugerir auto-fill.

## Referencias visuales
- App nativa móvil: estilo Procore o PlanGrid (apps de construcción mobile).
- Canvas de firma: estilo DocuSign mobile.

## Notas técnicas
- PWA con offline support (Service Worker + IndexedDB).
- Compresión de imágenes antes de upload.
- Detección de keywords ("retraso", "falla", "incidente") para auto-tag de desviación.
- Integración con `reportes_semanales` (auto-generación con bitácoras de la semana).
