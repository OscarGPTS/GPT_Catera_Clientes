# Design System — GPT Services Platform

Sistema de diseño consolidado para alimentar Claude Design. Basado en la decisión D5 del plan ejecutable v2.1.

---

## 1. Identidad de marca

**Empresa:** Tech Energy Control S.A. de C.V. (marca comercial: GPT Services)
**Sector:** Servicios industriales oilfield — Hot Tapping, Line Stopping, válvulas, soldadura
**Logo:** texto "GPT" en degradé naranja → rojo, sobre fondo blanco
**Tono:** Profesional, industrial, serio. Comunica precisión técnica, certificaciones ISO 9001/14001/45001 y experiencia en sectores de energía y defensa.

---

## 2. Paleta de colores

### Primario — naranja (gpt-orange)
Color del logo. Usar para botones primarios, links activos, acentos de marca.

| Token | Hex | Uso |
|-------|-----|-----|
| `gpt-50` | #FFF7ED | Fondos sutiles, hovers ligeros |
| `gpt-100` | #FFEDD5 | Fondos de badges informativos |
| `gpt-200` | #FED7AA | Bordes ligeros |
| `gpt-300` | #FDBA74 | Estados hover |
| `gpt-400` | #FB923C | Iconos secundarios |
| `gpt-500` | #F97316 | Acento puro (alterno al 600) |
| `gpt-600` | #EA580C | **Primario principal** — botones, links, indicadores |
| `gpt-700` | #C2410C | Hover de botones primarios |
| `gpt-800` | #9A3412 | Texto sobre fondos naranja claros |
| `gpt-900` | #7C2D12 | Texto sobre fondos muy claros |
| `gpt-950` | #431407 | Texto profundo, casi negro con tinte cálido |

### Secundario — rojo (gpt-red)
Color secundario del logo. Usar para alertas críticas, errores, badges de urgencia, indicadores rojos.

| Token | Hex | Uso |
|-------|-----|-----|
| `gpt-red-50` | #FEF2F2 | Fondos de alertas suaves |
| `gpt-red-100` | #FEE2E2 | Fondos de badges danger |
| `gpt-red-200` | #FECACA | Bordes de alertas |
| `gpt-red-300` | #FCA5A5 | — |
| `gpt-red-400` | #F87171 | — |
| `gpt-red-500` | #EF4444 | — |
| `gpt-red-600` | #DC2626 | **Crítico principal** — alertas, errores, eliminar |
| `gpt-red-700` | #B91C1C | Hover de acciones críticas |
| `gpt-red-800` | #991B1B | Texto sobre fondos rojo claros |
| `gpt-red-900` | #7F1D1D | Texto crítico |
| `gpt-red-950` | #450A0A | — |

### Neutros — slate (Tailwind estándar)
Fondos, bordes, texto. Tono frío para sensación profesional/industrial.

| Token | Hex | Uso |
|-------|-----|-----|
| `slate-50` | #F8FAFC | **Fondo principal del área de trabajo** |
| `slate-100` | #F1F5F9 | Fondos de filas zebra, hovers |
| `slate-200` | #E2E8F0 | **Bordes default (0.5px)** |
| `slate-300` | #CBD5E1 | Bordes énfasis, dividers |
| `slate-400` | #94A3B8 | Iconos deshabilitados, placeholders |
| `slate-500` | #64748B | Texto secundario |
| `slate-600` | #475569 | Texto secundario fuerte |
| `slate-700` | #334155 | Texto secundario muy fuerte |
| `slate-800` | #1E293B | Sidebar oscuro |
| `slate-900` | #0F172A | **Sidebar principal**, texto principal |

### Semánticos

| Concepto | Color base | Hex | Uso |
|----------|-----------|-----|-----|
| Success | green-600 | #16A34A | Adjudicado, aprobado, completado |
| Warning | amber-600 | #D97706 | En revisión, pendiente, atención |
| Danger | gpt-red-600 | #DC2626 | Rechazado, urgente, eliminar |
| Info | blue-600 | #2563EB | Cotizando, en proceso, informativo |

