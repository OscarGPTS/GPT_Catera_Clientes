# Pantalla 03 — Admin de socios

**Ruta:** `/admin/socios`
**Módulo:** M1 Auth
**Hito:** H1
**Layout:** Sidebar + topbar + main

---

## Rol del usuario
`super_admin` y `direccion_general`. Configura quién es socio en el sistema según la decisión D2 (cascada API RH → override manual → email allowlist).

## Objetivo
Ver socios actuales identificados por cada método. Activar override manual. Gestionar email allowlist para casos edge.

## Componentes principales

**Header:**
- Título "Socios y permisos ejecutivos" + subtítulo "Personas con acceso a Vista Ejecutiva, finanzas en lectura, y reportes mensuales".
- Badge informativo "8 socios identificados (5 RH + 2 override + 1 allowlist)".

**3 secciones acordeón vertical (todas expandidas por default):**

### Sección 1: Identificados por API RH (cascada nivel 1)
Lista no editable. Tabla simple:
- Avatar + nombre, puesto en RH (ej. "Director General", "Socio", "Accionista"), match rule aplicado, fecha último login.
- Texto explicativo arriba: "Estos usuarios son detectados automáticamente por su puesto en el sistema RH. Si necesitas cambiar el puesto, hazlo en RH."
- Botón "Configurar reglas de matching" → link a `/admin/rh-mapping`.

### Sección 2: Override manual (cascada nivel 2)
Buscador "Buscar usuario..." + tabla editable:
- Avatar + nombre + email + rol del sistema actual.
- Switch toggle "Es socio" — al activarlo, abre modal con campo "Razón del override" (textarea) y "Aprobado por" (select de DG).
- Lista actuales con override activo + auditoría (quién y cuándo).
- Para deshabilitar: clic en switch → modal "Confirmar remoción" con razón.

### Sección 3: Email allowlist (cascada nivel 3)
Tabla editable de emails sueltos:
- Email, notas (texto libre), agregado por (avatar+nombre), fecha.
- Botón "+ Agregar email" abre modal con: Email + Notas + Aprobado por.
- Acción eliminar por fila con confirmación.
- Texto explicativo: "Última red de seguridad para socios externos sin cuenta de RH ni usuario en el sistema."

## Datos de ejemplo
Sección 1 (RH):
- Guillermo Gutiérrez Melo · Director General · matched "%Director General%" · hace 5 horas
- Fernando Basave Arce · Gerente de Proyectos · matched "%Gerente de Proyectos%" · hace 2 horas

Sección 2 (Override):
- Roberto Inversor · rinversor@externo.com · Override "Es socio" · razón "Inversionista minoritario aprobado en junta del 15-mar-2026" · activado por DG.

Sección 3 (Allowlist):
- consultor.estrategico@externo.com · "Asesor estratégico ad honorem" · agregado por GG · 02-feb-2026.

## Estados
- **Vacío sección 1:** "API RH no ha respondido o no hay matches. Verifica el mapeo en /admin/rh-mapping".
- **Loading:** skeleton.
- **Error:** banner rojo arriba "API RH no disponible".

## Responsive
Mobile: cada sección colapsable individual. Tablas se vuelven cards apiladas.

## Edge cases
- Si un usuario aparece en las 3 cascadas, mostrar badge "Triple match" junto a su nombre (no afecta funcionalidad pero indica configuración redundante).
- Si remover override deja al usuario sin acceso a /ejecutivo, warning en modal "Este usuario perderá acceso a Vista Ejecutiva".

## Referencias visuales
- Patrón de cascada visual: Notion settings, Stripe permissions.

## Notas técnicas
- Cada sección tiene su API endpoint independiente.
- Cambios disparan re-cálculo de SocioResolver (cache busting).
- Auditoría completa en `socios_audit` (quién, cuándo, qué cambió).
