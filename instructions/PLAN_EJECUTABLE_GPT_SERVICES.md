# Plan Ejecutable — Plataforma GPT Services
**Tech Energy Control S.A. de C.V. | Sistema integral de licitaciones, finanzas, operación y vista ejecutiva**

> Documento maestro consolidado v2.1. Reemplaza las versiones anteriores (POC + Addendum Directivo + Plan v2.0).
> Versión: 2.1 | Fecha: Mayo 2026 | Owner técnico: Equipo de desarrollo | Owner de negocio: Dirección General
> Cambios v2.1 vs v2.0: integración del procedimiento PRO-GPT-PYT-01 (decisiones D10-D13 + 5 artefactos secundarios).

---

## 1. Resumen ejecutivo

GPT Services opera hoy con un proceso descentralizado: oportunidades en Excel (`Status_ofertas_GPT_Services_2026.xlsx`), cotizaciones en Word, viáticos en PDF firmados, documentación de proyectos dispersa en carpetas físicas y digitales, y comunicación operativa en WhatsApp. Esto genera tres problemas estructurales:

1. **Pérdida de trazabilidad** entre oportunidad → CP (Costo y Presupuesto) → DN (Desarrollo de Negocios) → ejecución → cierre, con artefactos formales (minutas, ofertas, fichas, libros de proyecto, finiquitos) que viven en correos y carpetas sueltas.
2. **Falta de visibilidad ejecutiva** en tiempo real para dirección y socios sobre pipeline, cartera, márgenes, concentración por cliente (la concentración SEDENA es 82.7% del pipeline 2026, riesgo no monitoreado), y carga de trabajo del equipo de Proyectos.
3. **Cierre financiero dual no soportado**: la dirección necesita el "cierre bueno" (gerencial: SAT + devengado + pipeline ponderado) además del cierre fiscal, y hoy se reconstruye manualmente cada mes.

La solución es una plataforma web Laravel que consolida los 6 procesos críticos (Comercial, Proyectos, Operaciones, Finanzas, Comunicación, Vista Ejecutiva) en un único sistema con tres niveles de acceso (Operativo, Financiero, Ejecutivo), alineado al procedimiento ISO `PRO-GPT-PYT-01` rev 02 vigente.

Resultados medibles esperados:

- Cotización Texmelucan (caso de validación) generable end-to-end en menos de 30 minutos vs flujo actual de varios días.
- Cierre gerencial de un mes producido en menos de 1 hora con un clic, vs 1-2 días de armado manual.
- Vista ejecutiva con KPIs en tiempo real: pipeline, hit rate (conteo y monto), margen promedio, concentración por cliente, carga del equipo, alertas accionables.
- Reemplazo de WhatsApp operativo con chat anclado a proyecto y trazabilidad histórica.
- Libro de Proyecto (Dossier ISO) automatizado con 10 secciones obligatorias y % de avance visible.
- Reporte de Asignación por persona (`FO-GPT-PYT-01-B`) generado automáticamente y exportable a Excel.

---

## 2. Decisiones cerradas (D1–D13)

Estas son las decisiones tomadas en sesiones de discovery y son la base no-negociable del plan:

| # | Tema | Decisión |
|---|------|----------|
| D1 | Cierre gerencial | SAT base + devengado (cartera contratada no facturada) + pipeline ponderado por probabilidad. Tres secciones independientes en el reporte. |
| D2 | Identificación de socios | Cascada combinada: API RH (puesto Socio/DG/Accionista/CEO) → override manual `es_socio_override` → email allowlist en `.env`. |
| D3 | Notificaciones (reemplazo WhatsApp) | Triple canal: email (formales) + panel in-app campanita estilo Linear/GitHub + chat embebido por proyecto/departamento/dirección con menciones, threading, adjuntos, búsqueda. Implementación con Laravel Reverb. |
| D4 | Alcance del entregable | Todo en un solo plan: POC operativo + Finanzas + Vista Ejecutiva (Fases 0–15). |
| D5 | UI/UX | Dashboard moderno serio. Sidebar colapsable (w-64 ↔ w-16) con menú hamburguesa + topbar (h-16) con notificaciones y acciones rápidas. Fondo blanco. Paleta corporativa naranja/rojo del logo GPT. Material Design + Flowbite con Tailwind 3. Responsive sm/md/lg/xl. |
| D6 | Auth multi-proveedor | Tres modalidades simultáneas: (A) Auth0 corporativo `@gptservices.com`/`@satechenergy.com` con búsqueda en API RH → autocrea local con rol default; (B) email/password para externos pre-registrados; (C) Google/Microsoft/Apple personal vía Socialite con allowlist. Tabla `auth_providers` permite múltiples vinculaciones por usuario. |
| D7 | KPI de éxito en adjudicación | Ambos en paralelo: por **conteo** (oportunidades adjudicadas / total presentadas) y por **monto** (monto adjudicado / monto total ofertado). Vista ejecutiva muestra los dos lado a lado. |
| D8 | Plurianualidad | Soporte desde el día 1. Corte por período configurable (mensual, trimestral, anual, personalizado). Distribución de ingresos/costos por días naturales por defecto, opcional por hitos. Campo `proyecto.metodo_distribucion_plurianual`. |
| D9 | Cadencia de entrega | Configurable. Se usa Sprint Plan como guía pero la plataforma se entrega por **hitos accionables** que la dirección puede aprobar/posponer. Cada hito = demo + criterios de aceptación + decisión go/no-go. |
| D10 | Minuta de Entrega CP→DN | Opcional por default, **configurable como obligatoria** desde `system_settings`. Cuando es obligatoria, bloquea el cambio a `en_ejecucion` hasta tener minuta firmada. Modelo `minutas_entrega` 1:1 con proyecto. PDF replica `FO-GPT-PYT-01-A`. |
| D11 | Libro de Proyecto / Dossier ISO | Estructura completa de 10 secciones (A-J) **obligatorias** con checklist de avance por sección. Modelo `libros_proyecto` + `libro_secciones` + `libro_documentos`. KPI `% avance dossier` por proyecto. Bloqueo de cierre formal si <100% (configurable). |
| D12 | Reporte de Asignación por persona | Módulo completo con vista mensual/trimestral. Heatmap mes×persona con código de color, KPIs de equipo, vista expandida individual con datos RH. Snapshot mensual automático. Exportable a Excel `FO-GPT-PYT-01-B`. |
| D13 | Refactor de roles a estructura del procedimiento | **Sustitución total**: se reemplaza `lider_proyectos` + `ingenieria` por 6 roles nuevos del procedimiento (`director_dn`, `gerente_proyectos`, `gerente_operaciones`, `ingeniero_costos`, `ingeniero_proyectos`, `trainee_proyectos`). Permisos **independientes** (sin herencia). Mapeo puesto-RH → rol-sistema configurable por admin desde tabla `rh_role_mapping`. |

---

## 3. Stack técnico definitivo