---

## 3. Tipografía

**Fuente principal:** `Inter` (Google Fonts), con fallback a `system-ui, -apple-system, sans-serif`.

**Razón de la elección:** Inter es legible a tamaños pequeños (importante para tablas densas de proyectos), tiene 9 pesos disponibles, y es neutra (no compite con la identidad naranja del logo).

### Escala tipográfica

| Token | Tamaño | Line-height | Peso | Uso |
|-------|--------|-------------|------|-----|
| `text-xs` | 12px | 16px | 400-500 | Labels en tablas, helpers, captions |
| `text-sm` | 14px | 20px | 400-500 | Texto base de tablas y formularios |
| `text-base` | 16px | 24px | 400 | Texto base de párrafos |
| `text-lg` | 18px | 28px | 500 | Subtítulos de cards |
| `text-xl` | 20px | 28px | 500 | Títulos de sección secundaria |
| `text-2xl` | 24px | 32px | 500 | Títulos de página |
| `text-3xl` | 30px | 36px | 500 | KPI numbers en dashboards |
| `text-4xl` | 36px | 40px | 600 | Solo en login y splashes |

### Pesos a usar

- **400 (regular)**: texto de párrafo, valores en tablas.
- **500 (medium)**: títulos, labels, botones, badges.
- **600 (semibold)**: solo para énfasis muy específico (KPIs grandes en dashboards).
- **No usar 700+** — se ve pesado contra el resto del UI.

---

## 4. Espaciado y layout

### Grid base
4px (todos los espaciados son múltiplos de 4).

### Espaciados comunes

| Token | Valor | Uso |
|-------|-------|-----|
| `space-1` | 4px | Gap entre icono y texto inline |
| `space-2` | 8px | Padding interno de badges, gaps en grids densos |
| `space-3` | 12px | Padding interno de inputs y botones pequeños |
| `space-4` | 16px | Padding default de cards, gap entre items en listas |
| `space-5` | 20px | — |
| `space-6` | 24px | Padding default del área de trabajo |
| `space-8` | 32px | Separación entre secciones de página |

### Layout de página

```
┌─────────────────────────────────────────────────┐
│ Topbar (h-16, fondo blanco)                     │
├──────────┬──────────────────────────────────────┤
│ Sidebar  │ Main (padding 24px desktop / 16 mob) │
│ w-64     │                                      │
│ ↕        │  Fondo: slate-50                     │
│ w-16     │                                      │
│ slate-900│                                      │
└──────────┴──────────────────────────────────────┘
```

### Breakpoints (Tailwind estándar)

| Breakpoint | Ancho | Comportamiento |
|------------|-------|----------------|
| (default) | <640px | Sidebar oculto, hamburguesa expone overlay |
| `sm` | ≥640px | Sidebar oculto, hamburguesa expone overlay |
| `md` | ≥768px | Sidebar colapsado (w-16) |
| `lg` | ≥1024px | Sidebar expandido (w-64) |
| `xl` | ≥1280px | Layout default |

---

## 5. Bordes y radios

### Border radius

| Token | Valor | Uso |
|-------|-------|-----|
| `rounded-none` | 0 | Solo cuando hay border-left/top accent |
| `rounded` | 4px | Inputs pequeños |
| `rounded-md` | 6px | **Default** — botones, inputs, badges |
| `rounded-lg` | 8px | Cards, modales |
| `rounded-xl` | 12px | Cards de KPIs destacados |
| `rounded-full` | 9999px | Avatars, pills |

### Border weight

- **0.5px solid `slate-200`** — bordes default de cards y separators.
- **1px solid `slate-300`** — bordes de inputs en estado hover.
- **2px solid `gpt-600`** — bordes de items destacados (la única excepción al 0.5/1px).
- **3px solid `gpt-600`** — `border-left` accent en cards activos del sidebar y items urgentes.

---

## 6. Sombras

Filosofía: **mínimas y funcionales**. No usar drop-shadows decorativos.

