# GPT Services Platform — Onboarding

Plataforma interna Laravel 12 que reemplaza el flujo descentralizado actual (Excel + Word + WhatsApp) por un sistema integrado de licitaciones, proyectos, finanzas y vista ejecutiva.

Este documento te ubica en lo que está construido y lo que falta. El plan canónico vive en [`instructions/PLAN_EJECUTABLE_GPT_SERVICES.md`](instructions/PLAN_EJECUTABLE_GPT_SERVICES.md) y [`instructions/PLAN_IMPLEMENTACION_PASO_A_PASO.md`](instructions/PLAN_IMPLEMENTACION_PASO_A_PASO.md).

## Setup local

```bash
composer install
cp .env.example .env
php artisan key:generate
# Crear MySQL "gpt_platform" o usar sqlite ajustando .env
php artisan migrate --seed
npm install
npm run build  # o "npm run dev" para hot reload
php artisan serve
```

Login con la cuenta sembrada: `admin@gptservices.com` / `password` (rol `super_admin`).

## Estado por módulo

Catorce módulos derivados del plan paso a paso. Lo que está en verde es funcional sobre el mock; lo amarillo tiene capa de datos lista pero la UI o la lógica de negocio rica espera trabajo.

| Módulo | Capa de datos | Servicios | UI/Controllers | Tests |
|--------|---------------|-----------|----------------|-------|
| **M0** Fundamentos (.env, deps, Tailwind 3, system_settings, CI) | ✅ | ✅ | ✅ | ✅ |
| **M1** Auth multi-proveedor + 22 roles + RH mock | ✅ | ✅ | ✅ | ✅ |
| **M2** Catálogos comerciales + CP + Tech Reference | ✅ | ✅ (Secuencias, TechReference) | 🟡 stubs | ✅ |
| **M3** Cotización COSS | ✅ | ✅ Calculadora (TODO Texmelucan) | 🟡 | ✅ |
| **M4** Adjudicación + Minuta CP→DN + DN | ✅ | — | 🟡 | — |
| **M5** KOM + Cronograma + BOM/BOE + Suministros | ✅ | — | 🟡 | — |
| **M6** Libro de Proyecto / Dossier ISO | ✅ | ✅ AperturaLibroService | 🟡 | ✅ |
| **M7** Bitácora + Reportes + Viáticos | ✅ | helper desviaciones | 🟡 | — |
| **M8** Carta Finiquito + Post-Mortem | ✅ | — | 🟡 | — |
| **M9** Reporte de Asignación + heatmap | ✅ | ✅ Snapshot service + cron + comando | ✅ heatmap + vista personal | ✅ |
| **M10** Notificaciones + Chat (Reverb) | ✅ | 1 Notification ejemplo | 🟡 | — |
| **M11** Finanzas (cuentas + estados de cuenta) | ✅ | middleware EnsureFinanzasAccess | 🟡 stub | — |
| **M12** Cierres SAT + Gerencial | ✅ | ✅ Generador 3 secciones + Prorrateador | 🟡 | — |
| **M13** Vista Ejecutiva (KPIs principales) | ✅ | ✅ KpisEjecutivoService | ✅ dashboard | — |
| **M14** Hardening | parcial | — | — | Pest verde, Pint limpio, CI YAML |

## Lo que ya funciona end-to-end

1. **Login email/password** con admin sembrado y verificación contra `auth_providers`.
2. **Dashboard** que muestra rol, permisos y último login.
3. **Reporte de Asignación** (`/proyectos/asignaciones`): heatmap mes×persona con código de color (verde/amarillo/naranja/rojo).
4. **Vista personal de asignación** (`/perfil/mi-asignacion`).
5. **Vista Ejecutiva** (`/ejecutivo`): pipeline, hit rate (conteo + monto), concentración por cliente, alerta automática SEDENA >50%.
6. **Admin**: usuarios, socios (override + allowlist), mapeo RH→rol, matriz de roles/permisos.
7. **Comando** `php artisan asignaciones:snapshot --mes=current` (D12).
8. **Job programado** `GenerarSnapshotAsignacionesJob` día 1 de cada mes 00:30.

## Decisiones (D1–D13) cumplidas

- **D1** Cierre gerencial 3 secciones — `GeneradorCierreGerencialService`.
- **D2** Identificación socios cascada RH→override→allowlist — `SocioResolver`.
- **D3** Notificaciones triple canal — base `notifications` + Reverb instalado (1 Notification ejemplo).
- **D6** Auth0 + email/password + Socialite con `auth_providers` 1:N — `AuthOrchestrator`.
- **D7** Hit rate por conteo y monto — `KpisEjecutivoService`.
- **D8** Plurianualidad — `ProrrateadorPlurianual` (estrategia días naturales). Hitos pendiente.
- **D10** Minuta de Entrega — tabla + setting `minuta_entrega_obligatoria`.
- **D11** Libro de Proyecto 10 secciones A-J — `AperturaLibroService` con plantilla por sublínea.
- **D12** Reporte de Asignación + heatmap — completo.
- **D13** 22 roles sin herencia + tabla `rh_role_mapping` — `RolesPermissionsSeeder` + `RoleMapper`.