### Backend
- **Laravel 11** (PHP 8.3) — framework principal.
- **MySQL 8** — base de datos relacional.
- **Redis** — cache, sesiones, queues, broadcast (Reverb).
- **Laravel Reverb** — websockets nativos (no requiere Pusher de pago) para chat tiempo real y panel de notificaciones.
- **Spatie Laravel Permission** — roles y permisos granulares (sin herencia, según D13).
- **Laravel Socialite** — Google, Microsoft (Azure), Apple Sign-In.
- **Auth0 PHP SDK** — autenticación corporativa.
- **Laravel Sanctum** — API tokens para apps móviles futuras.
- **Spatie Laravel Settings** — `system_settings` configurables por admin (incluye `minuta_entrega_obligatoria`, `bloqueo_cierre_dossier_incompleto`).

### Frontend
- **Blade + Tailwind 3** — templating server-side principal.
- **Alpine.js 3** — interactividad ligera.
- **Livewire 3** — componentes server-rendered con reactividad (formularios complejos, tablas filtrables, heatmap del Reporte de Asignación).
- **Flowbite Pro** — biblioteca de componentes Tailwind.
- **ApexCharts** — gráficas para Vista Ejecutiva y heatmap.
- **Heroicons** — iconografía consistente.

### Generación de documentos
- **DomPDF** — PDFs de cotización, cierres, reportes, **minuta de entrega**, **carta finiquito**, **post-mortem**.
- **Browsershot** (Puppeteer) — PDFs con CSS/charts complejos cuando DomPDF no alcanza.
- **PhpSpreadsheet** — Excel de Resumen Ejecutivo, Status de ofertas, **Reporte de Asignación**, **Listado de Suministros**, exportaciones.
- **PhpWord** (opcional) — fallback para cotizaciones tipo `FO-GPT-VTS-01-E` y minutas de entrega.

### Calidad y CI/CD
- **Pest 3** — testing (preferred) + PHPUnit como fallback.
- **Larastan** — análisis estático.
- **Laravel Pint** — formateo de código.
- **GitHub Actions** — pipeline CI: tests, static analysis, build de assets.
- **Laravel Telescope** — debugging en staging.
- **Sentry** — monitoreo de errores en producción.

### Infraestructura propuesta
- **Hosting**: VPS dedicado (DigitalOcean/AWS Lightsail) o servidor on-premise corporativo.
- **CI/CD deploy**: GitHub Actions → SSH + Deployer.
- **Backups**: MySQL diario + archivos S3 (incluye libros de proyecto).
- **SSL**: Let's Encrypt + Cloudflare proxy.
- **Reverb**: mismo servidor o subdominio dedicado (`ws.gpt-platform.com`).
- **Storage de archivos del Libro de Proyecto**: S3 con versionado activo + retención 5 años (alineado a ISO 9001).

---

## 4. Arquitectura de autenticación multi-proveedor

Esta es la pieza arquitectónicamente más delicada del sistema. Separa identidad (quién eres) de autenticación (cómo lo demuestras).

### 4.1 Modelo de datos de auth

```
users
  - id, name, email (UNIQUE), avatar_url, departamento, puesto
  - employee_id (nullable, ID en API RH)
  - es_socio (boolean, computed por SocioResolver)
  - es_socio_override (nullable boolean, manual)
  - status (active | invited | suspended)
  - created_at, last_login_at

auth_providers
  - id, user_id (FK)
  - provider (enum: auth0 | email_password | google | microsoft | apple)
  - provider_user_id (sub de Auth0/Google/etc., nullable para email_password)
  - password_hash (solo para email_password)
  - is_primary (boolean, primer método registrado)
  - linked_at, last_used_at
  - UNIQUE(provider, provider_user_id)

socios_allowlist
  - email (UNIQUE), notes, added_by, added_at

email_allowlist (para sociales personales)
  - email (UNIQUE), allowed_providers (JSON: ["google", "microsoft"])
  - role_default, departamento_default

rh_role_mapping (D13 — mapeo puesto RH → rol sistema, configurable por admin)
  - id, puesto_rh (string, match exacto o LIKE)
  - rol_sistema (FK a roles Spatie)
  - prioridad (int, para resolución de conflictos cuando un puesto matchea varias reglas)
  - departamento_filter (nullable, restringe regla a un departamento)
  - activo (boolean)
```

### 4.2 Flujo de login unificado

Pantalla `/login` muestra tres caminos:

**A) Auth0 corporativo** (botón principal):
1. Usuario hace clic → redirect a Auth0 con connection corporativa.
2. Auth0 valida que el email termine en `@gptservices.com` o `@satechenergy.com`.
3. Callback en Laravel → consulta API RH con el email.
4. Si RH responde con datos → upsert en `users` con datos completos (puesto, departamento, employee_id).
5. **RoleMapper consulta `rh_role_mapping`** y asigna el rol del sistema según el puesto RH.
6. Si es primer login → crea registro en `auth_providers` (provider=auth0).
7. Inicia sesión.

**B) Email + password** (formulario):
1. Usuario ingresa email + password.
2. Sistema busca `auth_providers` con provider=email_password y email match.
3. Si no existe en `users` → rechaza ("Cuenta no autorizada. Contacta al administrador.").
4. Si existe pero el password no coincide → rechaza con throttle.
5. Si OK → inicia sesión.

**C) Proveedores sociales** (botones Google/Microsoft/Apple):
1. Usuario hace clic en "Continuar con Google".
2. Socialite redirect → callback con datos del proveedor.
3. Sistema verifica que email esté en `email_allowlist`.
4. Si no está → rechaza.
5. Si está y es primer login con ese proveedor → crea `auth_providers` y vincula al `user` existente o lo crea con datos de allowlist.
6. Inicia sesión.

### 4.3 Vinculación múltiple

Un usuario puede tener varias filas en `auth_providers`. Ejemplo: Fernando Basave entra normalmente con Auth0 corporativo, pero un día desde su celular personal usa Google. La primera vez que use Google, si su email Gmail está en `email_allowlist` y matchea con su `users.email`, se crea un segundo `auth_provider` vinculado al mismo `user_id`.

### 4.4 Mapeo RH → rol del sistema (D13)

Tabla `rh_role_mapping` configurable desde panel admin (`/admin/rh-mapping`). Reglas iniciales semilla:

| puesto_rh (LIKE) | rol_sistema | prioridad |
|------------------|-------------|-----------|
| `%Director General%` | `direccion_general` | 100 |
| `%Director%Desarrollo de Negocios%` | `director_dn` | 90 |
| `%Gerente de Proyectos%` | `gerente_proyectos` | 80 |
| `%Gerente de Operaciones%` | `gerente_operaciones` | 80 |
| `%Ingeniero de Costos%` | `ingeniero_costos` | 70 |
| `%Ingeniero de Proyectos%` | `ingeniero_proyectos` | 60 |
| `%Trainee%` o `%Becario%` | `trainee_proyectos` | 50 |
| `%CFO%` o `%Director de Finanzas%` | `cfo` | 90 |
| `%Comercial%` o `%Ventas%` | `comercial` | 60 |
| (default fallback) | `ingeniero_proyectos` | 0 |

`RoleMapper` ejecuta en cada login con cache de 30 min. Si el puesto RH cambia (promoción), el rol se actualiza en el siguiente login. Si la regla no matchea, asigna fallback y notifica al admin.

---

## 5. Modelo de datos completo

### 5.1 Identidad y permisos