| Token | Valor | Uso |
|-------|-------|-----|
| `shadow-none` | — | Default de cards |
| `shadow-sm` | `0 1px 2px rgba(0,0,0,0.05)` | Solo en dropdowns y tooltips |
| `shadow-focus` | `0 0 0 3px rgba(234,88,12,0.2)` | Anillo de focus en inputs |

---

## 7. Estados de UI

### Estados de proyecto (badges)

| Estado | Color background | Color text | Uso |
|--------|------------------|------------|-----|
| `borrador` | slate-100 | slate-700 | Sin enviar |
| `cotizando` | blue-100 | blue-700 | Equipo armando cotización |
| `cotizado` | gpt-100 | gpt-800 | Cotización lista |
| `presentado` | gpt-100 | gpt-800 | Enviado al cliente |
| `adjudicado_pendiente` | amber-100 | amber-800 | OC pendiente firma |
| `adjudicado_firmado` | green-100 | green-800 | OC firmada |
| `en_ejecucion` | green-100 | green-800 | Proyecto en obra |
| `en_cierre` | gpt-100 | gpt-800 | Cierre administrativo |
| `cerrado` | slate-100 | slate-700 | Finalizado |
| `cancelado` | slate-100 | slate-500 | Cancelado |
| `perdido` | gpt-red-100 | gpt-red-800 | No adjudicado |
| `archivado` | slate-100 | slate-500 | Archivado |

### Estados de prioridad

| Prioridad | Background | Text | Border-left |
|-----------|-----------|------|-------------|
| Urgente | gpt-red-50 | gpt-red-800 | 3px gpt-red-600 |
| Alta | gpt-50 | gpt-800 | 3px gpt-600 |
| Media | blue-50 | blue-800 | 3px blue-600 |
| Baja | slate-50 | slate-700 | 3px slate-300 |

---

## 8. Iconografía

