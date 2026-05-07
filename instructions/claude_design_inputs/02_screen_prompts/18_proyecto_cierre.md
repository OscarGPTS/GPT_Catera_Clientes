# Pantalla 18 — Cierre de proyecto (Carta Finiquito + Post-Mortem)

**Ruta:** `/proyectos/{id}/cierre`
**Módulo:** M8 Cierre
**Hito:** H9
**Layout:** Sidebar + topbar + main

---

## Rol del usuario
`ingeniero_proyectos`, `gerente_proyectos`. Cierra formalmente el DN una vez completada la ejecución. Genera Carta Finiquito (`RE-GPT-QHSE-106-D`) y Post-Mortem técnico-operativo.

## Objetivo
Validar que todos los requisitos para cierre están completos. Liberar formalmente personal y equipos. Capturar lecciones aprendidas y desviaciones.

## Componentes principales

**Header:**
- Título "Cierre · DN-018/26 · Texmelucan".
- Estado actual del proyecto: badge "En cierre" (gpt-100).
- Botón "Cancelar".
- Botón principal `gpt-600`: "Cerrar proyecto formalmente" (disabled hasta validaciones).

**Layout 2 columnas:**

### Columna izquierda (60%)

**Sección 1 — Validaciones para cierre**
Card con checklist de pre-requisitos:
- ✓ Cronograma 100% completado
- ✓ Bitácoras diarias firmadas (45/45 días)
- ✓ Reportes semanales enviados (7/7)
- ⚠ Dossier ISO al 92% (falta sección G "Pruebas")
- ✓ Viáticos cerrados con comprobantes
- ⚠ Post-Mortem pendiente
- ✓ V°B° cliente final firmado

Si setting `bloqueo_cierre_dossier_incompleto=true` y dossier <100%, banner rojo "El cierre está bloqueado hasta completar el dossier al 100%."

**Sección 2 — Carta Finiquito**
Form expandible:
- Fecha emisión (default hoy).
- Personal liberado: tabla con avatares + fecha liberación + observaciones.
- Equipos liberados: tabla con equipo + condición de retorno (operativo/mantenimiento/baja) + observaciones.
- Observaciones generales (textarea).
- Botón "Generar PDF de Carta Finiquito" → preview.
- Workflow de firmas:
  - Firma de GPT (interno): `gerente_proyectos`.
  - Firma del cliente (externo): link único enviado por email.

**Sección 3 — Post-Mortem Técnico-Operativo**
Form con plantilla estructurada:
- Fecha sesión (datepicker).
- Participantes (multi-select).
- **Métricas:**
  - Presupuesto planeado: $96,998.30 (auto)
  - Presupuesto real: input manual (gastos finales) + delta automático.
  - Tiempo planeado: 45 días (auto)
  - Tiempo real: input + delta.
  - Calidad: ¿hubo no-conformidades? (switch + descripción).
- **Lecciones aprendidas:** repetidor de items (categoría: técnica/operativa/comercial/seguridad + descripción + impacto).
- **Desviaciones:** repetidor (tipo + causa raíz + impacto + acción correctiva).
- **Recomendaciones de mejora:** repetidor (descripción + responsable de implementar + fecha objetivo).
- Botón "Generar PDF Post-Mortem".

### Columna derecha (40%) — Sidebar resumen

Card sticky:
- **Métricas finales** vs planeadas:
  - Costo: $96,998.30 vs $97,540.00 (+0.6%) → verde
  - Tiempo: 45 días vs 47 días (+4.4%) → amarillo
  - Margen real: 41.59% vs 41.59% (=) → verde
- **Equipo del proyecto**: avatares con horas/días aportados.
- **Documentos finales**:
  - Dossier ISO consolidado (link descarga).
  - Carta Finiquito firmada (cuando lista).
  - Post-Mortem PDF (cuando listo).
  - Reporte ejecutivo del proyecto.
- **Próximos pasos** automáticos al cerrar:
  1. Estado pasa a `cerrado`.
  2. Se libera capacidad del equipo.
  3. Se actualiza KPI ejecutivo de cierres.
  4. Se notifica al cliente con dossier consolidado.

## Datos de ejemplo (Texmelucan al cierre)
- Fecha real cierre: 17-abr-2025.
- Carta Finiquito: personal liberado 4 personas, equipos liberados 3 (T-1200 operativo, soldadora operativa, camión grúa devuelto).
- Post-Mortem:
  - Lecciones: "Hot Tap en línea 30" requiere 1 día más por verificación de presión" (técnica).
  - Desviaciones: "Bridas 10" llegaron con 3 días de retraso por proveedor" (compras).
  - Recomendaciones: "Anticipar OC de bridas 30 días antes" (compras, fecha 30-abr).

## Estados
- **Pendientes:** validaciones marcadas en rojo + bloqueo del cierre.
- **Listo para cerrar:** todos los checks verdes + botón habilitado.
- **Cerrado:** lock global + texto "Proyecto cerrado el 17-abr-2025 por Fernando Basave" + descarga de documentos.

## Responsive
- **Mobile:** una sola columna, sidebar derecho se mueve abajo.
- **Desktop:** layout 2 columnas como descrito.

## Edge cases
- Si setting `bloqueo_cierre_dossier_incompleto=false`, permite cerrar con dossier incompleto pero el evento se loggea como "cerrado_con_pendientes".
- Si setting `bloqueo_cierre_post_mortem_pendiente=true`, post-mortem es obligatorio.
- Si delta de costo >10%, modal de confirmación: "Hubo desviación significativa de costo. ¿Documentaste la razón en post-mortem?"
- Si la carta finiquito no es firmada por el cliente en 14 días, escalación al `director_dn`.

## Referencias visuales
- Cards de métricas con deltas: estilo Stripe analytics.
- Forms estructurados de post-mortem: estilo Linear retrospectives.

## Notas técnicas
- Generación PDF Carta Finiquito replica `RE-GPT-QHSE-106-D`.
- Post-Mortem alimenta KPI ejecutivo de "% proyectos con post-mortem".
- Al cerrar: `proyecto_eventos.tipo = 'proyecto_cerrado'`.
- Se libera asignación en `asignaciones_personas` próximo snapshot.