| Tabla | Propósito |
|-------|-----------|
| `users` | Usuario canónico del sistema. |
| `auth_providers` | Métodos de autenticación vinculados (1:N). |
| `socios_allowlist` | Lista manual de emails con privilegio de socio. |
| `email_allowlist` | Emails autorizados para login con sociales. |
| `rh_role_mapping` | Mapeo configurable puesto-RH → rol-sistema (D13). |
| `roles`, `permissions`, `model_has_roles`, `role_has_permissions` | Spatie Permission. **Permisos independientes sin herencia (D13).** |
| `system_settings` | Configuración global editable por admin (D10 minuta obligatoria, D11 bloqueo cierre dossier). |
| `notifications` | Tabla estándar Laravel notifications + custom fields. |
| `personal_access_tokens` | Sanctum para futuras apps móviles. |

### 5.2 Comercial (CP — Costo y Presupuesto)

| Tabla | Propósito |
|-------|-----------|
| `clientes` | Cliente final. Campos: razon_social, alias_3letras, rfc, sector, segmento. |
| `contactos_cliente` | Personas dentro del cliente (1:N). |
| `proyectos` | Tabla central. Campos: tech_reference, cp_numero, dn_numero, año, cliente_id, sublinea (HTP/LSP/VLV/SOL/SG), estado (12 estados), fecha_inicio_planeada, fecha_fin_planeada, metodo_distribucion_plurianual, gerente_proyectos_id, gerente_operaciones_id, ingeniero_costos_id, ingeniero_proyectos_id, trainee_id (D13 — 5 FKs a users según el procedimiento). |
| `proyecto_eventos` | Timeline de auditoría: cada cambio de estado, asignación, comentario clave, **KOM interno**, **KOM cliente**, **minuta firmada**, **carta finiquito emitida**. |
| `cotizaciones` | Versionado de cotizaciones por proyecto. |
| `cotizacion_partidas` | Detalle COSS por cotización (replica `FO-GPT-VTS-01-F`). |
| `secuencias` | Generador atómico de CP/DN con `lockForUpdate`. |

### 5.3 Solicitudes internas durante CP (artefactos del procedimiento)

| Tabla | Propósito |
|-------|-----------|
| `solicitudes_internas` | Maestro de requisiciones a otras áreas durante CP. Campos: tipo (`requisicion_compras` \| `orden_trabajo_ingenieria`), proyecto_id, cp_id, codigo_formato (RE-GPT-COM-01-A o FO-GPT-ING-01-A), estado, solicitante_id, asignado_id, fecha_solicitud, fecha_respuesta_requerida, fecha_respuesta_real. |
| `solicitudes_internas_items` | Detalle de cada solicitud (descripción, cantidad, especificación). |

### 5.4 Adjudicación (transición CP→DN)

| Tabla | Propósito |
|-------|-----------|
| `minutas_entrega` (D10) | 1:1 con proyecto al adjudicar. Campos: proyecto_id, fecha_reunion, hora_inicio, hora_fin, modalidad (presencial/virtual/mixta), orden_del_dia (JSON), acuerdos (JSON con id, descripcion, responsable_id, fecha_compromiso), pdf_path, firmado_at, status. |
| `minuta_entrega_participantes` | N:N usuarios participantes con firma digital. |

### 5.5 Operaciones (DN — Desarrollo de Negocios)

| Tabla | Propósito |
|-------|-----------|
| `kick_off_meetings` | Reuniones KOM. Tipo: `kom_interno` (entre Proyectos y Operaciones) \| `kom_cliente`. Campos: proyecto_id, fecha, participantes (JSON), agenda, minuta_pdf_path, cronograma_attached_id. |
| `cronogramas` | Cronograma del proyecto (importable desde MS Project / Project Libre). |
| `cronograma_actividades` | Actividades con fechas planificadas, vinculadas y reales. |
| `bom_boe_items` | Bill of Materials / Equipment del proyecto (visto en `FO-GPT_3.xlsx` LIB 4.1km). Campos: tipo (BOM/BOE), descripcion, cantidad, status (En almacén / Por afilar / Por fabricar / Por comprar / En tránsito), responsable, fecha_requerida. |
| `listados_suministros` | Replica `FO-GPT-PYT-01-C`. % de avance global computado de los items. |
| `listados_suministros_items` | Items con % avance individual mapeado al procedimiento (0-25% definición/cotización/OC, 26-50% revisión ingeniería/fabricación, 51-75% fabricación/FAT, 76-100% embarque/dossier). |
| `solicitudes_viaticos` | Master de cada solicitud (replica `FO-GPT-SSGG-01-A`). |
| `viaticos_personal` | Personal asignado a la solicitud. |
| `viaticos_partidas` | Detalle de gastos: hospedaje, alimentos, transporte. |
| `bitacora_diaria` | Replica `FO-GPT-PYT-01-D`. Campos: proyecto_id, fecha, relacion_actividades, personal_gpt (JSON), equipos_en_sitio (JSON), vobo_cliente_nombre, vobo_cliente_organizacion, vobo_cliente_fecha, firmado_at. |
| `reportes_semanales` | Auto-generados desde bitácoras de la semana, editables antes de envío. Campos: proyecto_id, semana_inicio, semana_fin, contenido_html, generado_at, enviado_at, recipients (JSON). |

### 5.6 Libro de Proyecto / Dossier ISO (D11)

| Tabla | Propósito |
|-------|-----------|
| `libros_proyecto` | 1:1 con proyecto. Campos: proyecto_id, fecha_apertura, fecha_cierre_estimado, fecha_cierre_real, porcentaje_avance_global (computado), bloqueado_para_cierre (boolean). |
| `libro_secciones` | 10 filas predeterminadas A-J por libro. Campos: libro_id, codigo (A/B/C/D/E/F/G/H/I/J), nombre, descripcion, porcentaje_avance, estado (pendiente/en_proceso/completo), responsable_id, observaciones. |
| `libro_seccion_checklist` | Checklist configurable por sublínea. Campos: seccion_id, item_descripcion, completado (bool), evidencia_documento_id (FK), completado_por, completado_at. |
| `libro_documentos` | N documentos por sección. Campos: seccion_id, nombre, archivo_path, version, mime_type, tamaño, subido_por, subido_at. |

**Las 10 secciones predefinidas** (extraídas del documento `Índice de Contenido de Libro de Proyecto`):
- A. Cronograma de Actividades
- B. Ingeniería de Proyecto
- C. Permisos
- D. Estudios (memorias de cálculo, plan de calidad)
- E. Procedimientos
- F. Certificados (personal, equipos, accesorios, materiales)
- G. Registro de Pruebas (NDT, hidrostática, hermeticidad)
- H. Seguridad (IMSS, DC-3, AST, plan emergencias)
- I. Ejecución (bitácoras, reportes)
- J. Misceláneos (BOM/BOE, oficios, organigramas)

**Checklist configurable por sublínea** ejemplo HTP (Hot Tapping):
- Sección F requiere: certificados de soldadores, certificados de operadores HT, certificados de equipos T-101/TM-760/TM-1200, certificados de materiales (válvulas, bridas, accesorios).
- Sección G requiere: pruebas no destructivas (RT/UT/MT/PT según procedimiento), prueba de hermeticidad de Hot Tap, lectura y avance de Hot Tap.