**Set:** Heroicons (https://heroicons.com), versión `outline` para iconografía general, versión `solid` para indicadores activos.

**Tamaños:**
- Inline en texto: 14-16px (`w-4 h-4`).
- Botones: 16-18px (`w-4 h-4` o `w-5 h-5`).
- Sidebar: 20px (`w-5 h-5`).
- KPI cards destacados: 24px (`w-6 h-6`).

**Iconos clave por dominio:**
- Comercial: `briefcase`, `document-text`, `currency-dollar`.
- Proyectos: `clipboard-document-list`, `wrench-screwdriver`, `hard-hat` (si existe).
- Finanzas: `banknotes`, `chart-bar`, `calculator`.
- Operaciones: `truck`, `cog-6-tooth`, `users`.
- Ejecutivo: `chart-pie`, `presentation-chart-line`, `eye`.

---

## 9. Componentes core

### Botones

```
Primary:    bg-gpt-600 text-white         → hover:bg-gpt-700
Secondary:  border-gpt-600 text-gpt-600   → hover:bg-gpt-50
Critical:   bg-gpt-red-600 text-white     → hover:bg-gpt-red-700
Ghost:      text-slate-600                → hover:bg-slate-100
```

Padding: `px-4 py-2` (default), `px-3 py-1.5` (small), `px-6 py-3` (large).
Radius: `rounded-md`.
Font: `text-sm font-medium`.

### Inputs

```
Default:    border-slate-200 bg-white     → focus:border-gpt-600 focus:ring-gpt-200
Error:      border-gpt-red-600            → focus:ring-gpt-red-200
Disabled:   bg-slate-50 text-slate-400 cursor-not-allowed
```

Altura: 36px default. Padding: `px-3 py-2`. Radius: `rounded-md`. Font: `text-sm`.

### Cards

**Default:** `bg-white border-0.5 border-slate-200 rounded-lg p-4`.
**KPI destacado:** `bg-gradient-to-br from-gpt-50 to-gpt-100 border-gpt-200 rounded-lg p-4`.
**Acento de prioridad:** agrega `border-l-4 border-gpt-600` y `rounded-l-none`.

### Tablas

- Header: `bg-slate-50 text-slate-600 text-xs uppercase tracking-wider font-medium`.
- Body row: `border-b border-slate-200 hover:bg-slate-50`.
- Padding cell: `px-4 py-3`.
- Font body: `text-sm`.

### Badges

`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium`.
Colores según tabla "Estados de proyecto" arriba.

### Sidebar

- Fondo: `slate-900`.
- Item normal: `text-slate-300 px-3 py-2`.
- Item activo: `text-white bg-slate-800 border-l-3 border-gpt-600 rounded-r-md`.
- Item hover: `text-white bg-slate-800/50`.
- Sección label: `text-slate-500 text-xs uppercase tracking-wider px-3 py-1`.

### Topbar

- Fondo: `bg-white border-b border-slate-200 h-16`.
- Search: input con icono `magnifying-glass`, `bg-slate-50 border-slate-200 placeholder:text-slate-400`.
- Acciones: botones ghost + dot indicator (`w-2 h-2 bg-gpt-600 rounded-full`) cuando hay no leídos.
- Avatar dropdown: círculo `w-8 h-8 bg-gpt-600 text-white text-xs font-medium`.

---

## 10. Patrones específicos del sistema

### Heatmap del Reporte de Asignación

Código de colores **fijo** según procedimiento PRO-GPT-PYT-01:

| Carga (proyectos activos) | Background | Text |
|---------------------------|-----------|------|
| 0-4 | `green-100` (#DCFCE7) | `green-900` (#14532D) |
| 5-7 | `amber-100` (#FEF3C7) | `amber-900` (#78350F) |
| 8-9 | `gpt-200` (#FED7AA) | `gpt-900` (#7C2D12) |
| 10+ | `gpt-red-200` (#FECACA) | `gpt-red-900` (#7F1D1D) |

Cada celda: `padding: 8px; border-radius: 4px; text-align: center; font-weight: 500`.

### Stepper de Minuta de Entrega (5 pasos)

- Pasos completados: círculo `gpt-600` con check, línea conector `gpt-600`.
- Paso actual: círculo `gpt-600` con número, anillo de focus.
- Pasos pendientes: círculo `slate-200` con número en `slate-500`, línea `slate-200`.

### Acordeón del Libro de Proyecto (10 secciones A-J)

Cada sección colapsable con:
- Header: código sección (A-J) + nombre + barra de progreso `gpt-600` + % avance + chevron.
- Body al expandir: drag&drop area + lista de archivos + checklist editable.
- Estado completo: borde superior `border-t-2 border-green-600` + check verde junto al nombre.

### Indicador de concentración SEDENA

Cuando concentración por cliente >50%, mostrar siempre alerta visible:
- Badge `bg-gpt-red-100 text-gpt-red-800 inline-flex items-center gap-1`.
- Icono `exclamation-triangle` + texto "Concentración SEDENA 82.7%".

---

## 11. Reglas de oro

1. **Fondo blanco siempre en main**, slate-50 solo como pausa visual entre cards.
2. **Naranja gpt-600 es el único acento de marca** — no introducir azules saturados, no introducir otros naranjas.
3. **Rojo gpt-red-600 solo para errores, alertas críticas y acciones destructivas** — nunca decorativo.
4. **Slate-900 es el sidebar y nada más** — no fondos oscuros en main area.
5. **Inter regular y medium únicamente** — bold solo en KPI numbers grandes.
6. **0.5px borders default** — nunca 1px o 2px decorativo, solo cuando hay énfasis funcional.
7. **No drop-shadows decorativos** — solo focus rings y dropdown lift.
8. **No gradients excepto en KPI cards destacados** del dashboard ejecutivo.
9. **Sentence case** — nunca Title Case ni ALL CAPS, excepto labels uppercase de columnas (`text-xs uppercase tracking-wider`).
10. **Iconos outline default**, solid solo para indicadores activos (estado seleccionado).

---

**Fin del Design System v1.0.**
