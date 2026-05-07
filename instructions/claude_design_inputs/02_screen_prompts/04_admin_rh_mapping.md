# Pantalla 04 — Mapeo RH → Rol del sistema

**Ruta:** `/admin/rh-mapping`
**Módulo:** M1 Auth
**Hito:** H1
**Layout:** Sidebar + topbar + main

---

## Rol del usuario
`super_admin` y `direccion_general`. Configura las reglas que asignan automáticamente un rol del sistema cuando un usuario se autentica con su puesto sincronizado desde RH (decisión D13).

## Objetivo
Ver, crear, editar y reordenar reglas de mapeo. Validar impacto antes de guardar (cuántos usuarios actuales se verían afectados).

## Componentes principales

**Header:**
- Título "Mapeo de puestos RH → Roles del sistema".
- Subtítulo "Las reglas se evalúan en orden de prioridad. La primera que matchea aplica."
- Botón principal `gpt-600`: "+ Nueva regla".
- Botón secundario: "Probar reglas con un puesto..." (abre modal de simulación).

**Tabla principal (drag&drop para reordenar prioridad):**
| Columna | Descripción |
|---------|-------------|
| Drag handle | Icono `bars-3` para arrastrar y reordenar |
| Prioridad | Número (100, 90, 80...) en badge slate |
| Patrón puesto RH | Texto monospace ej. `%Director General%` |
| Filtro departamento | Texto opcional ej. "Proyectos" o "Todos" |
| Rol asignado | Badge del rol del sistema |
| Usuarios afectados | Contador clickable: "5 usuarios" → abre drawer con lista |
| Activa | Switch toggle |
| Acciones | Edit, Duplicar, Eliminar |

**Modal "Nueva regla" / "Editar regla":**
Form con:
- Patrón puesto RH (input con preview en vivo: "Esto matchearía: ...")
- Filtro departamento (select con opción "Todos los departamentos")
- Rol asignado (select de los 22 roles, agrupados por categoría)
- Prioridad (slider 0-100 con valor numérico)
- Switch "Activa al guardar"
- **Sección preview:** "Esta regla afectaría a 5 usuarios actuales: [lista de avatares]"

**Modal "Probar reglas":**
Input "Ingresa un puesto RH..." + select departamento → muestra qué regla matchearía con su prioridad y rol resultante. Si ninguna matchea, muestra "Aplicaría fallback: ingeniero_proyectos".

## Datos de ejemplo (10 reglas seed iniciales)

| Prioridad | Patrón | Departamento | Rol | Usuarios | Activa |
|-----------|--------|--------------|-----|----------|--------|
| 100 | `%Director General%` | Todos | direccion_general | 1 | ✓ |
| 90 | `%Director%Desarrollo de Negocios%` | Todos | director_dn | 1 | ✓ |
| 90 | `%CFO%` o `%Director de Finanzas%` | Finanzas | cfo | 1 | ✓ |
| 80 | `%Gerente de Proyectos%` | Proyectos | gerente_proyectos | 2 | ✓ |
| 80 | `%Gerente de Operaciones%` | Operaciones | gerente_operaciones | 1 | ✓ |
| 70 | `%Ingeniero de Costos%` | Proyectos | ingeniero_costos | 2 | ✓ |
| 60 | `%Ingeniero de Proyectos%` | Proyectos | ingeniero_proyectos | 4 | ✓ |
| 60 | `%Comercial%` o `%Ventas%` | Comercial | comercial | 3 | ✓ |
| 50 | `%Trainee%` o `%Becario%` | Proyectos | trainee_proyectos | 1 | ✓ |
| 0 | (default fallback) | Todos | ingeniero_proyectos | — | ✓ |

## Estados
- **Vacío:** estado inicial vacío con CTA "Aplicar reglas seed iniciales (recomendado)".
- **Regla con conflicto:** badge warning "Conflicto con regla #N" cuando dos reglas tienen misma prioridad y patrones que se solapan.
- **Regla sin matches:** badge gris "0 usuarios afectados actualmente".

## Responsive
- **Mobile:** tabla se vuelve cards apiladas. Drag&drop con botones up/down en lugar de arrastrar.
- **Desktop:** tabla completa con drag&drop fluido.

## Edge cases
- Si guardas una regla nueva, mostrar toast: "Regla guardada. Se aplicará en el próximo login de los usuarios afectados."
- Si eliminas una regla y hay usuarios actualmente con ese rol asignado por esa regla, modal warning: "5 usuarios perderán este rol en su próximo login. ¿Asignar manualmente otro rol?"
- Botón global "Aplicar reglas ahora a todos" (peligroso) requiere confirmación doble.

## Referencias visuales
- Drag&drop: estilo Linear roadmap o Trello cards.
- Modal de testing: estilo regex tester.

## Notas técnicas
- El patrón se evalúa con `LIKE` SQL (case-insensitive) usando `%` como wildcard.
- Cache de `rh_role_mapping` se invalida en cada save.
- Auditoría en `rh_role_mapping_audit`.