### 5.7 Cierre de proyecto

| Tabla | Propósito |
|-------|-----------|
| `cartas_finiquito` | Replica `RE-GPT-QHSE-106-D`. Campos: proyecto_id, fecha_emision, personal_liberado (JSON), equipos_liberados (JSON), pdf_path, firmado_cliente_at, firmado_gpt_at. |
| `post_mortem` | Cierre interno técnico-operativo. Campos: proyecto_id, fecha_sesion, participantes (JSON), lecciones_aprendidas (JSON), desviaciones_costo, desviaciones_tiempo, desviaciones_calidad, presupuesto_planeado, presupuesto_real, recomendaciones_mejora (JSON), pdf_path. |

### 5.8 Reporte de Asignación por persona (D12)

| Tabla | Propósito |
|-------|-----------|
| `asignaciones_personas` | Snapshot mensual por usuario. Campos: user_id, mes, año, cp_asignados, cp_ejecutados, cp_remanentes, cp_residual_anterior, dn_activos, dn_stand_by, dn_cerrados, dn_cancelados, total_servicio, total_suministro, gerencia_regional (GRC/GRS/GRN/DG/GPT-IM), generado_at. |
| `asignaciones_kpi_snapshot` | Snapshots diarios para gráficas históricas. |

Job programado: `asignaciones:snapshot --mes=current` corre el día 1 de cada mes a las 00:30. También disponible bajo demanda desde el panel.

### 5.9 Finanzas

| Tabla | Propósito |
|-------|-----------|
| `cuentas_bancarias` | Cuentas de la empresa. |
| `estados_cuenta` | Carga mensual por banco. |
| `movimientos_bancarios` | Detalle parseado del estado. |
| `cierres_mensuales` | Cierre por mes y tipo. Tipo enum: `contable_sat` \| `gerencial_avance`. |
| `cierres_secciones` | Bloques del cierre gerencial: sat_base, devengado, pipeline_ponderado. |
| `cierres_lineas` | Detalle de cada sección. |
| `resultados_financieros` | Snapshots históricos por proyecto y cierre. |
| `finanzas_audit` | Auditoría inmutable. |

### 5.10 Comunicación

| Tabla | Propósito |
|-------|-----------|
| `chat_canales` | Tipo: proyecto \| departamento \| direccion \| privado. |
| `chat_canal_miembros` | Pivot N:N. |
| `chat_mensajes` | Mensajes con threading, adjuntos, edits. |
| `chat_menciones` | Menciones @usuario. |
| `chat_lecturas` | Tracking de mensajes leídos. |

### 5.11 Vista Ejecutiva

| Tabla | Propósito |
|-------|-----------|
| `kpi_snapshots` | Snapshots diarios de KPIs precalculados. |

### 5.12 Algoritmos clave

- **Tech Reference regex**: `^\d{6}-\d{1,2}-[A-Z]{3}-[A-Z&]{3,4}\s+(\d+x\d+|x)\s+_.{1,80}$`. Validación obligatoria al crear el Tech Reference en fase Ejecución del CP (no antes, según procedimiento 5.3.4).
- **Asignación atómica CP/DN**: tabla `secuencias` con `lockForUpdate` para evitar duplicados en concurrencia. Formato `CP-XXX/AA` y `DN-XXX/AA` con XXX consecutivo y AA = últimos 2 dígitos del año.
- **Cálculos COSS**: replican Excel original `FO-GPT-VTS-01-F`. Validación con fixture Texmelucan ($61,717.61 → $96,998.30, margen 41.59%).
- **% avance dossier**: `AVG(libro_secciones.porcentaje_avance)` por proyecto.
- **% avance suministros**: `AVG(listados_suministros_items.porcentaje_avance)` por listado.
- **Heatmap carga por persona**: query agregada sobre `asignaciones_personas` por mes con código de color (verde 0-4, amarillo 5-7, naranja 8-9, rojo 10+).
- **RoleMapper**: clase de servicio que ejecuta el matching `puesto_rh → rol_sistema` consultando `rh_role_mapping` ordenado por prioridad DESC.
- **SocioResolver**: clase de servicio que resuelve la cascada D2 (RH → override → allowlist) con cache de 30 min.
- **Distribución plurianual**: helper `ProrrateadorPlurianual` con dos estrategias: `PorDiasNaturales` y `PorHitos`.

---

## 6. Sistema de roles (Spatie) — refactor D13

Se elimina la antigua estructura. El sistema queda con **22 roles totales**, sin herencia entre ellos (cada uno con su propio set de permisos):

### 6.1 Dirección y socios
| Rol | Acceso clave |
|-----|--------------|
| `super_admin` | Todo. Solo personal técnico. |
| `direccion_general` | Todo el sistema, vista ejecutiva completa, aprobaciones de recursos. |
| `socio` | Vista ejecutiva, finanzas (solo lectura), reportes. |
| `comite_socios` | Vista ejecutiva, aprobaciones de inversiones grandes. |

### 6.2 Comercial
| Rol | Acceso clave |
|-----|--------------|
| `director_dn` | Aprueba CP en Comité Comercial, notifica adjudicaciones, **owner de minuta de entrega**, comunica con cliente, aporta info para DN. |
| `comercial` | Crea oportunidades, captura datos del cliente, sigue ofertas. |

### 6.3 Proyectos (D13 — los nuevos roles del procedimiento)
| Rol | Acceso clave |
|-----|--------------|
| `gerente_proyectos` | Asigna CP/DN a su equipo, autoriza CP, controla y reporta estatus, dueño del procedimiento, owner de Reporte de Asignación. **Único rol que ve todo el módulo de Proyectos.** |
| `ingeniero_costos` | Supervisa fichas de proyecto y ofertas comerciales, atiende CP específicos, revisa cálculos COSS antes de salir a Comité Comercial. |
| `ingeniero_proyectos` | Ejecuta CP/DN asignados, conduce KOM con cliente, lleva bitácora diaria, alimenta Libro de Proyecto, gestiona movilizaciones. |
| `trainee_proyectos` | Soporte a CP/DN sin autorización para emitir documentos formales. Acompaña a `ingeniero_proyectos`. |

### 6.4 Operaciones
| Rol | Acceso clave |
|-----|--------------|
| `gerente_operaciones` | Verifica alcances de CP, participa en definición de costos, asigna recursos operativos (personal, equipos), toma decisiones sobre desviaciones junto con Gerente Proyectos. |
| `serv_tecnicos` | Proyectos HTP/LSP/VLV asignados. |
| `soldadura` | Proyectos SOL asignados. |
| `serv_generales` | Viáticos, logística (Ana Lilia López). |
| `qhse` | Permisos, ATS, incidentes, dossier sección H. |
| `almacen` | Inventario, movimientos, BOM/BOE status. |
| `manufactura` | OPM internas, fabricación de housings, brocas, etc. |

### 6.5 Compras e Ingeniería
| Rol | Acceso clave |
|-----|--------------|
| `compras` | BOM/BOE, OC, proveedores, atiende `solicitudes_internas` tipo `requisicion_compras`. |
| `ingenieria_diseño` | Atiende `solicitudes_internas` tipo `orden_trabajo_ingenieria`, alimenta sección B del Libro de Proyecto. |

