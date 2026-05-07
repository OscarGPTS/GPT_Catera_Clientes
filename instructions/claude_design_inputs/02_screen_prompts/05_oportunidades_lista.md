# Pantalla 05 — Lista de oportunidades (Status de Ofertas)

**Ruta:** `/oportunidades`
**Módulo:** M2 Comercial
**Hito:** H2
**Layout:** Sidebar + topbar + main

---

## Rol del usuario
`comercial`, `director_dn`, `gerente_proyectos`, `direccion_general`. Replica del archivo `Status_ofertas_GPT_Services_2026.xlsx` que hoy se mantiene a mano en Excel.

## Objetivo
Ver todas las oportunidades del año, filtrar, buscar, exportar a Excel `FO-GPT-VTS-01-B`, crear nueva oportunidad.

## Componentes principales

**Header:**
- Título "Oportunidades 2026" + subtítulo dinámico "18 ofertas activas · $54.7M USD pipeline · 22% hit rate".
- Tabs de año: "2024" | "2025" | "**2026**" (activo) | "+".
- Botón principal `gpt-600`: "+ Nueva oportunidad".
- Botón secundario: "Exportar Excel" + icono download.

**KPIs en fila (4 cards):**
1. Pipeline activo: $54.7M USD · 18 ofertas
2. Adjudicado YTD: $8.3M USD · 4 proyectos
3. Hit rate: 22% (conteo) / 15% (monto) — dos números lado a lado (D7)
4. Concentración SEDENA: 82.7% — card en `gpt-red-50` con icono warning si >50%

**Filtros (en una fila colapsable):**
- Search "Buscar por Tech Reference, cliente, CP..."
- Multi-select Sublínea: HTP / LSP / VLV / SOL / SG
- Multi-select Cliente (con buscador interno)
- Multi-select Estado (12 estados)
- Toggle "Solo mis oportunidades" (filtra por usuario asignado)
- Botón "Limpiar"

**Tabla principal:**
| Columna | Descripción |
|---------|-------------|
| CP | `CP-003/25` en monospace |
| Tech Reference | Truncado 40 chars con tooltip al hover |
| Cliente | Alias 3 letras (badge slate) + razón social pequeña abajo |
| Sublínea | Badge color según sublínea (HTP=naranja, LSP=azul, etc.) |
| Sector | Texto plano |
| Monto USD | Right-aligned, formato $XX,XXX.XX |
| Estado | Badge según estado (12 colores diferenciados) |
| Líder | Avatar + nombre del `gerente_proyectos` o `ingeniero_proyectos` |
| Última actividad | Relativo: "hace 2h" |
| Acciones | Menu kebab: Ver, Editar, Cotizar, Adjudicar, Cancelar |

**Footer de tabla:**
- Paginación 25/50/100 filas.
- Resumen: "Mostrando 1-25 de 18 oportunidades · Total filtrado: $12.3M USD".

## Datos de ejemplo (5 filas reales del archivo Status 2026)

| CP | Tech Ref | Cliente | Sublínea | Sector | Monto | Estado | Líder | Actividad |
|----|----------|---------|----------|--------|-------|--------|-------|-----------|
| CP-003/25 | 250121-0-IGA-HTP x _HT 30"x10" Texmelucan | IGA · IGASAMEX | HTP | Transporte y distribución | $96,998.30 | Cotizado | FB | hace 2h |
| CP-008/25 | 250218-0-SDN-SOL x _Servicios soldadura | SDN · SEDENA | SOL | Defensa | $2,150,000 | Adjudicado pendiente | EM | hace 1d |
| CP-012/25 | 250305-0-PTX-LSP x _LS 24" Atasta | PTX · PROTEXA | LSP | E&P | $187,500 | Cotizando | GG | hace 5h |
| CP-015/25 | 250322-0-FER-VLV x _Suministro válvulas | FER · ESENTIA | VLV | Refinación | $43,200 | Borrador | RT | hace 3d |
| CP-001/26 | 260105-0-ENG-HTP x _HT 16" gas | ENG · ENGIE | HTP | Generación | $58,900 | Presentado | FB | hace 1h |

## Estados
- **Vacío:** ilustración + "No hay oportunidades este año aún" + CTA "Crear la primera".
- **Loading:** skeleton de tabla.
- **Error:** banner con retry.
- **Filtrado vacío:** "Ningún resultado con los filtros aplicados".

## Responsive
- **Mobile:** tabla → cards. Cada card muestra CP+TechRef+Cliente+Estado+Monto. Tap para ir a detalle.
- **Tablet:** oculta columnas Sector y Última actividad.
- **Desktop:** tabla completa.

## Edge cases
- Mostrar alerta superior persistente si concentración SEDENA >50%: "Riesgo de concentración: SEDENA 82.7% del pipeline".
- Si el usuario es `comercial`, solo ve sus propias oportunidades (toggle "Solo mis oportunidades" pre-marcado).
- Si una oportunidad lleva >30 días sin actividad, mostrar dot rojo en columna "Última actividad".
- Estados visuales para `perdido`: fila con opacity 60% y línea diagonal.

## Referencias visuales
- Tablas densas: estilo Linear issues, Stripe transactions.
- KPI cards: estilo Mercury dashboard.

## Notas técnicas
- Server-side pagination y filtering (Livewire).
- Export Excel genera archivo idéntico al template `FO-GPT-VTS-01-B`.
- Click en fila → drawer con resumen + link a `/proyectos/{cp}/detalle`.
