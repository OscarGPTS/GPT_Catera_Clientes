# Pantalla 02 — Admin de usuarios

**Ruta:** `/admin/usuarios`
**Módulo:** M1 Auth
**Hito:** H1
**Layout:** Sidebar + topbar + main

---

## Rol del usuario
`super_admin` y `direccion_general`. Gestiona usuarios del sistema: empleados internos (sincronizados desde RH), externos pre-registrados, y configura roles.

## Objetivo
Buscar usuarios, ver su estado, editar roles, invitar externos, suspender accesos.

## Componentes principales

**Header de página:**
- Título "Usuarios" + subtítulo "126 usuarios activos · 8 invitados pendientes · 3 suspendidos".
- Botón principal `gpt-600`: "Invitar usuario externo" + icono `user-plus`.
- Botón secundario: "Sincronizar con RH" + icono `arrow-path`.

**Filtros (en una fila):**
- Search input "Buscar por nombre o email" (icono lupa, full-width hasta 400px).
- Select "Rol" (multi-select): muestra los 22 roles del sistema agrupados (Dirección, Comercial, Proyectos, Operaciones, etc.).
- Select "Status": Activo / Invitado / Suspendido / Todos.
- Select "Departamento": consultado del catálogo.
- Botón "Limpiar filtros" ghost.

**Tabla de usuarios:**
| Columna | Descripción |
|---------|-------------|
| Avatar + Nombre | Avatar circular con iniciales (gpt-600 background) + nombre + email pequeño abajo |
| Rol | Badge con el rol principal. Si tiene varios, "+N más" |
| Departamento | Texto plano slate-700 |
| Puesto | Texto plano slate-700 (sincronizado desde RH) |
| Métodos auth | Iconos pequeños horizontales: Auth0 logo, Google, Microsoft, Apple, llave (email/password). Solo los métodos vinculados |
| Último acceso | Relativo: "hace 2 horas", "ayer", "hace 5 días" |
| Status | Badge: Activo (verde), Invitado (amarillo), Suspendido (rojo) |
| Acciones | Menu kebab con: Ver perfil, Editar, Reset password (si email/password), Suspender, Eliminar |

**Sidebar derecho (drawer) al hacer clic en una fila:**
Aparece desde la derecha 480px. Contiene:
- Header con avatar grande + nombre + email + botón cerrar.
- Tabs: General · Roles · Auth · Auditoría.
- **Tab General:** datos RH (puesto, departamento, antigüedad, employee_id), datos del sistema (estado, último login, fecha creación).
- **Tab Roles:** lista de roles asignados con switch toggle. Botón "Agregar rol".
- **Tab Auth:** lista de `auth_providers` vinculados. Por cada uno: provider, fecha vinculación, último uso. Botón "Vincular nuevo método".
- **Tab Auditoría:** timeline vertical de eventos del usuario (logins, cambios de rol, edits).

## Datos de ejemplo (5 filas mínimo)

| Avatar | Nombre | Email | Rol | Departamento | Puesto | Métodos | Último acceso | Status |
|--------|--------|-------|-----|--------------|--------|---------|---------------|--------|
| FB | Fernando Basave Arce | fbasave@gptservices.com | Gerente Proyectos | Proyectos | Gerente de Proyectos | Auth0, Google | hace 2 horas | Activo |
| EM | Erick Daniel Morales | emorales@gptservices.com | QHSE | QHSE | Coordinador QHSE | Auth0 | hace 30 min | Activo |
| GG | Guillermo Gutiérrez Melo | ggutierrez@gptservices.com | Dirección General | Dirección | Director General | Auth0 | hace 5 horas | Activo |
| DR | Denisse Ramírez | dramirez@gptservices.com | CFO | Finanzas | CFO | Auth0, Microsoft | hace 1 día | Activo |
| JC | Juan Cliente | jc@cliente-externo.com | Cliente Externo | — | Director Operaciones IGASAMEX | Email/Password | hace 3 días | Activo |

## Estados
- **Vacío:** ilustración + "No hay usuarios registrados aún" + CTA "Sincronizar con RH" o "Invitar primer usuario".
- **Loading:** skeleton de filas.
- **Filtrado sin resultados:** "No se encontraron usuarios con los filtros aplicados" + botón "Limpiar filtros".
- **Error de sincronización:** alerta amarilla arriba "API RH no disponible. Mostrando última sincronización: hace 2 horas".

## Responsive
- **Mobile:** tabla se vuelve cards apiladas verticalmente. Cada card muestra avatar+nombre+rol+status + tap para drawer (que ocupa 100% en mobile).
- **Tablet:** oculta columnas Departamento y Puesto.
- **Desktop:** tabla completa.

## Edge cases
- Si un usuario tiene un rol que NO está en `rh_role_mapping` (ej. asignado manualmente), mostrar pequeño icono `info` junto al rol con tooltip "Asignado manualmente".
- Si un usuario es socio (`es_socio=true`), mostrar badge dorado pequeño "Socio" junto al nombre.
- Si un usuario está suspendido, atenuar toda la fila (opacity-50).

## Referencias visuales
- Estilo de tabla: similar al admin de Vercel o Linear.
- Drawer lateral: claude.ai sidebar style.

## Notas técnicas
- Paginación: 25 por página default.
- Busca con debounce de 300ms.
- Drawer se abre con `aria-expanded` y trap-focus.
- Acciones destructivas (Eliminar) requieren modal de confirmación.