### 6.6 Finanzas
| Rol | Acceso clave |
|-----|--------------|
| `cfo` | Finanzas completas, cierres SAT y gerencial, cuentas bancarias. |
| `analista_financiero` | Finanzas operativa, no aprobaciones. |
| `finanzas_general` | Lectura de finanzas operativa. |

### 6.7 Externos
| Rol | Acceso clave |
|-----|--------------|
| `cliente_externo` | Solo proyectos donde son cliente, vista limitada. |
| `auditor_externo` | Lectura amplia con marca de agua, sin edición. |

### 6.8 Restricciones críticas

- Rutas `/finanzas/*` solo accesibles por `cfo`, `direccion_general`, `socio`, `comite_socios`, `analista_financiero`. Middleware `EnsureFinanzasAccess`.
- Rutas `/proyectos/asignaciones` solo accesibles por `gerente_proyectos`, `gerente_operaciones`, `direccion_general`. Cada usuario individual ve su propia tarjeta en `/perfil/mi-asignacion`.
- Aprobación de CP requiere `gerente_proyectos` + autorización del Comité Comercial (modelado como evento en `proyecto_eventos`).
- `trainee_proyectos` no puede emitir documentos formales (cotización, ficha, oferta) — solo capturar drafts que requieren aprobación de `ingeniero_costos` o superior.

---

## 7. Sistema de diseño (UI/UX)

### 7.1 Tokens Tailwind (`tailwind.config.js`)

```js
theme: {
  extend: {
    colors: {
      'gpt': {
        50: '#FFF7ED', 100: '#FFEDD5', 200: '#FED7AA', 300: '#FDBA74',
        400: '#FB923C', 500: '#F97316', 600: '#EA580C', 700: '#C2410C',
        800: '#9A3412', 900: '#7C2D12', 950: '#431407'
      },
      'gpt-red': {
        50: '#FEF2F2', 100: '#FEE2E2', 200: '#FECACA', 300: '#FCA5A5',
        400: '#F87171', 500: '#EF4444', 600: '#DC2626', 700: '#B91C1C',
        800: '#991B1B', 900: '#7F1D1D', 950: '#450A0A'
      }
    },
    fontFamily: {
      sans: ['Inter', 'system-ui', 'sans-serif'],
    }
  }
}
```

