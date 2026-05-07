# Pantalla 21 — Cierre mensual SAT vs Gerencial

**Ruta:** `/finanzas/cierres-mensuales`
**Módulo:** M12 Finanzas
**Hito:** H13
**Layout:** Sidebar + topbar + main

---

## Rol del usuario
`cfo` (Denisse Ramírez), `direccion_general`, `socio`, `comite_socios`, `analista_financiero`. **Acceso restringido por middleware `EnsureFinanzasAccess`.** El "cierre bueno" del que habla la dirección — decisión D1.

## Objetivo
Generar dos vistas del cierre mensual:
1. **Cierre SAT** (contable, lo que se factura).
2. **Cierre Gerencial** (vista real: SAT base + devengado + pipeline ponderado).

Permite comparar SAT vs Gerencial para entender la "salud real" del negocio vs la fiscal.

## Componentes principales

**Header:**
- Título "Cierres mensuales" + subtítulo "Marzo 2026".
- Selector año + mes.
- Botón "Generar cierre del mes" (si no existe aún).
- Botón "Exportar PDF" (cuando existe).
- Tabs: "**Cierre SAT**" | "**Cierre Gerencial**" | "Comparativa".

### Tab 1 — Cierre SAT (vista contable)
Card único:
- Header: "Cierre SAT · Marzo 2026 · Estado: Cerrado por Denisse Ramírez el 02-abr-2026".
- Tabla simple de facturas emitidas:
  - Folio · Cliente · Proyecto (DN) · Fecha · Monto USD.
- Total facturado del mes: $187,520.45 USD.
- Toggle "Ver detalle" expande tabla con observaciones.

### Tab 2 — Cierre Gerencial (las 3 secciones según D1)
Layout en 3 cards apiladas verticales:

**Card 1 — SAT base** (color `slate-100` neutro)
- Header: "Sección 1: SAT base — facturado del mes".
- Total: $187,520.45 USD.
- Sub-tabla compacta (ver Tab 1).

**Card 2 — Devengado** (color `gpt-100` naranja claro)
- Header: "Sección 2: Devengado — cartera comprometida no facturada".
- Subtítulo: "Proyectos con OC firmada cuyo avance del mes no se facturó aún".
- Tabla:
  | DN | Proyecto | Cliente | Avance del mes | Método distribución | Monto devengado |
  |----|----------|---------|---------------|---------------------|-----------------|
  | DN-018/26 | HT 30"x10" Texmelucan | IGASAMEX | 65% completado | Días naturales | $63,048.89 |
  | DN-021/26 | LS 24" Atasta | PROTEXA | 40% completado | Hitos | $75,000.00 |
- Total devengado: $138,048.89 USD.

**Card 3 — Pipeline ponderado** (color `blue-50` azul claro)
- Header: "Sección 3: Pipeline ponderado — proyectos no firmados ajustados por probabilidad".
- Subtítulo: "Cotizaciones presentadas con su probabilidad de adjudicación".
- Tabla:
  | CP | Proyecto | Cliente | Monto cotizado | % Probabilidad | Monto ponderado |
  |----|----------|---------|----------------|----------------|-----------------|
  | CP-001/26 | HT 16" gas | ENGIE | $58,900 | 70% | $41,230 |
  | CP-007/26 | Servicios SOL | SEDENA | $2,150,000 | 60% | $1,290,000 |
- Total pipeline ponderado: $1,850,330.55 USD.

**Footer del Tab 2:**
- Total cierre gerencial: SAT + Devengado + Pipeline = **$2,175,899.89 USD**.
- Comparativa visual: barra apilada mostrando los 3 componentes.

### Tab 3 — Comparativa
Visualización lado a lado:
- Card SAT: $187,520.45 (lo que ve el SAT).
- Card Gerencial: $2,175,899.89 (lo que la dirección debería ver).
- Delta: $1,988,379.44 (1059% más).
- Gráfica waterfall: SAT base → +Devengado → +Pipeline ponderado = Total Gerencial.
- Histórico: gráfica de líneas últimos 12 meses con ambas series (SAT en gris, Gerencial en gpt-600).

**Sidebar derecho (sticky):**
- Estado del cierre: Borrador / En revisión / Cerrado.
- Workflow:
  - Generado por (avatar) + fecha.
  - Revisado por CFO (avatar) + fecha o "pendiente".
  - Aprobado por DG (avatar) + fecha o "pendiente".
- Botón "Aprobar y bloquear" (solo CFO+DG).
- Auditoría: timeline de todos los cambios.

## Datos de ejemplo (Marzo 2026)
- SAT: $187,520.45 (3 facturas: IGASAMEX $96,998.30 + PROTEXA cobro hito 1 $80,000 + ESENTIA suministro $10,522.15).
- Devengado: $138,048.89 (DN-018 al 65% + DN-021 al 40%).
- Pipeline: $1,850,330.55 (16 cotizaciones presentadas con probabilidades variables).

## Estados
- **Sin cierre del mes:** botón grande "Generar cierre del mes" + estimación de tiempo "(~30 segundos)".
- **Generando:** progress bar con pasos: "Calculando SAT base..." → "Calculando devengado..." → "Calculando pipeline ponderado..." → "Listo".
- **Borrador:** indicador "Borrador — pendiente de revisión".
- **Cerrado:** lock + descarga de PDF.

## Responsive
- **Mobile:** cards apiladas, tabs scroll horizontal, sidebar derecho colapsa al final.
- **Desktop:** layout 2 columnas (main + sidebar) con cards full-width.

## Edge cases
- Si no hay facturas emitidas en el mes: SAT base = $0 con mensaje "Sin facturación en este mes".
- Si no hay proyectos en ejecución: Devengado = $0.
- Si no hay cotizaciones presentadas: Pipeline = $0.
- Si % probabilidad cambia retroactivamente, advertencia "Recalcular cierre" para mantener consistencia.
- Acceso negado para roles sin permiso: redirect con mensaje "No tienes permisos para acceder a Finanzas".

## Referencias visuales
- Cards de secciones diferenciadas con colores semánticos (slate, naranja, azul).
- Waterfall chart: estilo financial reports (Excel waterfall, Mercury accounting).
- Comparativa: estilo Stripe Sigma reports.

## Notas técnicas
- Acceso protegido por middleware `EnsureFinanzasAccess`.
- Generación calcula on-the-fly + persiste en `cierres_mensuales` + `cierres_secciones` + `cierres_lineas`.
- Snapshots inmutables al cerrar formalmente.
- Auditoría inmutable en `finanzas_audit`.
- Plurianualidad respetada según `proyecto.metodo_distribucion_plurianual`.