## Comandos útiles

```bash
./vendor/bin/pest             # 35 tests
./vendor/bin/pint --test      # check estilo
./vendor/bin/phpstan analyse  # nivel 5
npm run build                 # build assets
php artisan asignaciones:snapshot --mes=current
php artisan asignaciones:snapshot --mes=2026-03
php artisan migrate:fresh --seed   # reset DB local
```

## Tablas y modelos

47 tablas relacionales, 30+ modelos Eloquent, organizados por módulo. Estructura de servicios:

```
app/
  Services/
    Auth/         AuthOrchestrator, RoleMapper, SocioResolver
    Rh/           RhClientInterface, RhClientHttp, RhClientMock
    Cotizaciones/ CalculadoraCoss
    Proyectos/    SecuenciasService, TechReferenceService
    Libro/        AperturaLibroService
    Finanzas/     ProrrateadorPlurianual, GeneradorCierreGerencialService
    Asignaciones/ SnapshotAsignacionesService
    Ejecutivo/    KpisEjecutivoService
  ValueObjects/   ResultadoCoss
  DTOs/           RhUser
```

## TODOs marcados (esperan recursos externos o trabajo posterior)

- **Auth0 SDK real** — `config/auth0.php` queda inerte hasta que `AUTH0_DOMAIN` esté configurado. Activar `AUTH0_REGISTER_GUARDS=true` en .env y completar `Auth0Controller::callback`.
- **Socialite credentials** — Google/Microsoft/Apple aún sin client_id/secret en `config/services.php`.
- **API RH real** — `RH_API_USE_MOCK=true` por defecto; cambiar a `false` cuando haya `RH_API_TOKEN`. El cliente HTTP tiene cache 30min + circuit breaker.
- **Calculadora COSS validación al centavo** — fórmulas implementadas según el plan, pero no validadas contra `FO-GPT-VTS-01-F` (Texmelucan). Test marcado `skip()` hasta tener el Excel.
- **UIs de M2-M8 y M10-M11** — backends listos, stubs visibles en sidebar. Patrón: copiar las UIs ya hechas (admin/usuarios, asignaciones, ejecutivo).
- **6 generadores de PDF** — `MinutaEntregaPdfGenerator`, `CotizacionPdfGenerator`, `FichaProyectoExportService`, `DossierConsolidadoGenerator`, `ResumenEjecutivoExport`, `ReporteEjecutivoPdfGenerator`. DomPDF y PhpSpreadsheet ya instalados.
- **7 Notifications restantes** — clonar `CpAsignadoNotification`: `CotizacionListaNotification`, `MinutaPendienteFirmaNotification`, `KomProgramadoNotification`, `ViaticosAprobadosNotification`, `DossierIncompletoAlertaNotification`, `DesviacionReportadaNotification`, `CierreMensualGeneradoNotification`.
- **Chat Livewire (M10)** — modelos listos, falta `ChatPanel` Livewire + canales auto-creados al crear proyecto + mention parser.
- **Parsers bancarios (M11)** — `BbvaParser`, `BanorteParser`, `BanamexParser`, `SantanderParser` para conciliación.
- **Importador MS Project (M5)** — `.mpp/.xml/.csv` para alimentar `cronograma_actividades`.
- **OAuth providers** — registrar apps Google Cloud Console / Microsoft Entra / Apple Developer y agregar credenciales al `.env`.

## Personas clave (de `PLAN_EJECUTABLE_GPT_SERVICES.md` Apéndice C)

- **Fernando Basave Arce** — Gerente de Proyectos, autor del procedimiento PRO-GPT-PYT-01.
- **Erick Daniel Morales Llerena** — QHSE, champion ISO recomendado.
- **Guillermo Gutiérrez Melo** — Director General.
- **Denisse Ramírez** — CFO.
- **Sergio Ordaz** — primera aprobación de viáticos.

## Caso de validación

Todo el sistema se valida reproduciendo el caso **Texmelucan**: Tech Reference `250121-0-IGA-HTP x _HT 30"x 10" Texmelucan`, cliente IGASAMEX, sublínea HTP, costo directo $61,717.61 USD → precio venta $96,998.30 USD, margen 41.59%. Está sembrado el cliente IGA en `clientes`. Falta cargar las 19 partidas reales cuando el Excel `FO-GPT-VTS-01-F` esté disponible.
