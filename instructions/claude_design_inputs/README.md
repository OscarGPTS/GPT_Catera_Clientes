# Paquete de inputs para Claude Design — GPT Services Platform

Este paquete contiene todo lo necesario para alimentar Claude Design (la herramienta de Anthropic) y generar los 22 mockups de alta fidelidad de la plataforma GPT Services.

---

## Cómo usar este paquete

Claude Design recibe 4 tipos de input:
1. **Design System** — tokens, paleta, tipografía, componentes (carpeta `01_design_system/`).
2. **Screenshots** — referencias visuales (carpeta `03_reference_screenshots/`).
3. **Codebase** — opcional, repo Laravel (cuando exista).
4. **Figma file** — opcional, no aplica todavía.

### Flujo recomendado

**Paso 1 — Cargar el Design System en Claude Design**
- Ir a Claude Design → "Add design system".
- Subir el archivo `01_design_system/DESIGN_SYSTEM.md`.
- (Opcional) Subir además `01_design_system/tokens.json` para tokens estructurados y `01_design_system/components_catalog.md` para el catálogo de componentes.

**Paso 2 — Cargar las screenshots de referencia**
- En Claude Design → "Add screenshot".
- Subir todos los archivos `.png` de `03_reference_screenshots/`. Son los 5 mockups previos que ya validaste y que sirven como ground truth visual.

**Paso 3 — Generar las pantallas una por una**
- Por cada pantalla en `02_screen_prompts/`, abrir el archivo `.md` correspondiente.
- Copiar todo el contenido del archivo y pegarlo como prompt en Claude Design.
- Generar la pantalla.
- Iterar visualmente desde Claude Design según necesidad.

### Por qué este formato

- **Cada pantalla en un archivo separado**: evita perder contexto y permite iterar pantallas individualmente sin afectar otras.
- **Prompts auto-contenidos**: cada uno tiene rol, casos de uso, datos de ejemplo (caso Texmelucan), componentes esperados, edge cases. Claude Design tiene todo lo que necesita sin pedirte aclaraciones.
- **Design system reutilizable**: una sola fuente de verdad para tokens, sin duplicar.

---

## Inventario de pantallas

Las 22 pantallas están numeradas según orden de implementación del plan paso a paso:

| # | Archivo | Pantalla | Módulo | Hito |
|---|---------|----------|--------|------|
| 01 | `01_login.md` | Login multi-proveedor | M0/M1 | H0 |
| 02 | `02_admin_usuarios.md` | Admin de usuarios | M1 | H1 |
| 03 | `03_admin_socios.md` | Admin de socios | M1 | H1 |
| 04 | `04_admin_rh_mapping.md` | Mapeo RH → rol | M1 | H1 |
| 05 | `05_oportunidades_lista.md` | Status de Ofertas | M2 | H2 |
| 06 | `06_oportunidad_nueva.md` | Wizard nueva oportunidad | M2 | H2 |
| 07 | `07_cp_asignar.md` | Asignación de CP a equipo | M2 | H2 |
| 08 | `08_cotizacion_editor.md` | Editor de cotización COSS | M3 | H3 |
| 09 | `09_solicitudes_internas.md` | Requisición a Compras / Orden a Ingeniería | M3 | H3 |
| 10 | `10_proyecto_adjudicar.md` | Adjudicación del proyecto | M4 | H5 |
| 11 | `11_minuta_entrega.md` | Stepper de minuta de entrega | M4 | H5 |
| 12 | `12_kom_cronograma.md` | KOM y cronograma del proyecto | M5 | H7 |
| 13 | `13_bom_boe.md` | BOM/BOE | M5 | H7 |
| 14 | `14_listado_suministros.md` | Listado de suministros con avance | M5 | H7 |
| 15 | `15_libro_proyecto.md` | Libro de proyecto / Dossier ISO | M6 | H6 |
| 16 | `16_bitacora_diaria.md` | Bitácora diaria mobile-first | M7 | H8 |
| 17 | `17_viaticos.md` | Solicitud de viáticos | M7 | H8 |
| 18 | `18_proyecto_cierre.md` | Cierre + carta finiquito + post-mortem | M8 | H9 |
| 19 | `19_reporte_asignacion.md` | Reporte de asignación con heatmap | M9 | H10 |
| 20 | `20_chat_notificaciones.md` | Chat embebido + bandeja de notificaciones | M10 | H11 |
| 21 | `21_finanzas_cierre_mensual.md` | Cierre mensual SAT vs gerencial | M12 | H13 |
| 22 | `22_dashboard_ejecutivo.md` | Dashboard ejecutivo de socios | M13 | H14 |

---

## Convenciones

Cada prompt sigue esta estructura para que Claude Design la entienda:

```
ROL DEL USUARIO: quién usa esta pantalla
OBJETIVO: para qué entra a esta pantalla
COMPONENTES PRINCIPALES: qué debe contener
DATOS DE EJEMPLO: valores reales del caso Texmelucan
ESTADOS: vacío, cargando, normal, error
RESPONSIVE: comportamiento mobile/tablet/desktop
EDGE CASES: qué considerar
REFERENCIAS: cuáles screenshots aplicar
```

Todas las pantallas comparten el layout base (sidebar + topbar) ya documentado en el design system, salvo `01_login.md` que es full-screen sin layout.

---

## Iteración

Cuando Claude Design genere una pantalla y quieras refinarla:
- Si el cambio aplica a UN componente: editar el design system en `01_design_system/components_catalog.md` y regenerar.
- Si el cambio aplica a UNA pantalla: editar el prompt en `02_screen_prompts/` y regenerar solo esa.
- Si el cambio aplica a TODOS los mockups: editar tokens en `01_design_system/tokens.json`.

---

**Generado:** Mayo 2026 | **Versión:** 1.0
