# Pantalla 15 — Libro de Proyecto / Dossier ISO

**Ruta:** `/proyectos/{id}/libro`
**Módulo:** M6 Libro
**Hito:** H6
**Layout:** Sidebar + topbar + main

---

## Rol del usuario
`ingeniero_proyectos` (alimenta la mayoría), `qhse` (sección H Seguridad), `gerente_proyectos` (supervisa), `auditor_externo` (read-only). El Libro de Proyecto es el dossier ISO 9001/14001/45001 que se entrega al cliente al cierre. Estructura de **10 secciones obligatorias A-J** según decisión D11.

## Objetivo
Alimentar progresivamente las 10 secciones del dossier durante la ejecución del proyecto. Llegar a 100% antes del cierre formal.

## Componentes principales

**Header:**
- Título "Libro de Proyecto · DN-018/26 · Texmelucan".
- Indicador de avance global grande: "**67%** completado · 67 de 100 ítems del checklist".
- Barra de progreso `gpt-600` ancha.
- Subtítulo: "Apertura: 02-mar-2025 · Cierre estimado: 25-abr-2025".
- Botón principal `gpt-600`: "Generar Dossier Consolidado PDF" (disabled hasta 100% si setting activo).
- Botón secundario: "Exportar checklist a Excel".

**Acordeón vertical de las 10 secciones (A-J):**

Cada sección tiene:
- **Header del acordeón:**
  - Letra grande (A, B, C...) en círculo `gpt-600` blanco.
  - Nombre de sección + código formato (si aplica).
  - Barra de progreso con %.
  - Conteo "8 de 12 items" + chevron expand/collapse.
  - Estado: pendiente (gris) / en proceso (amarillo) / completo (verde con check).

- **Body al expandir:**
  - Descripción de la sección.
  - **Checklist editable** con items según sublínea (HTP en este caso):
    - Cada item: checkbox + descripción + (si completo) evidencia adjunta + responsable + fecha.
    - Items sin completar: muestran "Pendiente" + responsable asignado.
  - **Drag&drop area** "Arrastrar archivos aquí o clic para seleccionar".
  - **Lista de documentos** subidos en esta sección con:
    - Nombre archivo + versión + tamaño + subido por + fecha.
    - Acciones: ver, descargar, eliminar.
  - Textarea de observaciones generales de la sección.

**Las 10 secciones predefinidas:**
- A. Cronograma de Actividades
- B. Ingeniería de Proyecto (planos, memorias de cálculo)
- C. Permisos (cliente, autoridades, internos)
- D. Estudios (memorias de cálculo, plan de calidad)
- E. Procedimientos (operativos, soldadura, calidad)
- F. Certificados (personal, equipos, accesorios, materiales)
- G. Registro de Pruebas (NDT, hidrostática, hermeticidad)
- H. Seguridad (IMSS, DC-3, AST, plan emergencias)
- I. Ejecución (bitácoras, reportes semanales)
- J. Misceláneos (BOM/BOE, oficios, organigramas)

**Sidebar derecho (sticky en desktop):**
- Mini-resumen visual de las 10 secciones con sus % (mini bars verticales).
- Sección "Bloqueos para cierre":
  - Listado de items pendientes que bloquean el cierre.
  - CTA "Asignar pendientes a responsables".
- Botón "Validar dossier completo".

## Datos de ejemplo (Texmelucan en progreso)
- Sección A Cronograma: 100% (1/1 item: cronograma vigente cargado).
- Sección B Ingeniería: 75% (3/4 items: planos OK, memoria OK, ATEX falta).
- Sección C Permisos: 100% (3/3 items).
- Sección D Estudios: 0% (pendiente).
- Sección E Procedimientos: 90% (9/10 items).
- Sección F Certificados: 80% (8/10 items: faltan certificados de soldadores 2/4).
- Sección G Pruebas: 50% (4/8 items, hidrostática programada).
- Sección H Seguridad: 100% (5/5 items: IMSS, DC-3, AST, etc.).
- Sección I Ejecución: 60% (bitácoras al día, reportes semanales 3/5).
- Sección J Misceláneos: 50% (BOM cargado, organigrama falta).

## Estados
- **Sin abrir:** "El libro se abre automáticamente al adjudicar el proyecto. Estado: aún no abierto."
- **Sección bloqueada:** si responsable de sección no está activo, badge "Sin responsable" rojo.
- **Setting `bloqueo_cierre_dossier_incompleto=true`:** banner amarillo si <100%: "El cierre formal del proyecto está bloqueado hasta completar el dossier."
- **100% completo:** banner verde "Dossier completo. Listo para generar PDF consolidado y proceder al cierre."

## Responsive
- **Mobile:** acordeón a ancho completo, sidebar derecho se mueve a abajo.
- **Tablet:** acordeón principal + sidebar lateral colapsable.
- **Desktop:** layout 2 columnas con sidebar sticky.

## Edge cases
- Cada sublínea (HTP, LSP, VLV, SOL, SG) tiene un checklist diferente. Mostrar el correspondiente al `proyecto.sublinea`.
- Si setting es false, permitir cierre con dossier incompleto pero loggear evento.
- Versionado de archivos: si subes un archivo con mismo nombre, modal "¿Sobrescribir o crear nueva versión?"
- Responsabilidad delegada: cada sección puede tener responsable distinto. Solo el responsable o `gerente_proyectos` puede marcar items como completos.

## Referencias visuales
- Acordeón con avance: estilo Linear cycles + GitHub issues nested.
- Drag&drop: estilo Notion file upload.

## Notas técnicas
- Cálculo de `% avance global = avg(% por sección)`.
- Cálculo de `% avance sección = (items completados / total items checklist) * 100`.
- Generación de PDF consolidado con Browsershot (DomPDF no alcanza para concatenar múltiples PDFs).
- Hash MD5 del PDF final para integridad.
- Storage en S3 con versionado activo.