Color primario: `gpt-600` (#EA580C). Secundario: `gpt-red-600`. Neutros: `slate-*`. Semánticos: `green-600`, `amber-600`, `blue-600`.

### 7.2 Componentes base

`<x-layouts.app>`, `<x-sidebar>`, `<x-topbar>`, `<x-card>`, `<x-stat-card>`, `<x-badge>`, `<x-button>`, `<x-data-table>`, `<x-modal>`, `<x-form.input>`, `<x-form.select>`, `<x-form.textarea>`, `<x-empty-state>`.

**Componentes específicos del refactor v2.1:**
- `<x-heatmap-personas>` — heatmap mes×persona del Reporte de Asignación.
- `<x-libro-proyecto-acordeon>` — acordeón con las 10 secciones, % de avance, drag&drop.
- `<x-checklist-seccion>` — checklist editable por sección del libro.
- `<x-minuta-stepper>` — wizard de 5 pasos para crear minuta de entrega.
- `<x-cronograma-gantt>` — visualización gantt simple basada en ApexCharts.

### 7.3 Layout general

Sidebar (w-64 expandido / w-16 colapsado): fondo `slate-900`, acento `gpt-600` activo, secciones agrupadas (Principal, Proyectos, Operaciones, Finanzas, Ejecutivo, Admin), footer con avatar.

Topbar (h-16): fondo blanco, hamburguesa, search, notificaciones (campanita), chat (burbuja), avatar dropdown, CTA "Nueva oportunidad".

Main: fondo `slate-50`, padding 24px desktop / 16px mobile.

### 7.4 Responsive

Breakpoints estándar Tailwind. Sidebar colapsa automáticamente bajo `lg`. Heatmap se vuelve scrollable horizontal en mobile.

---

## 8. Plan de hitos y sprints

Cadencia configurable según D9. Se proponen sprints de 1 semana como guía base, pero la entrega es por hitos. Cada hito tiene demo + criterios de aceptación.

### Hito 0 — Fundamentos (Sprint 1, 1 semana)
**Objetivo**: Repo, infra, login funcional con los 3 métodos.

- Inicializar Laravel 11 con Tailwind 3, Alpine, Livewire, Flowbite Pro.
- Configurar MySQL + Redis + Reverb.
- Implementar `auth_providers` y los 3 flujos de login.
- Layout base con sidebar y topbar funcionales.
- CI/CD GitHub Actions.
- Seeder inicial de un super_admin.
- `system_settings` con keys iniciales.

**Criterio de aceptación**: 3 usuarios distintos pueden entrar con los 3 métodos diferentes y ven el layout vacío.

### Hito 1 — Integración RH, roles y catálogo de usuarios (Sprint 2)
**Objetivo**: Onboarding automático con mapeo RH→rol (D13).

- Cliente HTTP a `services.satechenergy.com/api/rh/users` con cache 30min + circuit breaker + MockRhClient.
- Tabla `rh_role_mapping` con seeder de 10 reglas iniciales.
- `RoleMapper` ejecutándose en cada login.
- Pantalla `/admin/usuarios` con CRUD para externos.
- Pantalla `/admin/socios` para gestionar override y allowlist (D2).
- Pantalla `/admin/rh-mapping` para configurar reglas (D13).
- Seeder de los 22 roles Spatie con sus permisos independientes (D13 sin herencia).

**Criterio de aceptación**: Login de un `@gptservices.com` real autoprovisiona el usuario, sincroniza puesto desde RH y le asigna el rol correcto vía RoleMapper.

### Hito 2 — Catálogo comercial y Resumen Ejecutivo (Sprint 3)
**Objetivo**: Crear oportunidades y emitir CP.

- Catálogos `clientes`, `contactos_cliente`, `sublineas`.
- Pantalla "Nueva oportunidad" con asignación atómica de CP via `secuencias`.
- Vista lista de oportunidades estilo Status de Ofertas.
- Aprobación de CP por Comité Comercial (modelada como evento).
- Asignación del CP por `gerente_proyectos` a `ingeniero_costos` o `ingeniero_proyectos` o `trainee_proyectos` (D13).
- Notificación de asignación.
- Exportación a Excel `FO-GPT-VTS-01-B`.

**Criterio de aceptación**: Cargar las 18 oportunidades del Status 2026 y exportar el Excel idéntico al original. Asignar CP al equipo y verificar notificación.

### Hito 3 — Solicitudes internas y Cotización (Sprint 4)
**Objetivo**: Generar la cotización Texmelucan end-to-end con los artefactos del procedimiento.

- Modelo `solicitudes_internas` con tipos `requisicion_compras` y `orden_trabajo_ingenieria`.
- Pantallas para crear, asignar y responder solicitudes.
- Modelo `cotizaciones` + `cotizacion_partidas`.
- UI de captura de las 19 partidas COSS con cálculos en vivo (Livewire).
- Validación de cálculos contra fixture Texmelucan.
- Generación de Tech Reference en fase Ejecución del CP (validación regex).
- Generación de PDF de cotización con DomPDF (template `FO-GPT-VTS-01-E`).
- Versionado de cotizaciones.

**Criterio de aceptación**: Reproducir cotización Texmelucan exacta. Crear y resolver una requisición a Compras y una orden de trabajo a Ingeniería como parte del CP.

### Hito 4 — Status de ofertas y dashboard básico (Sprint 5)
**Objetivo**: Visibilidad operativa del pipeline.

- Pantalla `/oportunidades` con tabla filtrable por sublinea, cliente, status, año.
- Dashboard operativo con KPIs personales por rol.
- Notificaciones in-app cuando se asigne CP/cotización.

**Criterio de aceptación**: Cada rol ve sus oportunidades correspondientes y recibe notificación cuando se le asigne una nueva.

### Hito 5 — Adjudicación, Minuta de Entrega y DN (Sprint 6)
**Objetivo**: Cierre del flujo comercial → ejecución con minuta formal (D10).

- Flujo de adjudicación: subir orden de compra del cliente.
- **Minuta de Entrega CP→DN (D10)** con stepper de 5 pasos: datos básicos → orden del día → acuerdos → participantes → preview/firma. PDF replica `FO-GPT-PYT-01-A`.
- Setting `minuta_entrega_obligatoria` que bloquea cambio a `en_ejecucion` cuando está activo.
- Asignación atómica de DN.
- Generación de Ficha de Proyecto `RE-GPT-CP-003-25`.
- Asignación de `gerente_proyectos`, `ingeniero_proyectos` y opcional `trainee_proyectos` al DN (D13).
- Cambio de estado a `en_ejecucion`.
- **Apertura automática del Libro de Proyecto** con las 10 secciones vacías (D11).

**Criterio de aceptación**: Adjudicar el proyecto Texmelucan, generar minuta firmada, asignar DN, generar ficha, abrir libro de proyecto con 10 secciones, ver el proyecto en bandeja del ingeniero.

### Hito 6 — Libro de Proyecto / Dossier ISO (Sprint 7)
**Objetivo**: Estructura de 10 secciones obligatorias con checklist (D11).

- Modelo `libros_proyecto` + `libro_secciones` + `libro_seccion_checklist` + `libro_documentos`.
- UI acordeón con las 10 secciones, % de avance visible, drag&drop.
- Checklist configurable por sublínea con validación de completitud.
- Cálculo automático de % avance global del dossier.
- Setting `bloqueo_cierre_dossier_incompleto` que valida 100% antes de permitir cierre formal.
- Generación de PDF consolidado del dossier al cierre (un solo entregable para cliente).

**Criterio de aceptación**: Crear libro de proyecto Texmelucan, alimentar las 10 secciones (al menos 1 documento por sección), llegar a 100% de avance, generar PDF consolidado.

### Hito 7 — KOM, Cronograma, BOM/BOE y Listado de Suministros (Sprint 8)
**Objetivo**: Soporte operativo del proyecto en fase de planeación.

- Modelo `kick_off_meetings` con tipos `kom_interno` y `kom_cliente`. Generación de minuta KOM.
- Modelo `cronogramas` con import desde MS Project / Project Libre.
- Visualización gantt simple con ApexCharts.
- BOM/BOE con tracking de status (En almacén / Por afilar / Por fabricar / Por comprar).
- Listado de Suministros (`FO-GPT-PYT-01-C`) con % de avance individual y consolidado.
- Validaciones cruzadas: si un item está en "Por fabricar" debe tener orden de trabajo a Ingeniería asociada.

**Criterio de aceptación**: Importar cronograma, generar minutas KOM interno y con cliente, alimentar BOM/BOE de un proyecto Hot Tap basado en el caso real `LIB 4.1km`. Llegar a 75% de avance en suministros.

### Hito 8 — Ejecución: Bitácora, Reportes Semanales, Viáticos (Sprint 9)
**Objetivo**: Gestión diaria de obra.

- Modelo `bitacora_diaria` (`FO-GPT-PYT-01-D`) con personal, equipos en sitio, V°B° del cliente.
- Modelo `reportes_semanales` auto-generados desde bitácoras de la semana, editables antes de envío.
- Solicitud de viáticos `FO-GPT-SSGG-01-A` con flujo de aprobación (Sergio Ordaz + Dirección).
- Notificación a `gerente_proyectos` cuando se identifique una desviación en bitácora.

**Criterio de aceptación**: Líder solicita viáticos, Sergio aprueba. Capturar 5 bitácoras diarias y generar el reporte semanal automáticamente.

### Hito 9 — Cierre de Proyecto: Carta Finiquito y Post-Mortem (Sprint 10a)
**Objetivo**: Cierre formal del DN.

- Modelo `cartas_finiquito` (`RE-GPT-QHSE-106-D`) con liberación de personal y equipos.
- Modelo `post_mortem` con plantilla estructurada (lecciones, desviaciones, presupuesto planeado vs real).
- Validación: no se puede emitir carta finiquito si el dossier <100% (cuando setting activo).
- KPI ejecutivo: % de proyectos con post-mortem completado.

**Criterio de aceptación**: Cerrar un proyecto completo: emitir carta finiquito, generar post-mortem, cambiar estado a `cerrado`, ver el proyecto en histórico con dossier consolidado descargable.

### Hito 10 — Reporte de Asignación por persona (Sprint 10b - en paralelo)
**Objetivo**: Vista de capacidad del equipo (D12).

- Modelo `asignaciones_personas` con snapshot mensual.
- Job programado `asignaciones:snapshot` día 1 de cada mes 00:30.
- Pantalla `/proyectos/asignaciones` con vista heatmap mes×persona.
- KPIs de equipo: total, activos, carga promedio, sobrecarga.
- Vista expandida individual con datos RH (puesto, ingreso, antigüedad, formación).
- Gráfica trimestral por persona.
- Filtros por gerencia regional, posición, año.
- Exportación a Excel `FO-GPT-PYT-01-B`.
- Vista personal `/perfil/mi-asignacion` para que cada ingeniero vea solo lo suyo.

**Criterio de aceptación**: Heatmap correcto del equipo de 7 personas para Q1 2026 con código de color funcional. Excel exportado idéntico al template original. Cada usuario ve solo su tarjeta en su perfil.

### Hito 11 — Notificaciones y chat (Sprint 11)
**Objetivo**: Reemplazo de WhatsApp operativo (D3).

- Email transaccional con plantillas Blade.
- Panel de notificaciones in-app (campanita) con marcar leído/no leído.
- Chat embebido con Reverb: canales por proyecto + departamento + dirección.
- Menciones `@usuario` con notificación.
- Búsqueda histórica.
- Adjuntos en chat.

**Criterio de aceptación**: 5 usuarios usando el chat en simultáneo, recibiendo notificaciones por email + in-app, con búsqueda funcional.

### Hito 12 — Finanzas: cuentas y estados de cuenta (Sprint 12)
**Objetivo**: Base financiera. Acceso restringido.

- CRUD de cuentas bancarias.
- Carga de estado de cuenta mensual con parser por banco.
- Conciliación manual con movimientos.
- Vista de saldos por cuenta y consolidado.

**Criterio de aceptación**: Cargar 1 estado de cuenta real, conciliar 80% automáticamente.

### Hito 13 — Cierres mensuales SAT vs gerencial (Sprint 13)
**Objetivo**: El "cierre bueno" (D1).

- Generador de cierre `contable_sat`.
- Generador de cierre `gerencial_avance` con 3 secciones (SAT + devengado + pipeline ponderado).
- Reporte PDF con las 3 secciones.
- Comparativa visual SAT vs gerencial con delta.

**Criterio de aceptación**: Generar cierre gerencial de marzo en menos de 1 minuto.

### Hito 14 — Vista ejecutiva y reportes (Sprint 14)
**Objetivo**: Dashboard para socios y dirección.

- Dashboard `/ejecutivo` con KPIs:
  - Pipeline activo (monto + conteo).
  - Adjudicado YTD (monto + conteo).
  - Hit rate por conteo y monto en paralelo (D7).
  - Cartera comprometida del mes.
  - Margen promedio.
  - **% de proyectos con dossier completo**.
  - **% de proyectos con post-mortem**.
  - **Carga promedio del equipo de Proyectos** (D12).
- Gráfica de concentración por cliente con alerta SEDENA >50%.
- Tabla "Ofertas que requieren atención".
- Filtros por período configurable (D8).
- Reporte ejecutivo descargable PDF.

**Criterio de aceptación**: Socio entra y en menos de 30 segundos identifica las 3 ofertas más urgentes, la concentración por cliente, y la salud operativa del equipo.

### Hito 15 — Hardening, testing, docs (Sprint 15)
**Objetivo**: Listo para producción.

- Suite de tests >80% coverage en módulos críticos.
- Tests de integración del flujo Texmelucan completo (CP→Cotización→Adjudicación→Minuta→DN→Libro→Ejecución→Cierre).
- Documentación de usuario por rol.
- Documentación técnica (README, ADRs, API docs).
- Seguridad: revisión OWASP top 10.
- Performance.
- Migración de datos históricos.
- Capacitación a usuarios clave.

**Criterio de aceptación**: Sistema en producción con 20 usuarios reales, 0 incidentes en primera semana.

### Resumen de cadencia

| Hito | Sprint | Semanas (acumuladas) |
|------|--------|----------------------|
| 0 | 1 | 1 |
| 1 | 2 | 2 |
| 2 | 3 | 3 |
| 3 | 4 | 4 |
| 4 | 5 | 5 |
| 5 | 6 | 6 |
| 6 | 7 | 7 |
| 7 | 8 | 8 |
| 8 | 9 | 9 |
| 9, 10 | 10 (paralelos) | 10 |
| 11 | 11 | 11 |
| 12 | 12 | 12 |
| 13 | 13 | 13 |
| 14 | 14 | 14 |
| 15 | 15 | 15 |

Total guía: ~15 semanas a 1 sprint/semana (vs 12 en v2.0). Crecimiento de 3 semanas por la integración del procedimiento PRO-GPT-PYT-01: Hito 6 (Libro de Proyecto), Hito 7 (KOM/Cronograma/BOM/Listado), parte del Hito 5 (Minuta), Hito 9 (Carta Finiquito + Post-Mortem) y Hito 10 (Reporte de Asignación). El refactor de roles (D13) está distribuido en Hito 1.

---

## 9. Criterios de aceptación globales

El proyecto se considera entregado cuando:

1. **Caso Texmelucan reproducido al 100%**: oportunidad creada, CP asignado a `ingeniero_costos`, requisición a Compras y orden de trabajo a Ingeniería emitidas, cotización idéntica al PDF original, adjudicación, **minuta de entrega firmada**, asignación de DN al `gerente_proyectos` + `ingeniero_proyectos`, **apertura del Libro de Proyecto con 10 secciones**, **KOM interno y con cliente**, viáticos solicitados, bitácoras diarias, reportes semanales, **carta finiquito**, **post-mortem**, dossier al 100%, cierre gerencial del mes incluye este proyecto en sección devengado.
2. **18 oportunidades 2026 migradas** del Status Excel.
3. **3 métodos de auth funcionando** + mapeo RH→rol funcional con al menos 5 reglas activas.
4. **Cierre gerencial de un mes real producido en <1 hora**.
5. **Dashboard ejecutivo con KPIs de hit rate** (conteo y monto) + KPIs operativos (% dossier, % post-mortem, carga del equipo).
6. **Concentración SEDENA visible** con alerta automática si >50%.
7. **Chat reemplaza WhatsApp** para al menos 5 conversaciones activas.
8. **Reporte de Asignación funcional** con heatmap del equipo real.
9. **Coverage >80%** en módulos cotización, finanzas, auth, libro_proyecto.
10. **Documentación completa** técnica y de usuario.
11. **Aprobación firmada por dirección general** y al menos 2 socios.

---

## 10. Riesgos y mitigaciones

| Riesgo | Severidad | Mitigación |
|--------|-----------|-----------|
| Concentración SEDENA 82.7% sesga métricas | Alta | KPIs filtrables con/sin SEDENA. Alerta visible. Reporte mensual a socios. |
| Resistencia cultural a abandonar WhatsApp | Alta | Chat in-app simulando UX similar. Migración gradual. Capacitación con casos reales. |
| Cálculos COSS deben coincidir con Excel | Crítica | Suite de tests con fixture Texmelucan. Test bloquea deploy si falla. |
| API RH puede degradarse | Media | Cache 30min + circuit breaker + MockRhClient + sync diario. |
| Confusión SAT vs gerencial | Alta | UI con colores distintos. Encabezado siempre visible. |
| Plurianualidad mal calculada | Media | Suite de tests con escenarios reales. |
| Datos sensibles expuestos a roles incorrectos | Crítica | Middleware + tests de policy + audit log. |
| Auth0 mal configurado | Alta | Modo de fallback email/password. |
| Adopción baja por comercial | Media | Dashboard personal con valor inmediato. |
| **Mapeo RH→rol incorrecto deja usuarios sin acceso correcto** | Alta | Fallback a `ingeniero_proyectos` por default. Notificación a admin. Tabla auditable. Pantalla de revisión semanal por gerente. |
| **Resistencia al Libro de Proyecto (10 secciones obligatorias)** | Alta | Checklist configurable por sublínea (no todo aplica a todos). Plantillas pre-cargadas para acelerar. Capacitación ISO con QHSE. |
| **Minuta de Entrega bloquea operación si está en "obligatoria"** | Media | Default `false` al inicio (opcional). Migración gradual: empezar opcional → activar obligatoriedad después de 30 días de uso. |
| **Refactor de roles rompe permisos de usuarios actuales** | Crítica | Sustitución total con script de mapeo manual. Comunicación previa. Setting de "modo lectura" durante migración. Backup completo antes. |
| **Carga del Reporte de Asignación pesada con muchos snapshots** | Media | Snapshots agregados, no detalle. Índices en `(user_id, año, mes)`. Cache de heatmap por 1 hora. |
| **Post-mortem sin completar bloquea cierre formal** | Media | Setting que permite cierre con post-mortem pendiente. Notificación recurrente al líder hasta completarlo. |

---

## 11. Action items inmediatos

**Infraestructura y accesos:**
- [ ] Provisionar VPS o servidor (Ubuntu 22.04 LTS).
- [ ] Configurar MySQL 8 + Redis.
- [ ] Comprar/configurar dominio (`platform.gptservices.com` sugerido).
- [ ] Configurar SSL.

**Cuentas externas:**
- [ ] Crear tenant Auth0 con connection corporativa Google Workspace.
- [ ] App OAuth Google Cloud Console.
- [ ] App OAuth Microsoft Entra.
- [ ] App Apple Developer.

**Repositorio:**
- [ ] Crear repo `gpt-services-platform` en GitHub privado.
- [ ] Branch protection en `main`.
- [ ] Configurar GitHub Actions secrets.

**Datos del cliente:**
- [ ] Confirmar lista nominal inicial de socios para `socios_allowlist`.
- [ ] Confirmar credenciales y disponibilidad de API RH.
- [ ] **Confirmar listado completo de puestos en RH para alimentar `rh_role_mapping` (D13)**.
- [ ] Confirmar bancos en uso.
- [ ] Definir si Denisse trabaja sola o con equipo.
- [ ] **Confirmar checklist por sublínea para Libro de Proyecto (HTP, LSP, VLV, SOL, SG)** — entrevista con QHSE para cada uno.

**Aprobación formal:**
- [ ] Aprobación del plan v2.1 por Dirección General.
- [ ] Aprobación de presupuesto por comité de socios.
- [ ] Designación de Product Owner (sugerido: Fernando Basave).
- [ ] **Designación de Champion ISO 9001/14001/45001 para validar Libro de Proyecto y procedimiento** (sugerido: Erick Daniel Morales Llerena, QHSE).

---

## 12. Apéndices

### A. Caso de validación Texmelucan (sin cambios desde v2.0)
- Tech Reference: `250121-0-IGA-HTP x _HT 30"x 10" Texmelucan`
- CP: 3/25 | Cliente: IGASAMEX | Usuario final: CENAGAS | Sublínea: HTP
- Costo directo: $61,717.61 USD | Precio venta final: $96,998.30 USD | Net margin: 41.59%

### B. Documentos fuente analizados (versión 2.1 incluye los 6 nuevos del procedimiento)

**Versión 2.0 (originales):**
1. `PROCESO_CONTROL_DE_PROYECTOS_DE_LICITACIÓN.docx` — flujo de proceso completo de licitaciones.
2. `FO-GPT-VTS-01-B_Resumen_Ejecutivo.xlsx` — template y caso Texmelucan.
3. `FO-GPT-VTS-01-F_Ficha_de_proyecto.xlsx` — template y `RE-GPT-CP-003-25_HT_30X10_Texmelucan`.
4. `FO-GPT-VTS-01-E_Cotización.docx` — template de cotización.
5. `250121-0-IGA-HTP_x__HT_30x_10_Texmelucan.pdf` — cotización real.
6. `Status_ofertas_GPT_Services_2026.xlsx` — pipeline 2026.
7. `SOLICITUD_DE_VIATICOS_280226-060326.pdf` — formato `FO-GPT-SSGG-01-A`.

**Versión 2.1 (nuevos del procedimiento PYT):**
8. `PRO-GPT-PYT-01.pdf` — Procedimiento maestro rev 02 (enero 2022) con las 5 fases de CP y DN.
9. `FO-GPT-PYT-01-A.docx` — Minuta de Entrega de Proyecto.
10. `FO-GPT-PYT-01-B.xlsx` — Reporte de Asignación.
11. `FO-GPT-PYT-01-C.xlsx` — Listado de Suministros con etapas de avance.
12. `FO-GPT-PYT-01-D.xlsx` — Bitácora de Actividades Diarias.
13. `Índice de Contenido de Libro de Proyecto.docx` — estructura A-J del dossier ISO.

### C. Personas clave del cliente
- **Fernando Basave Arce** — `gerente_proyectos` (autor del procedimiento, Ingeniero de Proyectos firmante).
- **Erick Daniel Morales Llerena** — `qhse` (revisor del procedimiento — Champion ISO recomendado).
- **Guillermo Gutiérrez Melo** — Gerente de Proyectos (aprobador del procedimiento, posible `direccion_general` o `gerente_proyectos`).
- **Jesús Becerra Yebra** — Soldadura.
- **Ana Lilia López Arreola** — Servicios Generales.
- **Denisse Ramírez** — `cfo`.
- **Sergio Ordaz** — autoriza viáticos junto a Dirección.

### D. Endpoints API RH (sin cambios)
URL: `services.satechenergy.com/api/rh/`
- `GET /users` | `POST /buscar-por-email` | `GET /{userId}`

### E. Mapeo procedimiento PRO-GPT-PYT-01 → módulos del sistema

| Sección procedimiento | Módulo del sistema |
|-----------------------|---------------------|
| 5.1 Costo y Presupuesto — Inicio | `proyectos` (estado `en_revision`) + `proyecto_eventos` (asignación CP) |
| 5.2 Planificación CP | `solicitudes_internas` (Compras + Ingeniería) |
| 5.3 Ejecución CP | `cotizaciones` + `cotizacion_partidas` + Tech Reference |
| 5.4 Monitoreo y Control CP | `asignaciones_personas` + Reporte de Asignación |
| 5.5 Cierre CP | Resguardo en `documentos_proyecto` |
| 5.6 Desarrollo de Negocios — Inicio | `minutas_entrega` (D10) |
| 5.7 Planificación DN | `kick_off_meetings` + `cronogramas` + `libros_proyecto` (D11) |
| 5.8 Ejecución DN | `bitacora_diaria` |
| 5.9 Monitoreo y Control DN | `reportes_semanales` + desviaciones en bitácora |
| 5.10 Cierre DN | `cartas_finiquito` + dossier consolidado + `post_mortem` |

### F. Resumen de los 13 cambios v2.1 vs v2.0

1. Nueva tabla `system_settings` para configuraciones globales.
2. Modelo `solicitudes_internas` con tipos requisición/orden de trabajo.
3. Modelo `minutas_entrega` con setting de obligatoriedad (D10).
4. Modelo `kick_off_meetings` con tipo interno/cliente.
5. Modelo `cronogramas` + `cronograma_actividades`.
6. Modelo `listados_suministros` con etapas de avance.
7. Modelo `libros_proyecto` + `libro_secciones` (10 obligatorias) + `libro_seccion_checklist` + `libro_documentos` (D11).
8. Modelo `reportes_semanales` auto-generados.
9. Modelo `cartas_finiquito` con liberación de personal y equipos.
10. Modelo `post_mortem` con plantilla estructurada.
11. Modelo `asignaciones_personas` con snapshot mensual + heatmap (D12).
12. Tabla `rh_role_mapping` configurable (D13).
13. Refactor total a 22 roles (era ~10 + 9 mantenidos), sin herencia de permisos (D13).

---

**Fin del documento v2.1.**
*Cualquier cambio a este plan requiere actualización versionada y aprobación de Dirección General + Champion ISO.*
