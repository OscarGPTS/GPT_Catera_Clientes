# Plan de Implementación Paso a Paso — GPT Services Platform
**Guía técnica detallada para agente IA o equipo de desarrollo**

> Documento operativo. Acompaña a `PLAN_EJECUTABLE_GPT_SERVICES.md` (v2.1).
> Este documento traduce los 15 hitos del plan ejecutivo en pasos accionables, con checkpoints, comandos exactos y criterios verificables por módulo.
> Versión: 1.0 | Fecha: Mayo 2026

---

## Cómo usar este documento

Este plan está organizado en **9 módulos funcionales** que cruzan los 15 hitos del plan ejecutivo. Cada módulo es un bloque cohesivo que el agente puede implementar de forma autónoma con criterios de verificación claros.

**Estructura de cada módulo:**
1. **Resumen y dependencias** — qué módulos previos necesitas haber terminado.
2. **Backend** — migraciones, modelos, services, jobs, tests.
3. **Frontend** — vistas Blade, componentes Livewire, Alpine.
4. **Tests** — qué probar y cómo.
5. **Checkpoint de salida** — comandos para verificar que el módulo quedó bien.

**Reglas para el agente:**
- Después de cada módulo, ejecutar `php artisan test` y verificar que **todos** los tests pasen antes de continuar.
- Si un test falla, detenerse y reportar — no avanzar al siguiente módulo.
- Cualquier decisión de diseño no documentada aquí debe consultarse con el humano antes de implementar.
- Commits atómicos: un commit por feature, mensajes en formato conventional (`feat:`, `fix:`, `test:`, `docs:`, `refactor:`).
- Cada migración debe tener su seeder de prueba antes de avanzar.

---

# Módulo 0 — Fundamentos del proyecto

**Hito asociado:** Hito 0
**Dependencias:** Ninguna
**Estimación:** 1 sprint (1 semana)

## 0.1 Setup inicial del repositorio

### Paso 0.1.1 — Crear proyecto Laravel
```bash
composer create-project laravel/laravel:^11.0 gpt-services-platform
cd gpt-services-platform
git init
git remote add origin git@github.com:[org]/gpt-services-platform.git
```

### Paso 0.1.2 — Configurar .env base
```env
APP_NAME="GPT Services"
APP_ENV=local
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gpt_platform
DB_USERNAME=root
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

# Auth0
AUTH0_DOMAIN=
AUTH0_CLIENT_ID=
AUTH0_CLIENT_SECRET=
AUTH0_COOKIE_SECRET=

# Socialite providers
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
MICROSOFT_CLIENT_ID=
MICROSOFT_CLIENT_SECRET=
APPLE_CLIENT_ID=

# RH API
RH_API_URL=https://services.satechenergy.com/api/rh
RH_API_TOKEN=
RH_API_USE_MOCK=true

# Reverb
REVERB_APP_ID=local-app
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret
REVERB_HOST=localhost
REVERB_PORT=8080

# Socios allowlist (D2)
SOCIOS_EMAIL_ALLOWLIST=
```

### Paso 0.1.3 — Instalar dependencias core
```bash
composer require laravel/sanctum
composer require laravel/socialite
composer require auth0/login
composer require spatie/laravel-permission
composer require spatie/laravel-settings
composer require livewire/livewire
composer require laravel/reverb
composer require barryvdh/laravel-dompdf
composer require phpoffice/phpspreadsheet
composer require phpoffice/phpword

composer require --dev pestphp/pest
composer require --dev pestphp/pest-plugin-laravel
composer require --dev larastan/larastan
composer require --dev laravel/pint
composer require --dev laravel/telescope
```

### Paso 0.1.4 — Setup de frontend
```bash
npm install
npm install -D tailwindcss@^3 postcss autoprefixer
npm install alpinejs
npm install flowbite
npm install apexcharts
npm install @heroicons/vue
npx tailwindcss init -p
```

### Paso 0.1.5 — Configurar tailwind.config.js
Aplicar la paleta GPT Services completa según sección 7 del plan ejecutable. Incluir colores `gpt-{50..950}`, `gpt-red-{50..950}`, fuente Inter como default.

### Paso 0.1.6 — Setup CI/CD (GitHub Actions)
Crear `.github/workflows/ci.yml` con tres jobs paralelos:
- `tests` — Pest + PHPUnit con MySQL service.
- `lint` — Pint check.
- `analysis` — Larastan nivel 5.

**Checkpoint M0.1:**
```bash
php artisan serve  # debe arrancar sin errores
npm run dev        # debe compilar tailwind
php artisan test   # debe pasar test default de Laravel
```

## 0.2 Migraciones base del módulo

### Paso 0.2.1 — Migración system_settings
```bash
php artisan vendor:publish --provider="Spatie\LaravelSettings\LaravelSettingsServiceProvider" --tag="migrations"
php artisan make:migration create_settings_table
```

Crear tabla `system_settings` (key-value JSON) con seeder inicial:
- `minuta_entrega_obligatoria` = false (D10)
- `bloqueo_cierre_dossier_incompleto` = true (D11)
- `bloqueo_cierre_post_mortem_pendiente` = false
- `auth_dominios_corporativos` = `["gptservices.com", "satechenergy.com"]`
- `concentracion_cliente_alerta_umbral` = 50

### Paso 0.2.2 — Estructura de directorios para servicios
Crear esta estructura:
```
app/
  Services/
    Auth/
    Rh/
    Cotizaciones/
    Proyectos/
    Libro/
    Finanzas/
    Asignaciones/
  ValueObjects/
  Mappers/
  DTOs/
```

**Checkpoint M0:**
- [ ] Repo en GitHub con branch protection.
- [ ] CI verde en primer push.
- [ ] `system_settings` con valores default sembrados.
- [ ] Estructura de carpetas creada.

---

# Módulo 1 — Autenticación multi-proveedor + RH + Roles

**Hitos asociados:** Hito 0 (auth básico), Hito 1 (RH + roles)
**Dependencias:** Módulo 0
**Estimación:** 2 sprints

## 1.1 Modelo de datos de identidad

### Paso 1.1.1 — Migración users (extendida)
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('avatar_url')->nullable();
    $table->string('departamento')->nullable();
    $table->string('puesto')->nullable();
    $table->string('employee_id')->nullable();
    $table->boolean('es_socio')->default(false);
    $table->boolean('es_socio_override')->nullable();
    $table->enum('status', ['active', 'invited', 'suspended'])->default('active');
    $table->timestamp('email_verified_at')->nullable();
    $table->timestamp('last_login_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
});
```

### Paso 1.1.2 — Migración auth_providers
```php
Schema::create('auth_providers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->enum('provider', ['auth0', 'email_password', 'google', 'microsoft', 'apple']);
    $table->string('provider_user_id')->nullable();
    $table->string('password_hash')->nullable();
    $table->boolean('is_primary')->default(false);
    $table->timestamp('linked_at');
    $table->timestamp('last_used_at')->nullable();
    $table->timestamps();
    $table->unique(['provider', 'provider_user_id']);
});
```

### Paso 1.1.3 — Migración socios_allowlist y email_allowlist
Como en el plan ejecutable sección 5.1.

### Paso 1.1.4 — Migración rh_role_mapping (D13)
```php
Schema::create('rh_role_mapping', function (Blueprint $table) {
    $table->id();
    $table->string('puesto_rh');
    $table->string('rol_sistema');
    $table->integer('prioridad')->default(50);
    $table->string('departamento_filter')->nullable();
    $table->boolean('activo')->default(true);
    $table->timestamps();
});
```

### Paso 1.1.5 — Spatie Permission setup
```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
```

### Paso 1.1.6 — Seeder de los 22 roles
Crear `RolesPermissionsSeeder` con los 22 roles del plan sección 6 (D13 sin herencia). Los permisos deben estar declarados explícitamente para cada rol (no usar `givePermissionTo` heredado).

Roles: `super_admin`, `direccion_general`, `socio`, `comite_socios`, `director_dn`, `comercial`, `gerente_proyectos`, `ingeniero_costos`, `ingeniero_proyectos`, `trainee_proyectos`, `gerente_operaciones`, `serv_tecnicos`, `soldadura`, `serv_generales`, `qhse`, `almacen`, `manufactura`, `compras`, `ingenieria_diseño`, `cfo`, `analista_financiero`, `finanzas_general`, `cliente_externo`, `auditor_externo`.

### Paso 1.1.7 — Seeder de rh_role_mapping
Sembrar las 10 reglas iniciales según plan sección 4.4.

## 1.2 Servicios de autenticación

### Paso 1.2.1 — `App\Services\Rh\RhClient`
Interfaz `RhClientInterface` con métodos:
- `searchByEmail(string $email): ?RhUser`
- `getById(string $userId): ?RhUser`
- `listAll(): Collection`

Implementaciones:
- `RhClientHttp` — cliente real con cache 30min + circuit breaker (usar `Illuminate\Support\Facades\Http` con retry).
- `RhClientMock` — datos hardcoded para desarrollo (usar cuando `RH_API_USE_MOCK=true`).

Bind en `AppServiceProvider` según env.

### Paso 1.2.2 — `App\Services\Auth\RoleMapper`
```php
class RoleMapper {
    public function resolveRoleForUser(User $user): string {
        // Consulta rh_role_mapping ordenado por prioridad DESC
        // Aplica matching LIKE sobre puesto_rh
        // Si matchea, retorna rol_sistema
        // Fallback: 'ingeniero_proyectos'
    }
}
```

### Paso 1.2.3 — `App\Services\Auth\SocioResolver`
Cascada D2: API RH → override manual → email allowlist (cache 30min).

### Paso 1.2.4 — Controllers de auth
- `Auth\Auth0Controller` — login + callback Auth0.
- `Auth\EmailPasswordController` — login + register (admin only).
- `Auth\SocialiteController` — login + callback genérico para Google/MS/Apple.
- `Auth\LinkProviderController` — vincular método adicional a usuario existente.

Cada controller delega a `AuthOrchestrator` que maneja la lógica común.

### Paso 1.2.5 — `App\Services\Auth\AuthOrchestrator`
Método principal `loginOrProvision(provider, providerUserId, email, profileData): User`:
1. Buscar `auth_provider` existente.
2. Si existe → `last_used_at = now()`, retornar `user`.
3. Si no existe → buscar `user` por email.
4. Si user existe → crear `auth_provider`, vincular.
5. Si user no existe:
   - Si email es corporativo → consultar RH, autocrear con datos RH, asignar rol via RoleMapper.
   - Si email no corporativo → verificar `email_allowlist`, autocrear con `role_default`.
   - Si nada matchea → throw exception "no autorizado".
6. Notificar nuevo login.

## 1.3 Pantallas de admin

### Paso 1.3.1 — `/admin/usuarios`
CRUD completo de users con filtros por rol, status, departamento. Acción "Invitar usuario externo" (envía email con link de creación de password).

### Paso 1.3.2 — `/admin/socios`
Listado con switch para `es_socio_override`. Tab para gestionar `socios_allowlist`.

### Paso 1.3.3 — `/admin/rh-mapping`
CRUD de reglas con preview: "Esta regla afectaría a X usuarios actualmente". Drag&drop para reordenar prioridad.

### Paso 1.3.4 — `/admin/roles-permisos`
Solo lectura inicialmente (cambios de permisos van por código). Visualizador tipo matriz role × permission.

## 1.4 Layout y navegación base

### Paso 1.4.1 — `<x-layouts.app>`
Componente Blade con sidebar + topbar + main + footer. Slot principal.

### Paso 1.4.2 — `<x-sidebar>`
- Alpine.js para colapsar (w-64 ↔ w-16).
- localStorage para persistir estado.
- Secciones agrupadas: Principal / Comercial / Proyectos / Operaciones / Finanzas / Ejecutivo / Admin.
- Items condicionales por permisos del usuario.

### Paso 1.4.3 — `<x-topbar>`
- Hamburguesa toggle del sidebar.
- Search global (placeholder por ahora).
- Botón notificaciones (badge contador).
- Botón chat (badge si hay no leídos).
- Avatar dropdown con: Perfil / Mi asignación / Configuración / Cerrar sesión.
- Botón "Nueva oportunidad" (visible solo para `comercial`, `director_dn`, `direccion_general`).

## 1.5 Tests del módulo

```php
// tests/Feature/Auth/Auth0LoginTest.php
test('user with corporate email gets provisioned via RH on first login');
test('user with non-corporate email is rejected on Auth0');
test('rh_role_mapping assigns correct role based on puesto');
test('fallback role applied when no rh_role_mapping matches');

// tests/Feature/Auth/EmailPasswordTest.php
test('preregistered user can login with email and password');
test('non-preregistered user cannot login');

// tests/Feature/Auth/SocialiteTest.php
test('google login allowed for whitelisted email');
test('google login rejected for non-whitelisted email');

// tests/Feature/Auth/MultiProviderLinkingTest.php
test('user can link multiple auth providers');
test('linking same provider twice updates last_used_at');

// tests/Unit/SocioResolverTest.php
test('socio resolver checks rh first');
test('socio resolver falls back to override');
test('socio resolver falls back to allowlist');
```

**Checkpoint M1:**
- [ ] Login con los 3 métodos funcional.
- [ ] Auto-provisión desde RH funcional con MockRhClient.
- [ ] 22 roles sembrados con permisos independientes.
- [ ] 10 reglas de mapeo RH→rol activas.
- [ ] Layout responsive con sidebar colapsable.
- [ ] Suite de tests >25 tests pasando.

---

# Módulo 2 — Catálogos comerciales y CP

**Hitos asociados:** Hito 2
**Dependencias:** Módulo 1
**Estimación:** 1 sprint

## 2.1 Catálogos base

### Paso 2.1.1 — Migraciones
- `clientes` (razon_social, alias_3letras, rfc, sector, segmento, activo).
- `contactos_cliente` (cliente_id, nombre, puesto, email, telefono, principal).
- `sublineas` (codigo, nombre, descripcion). Seed: HTP, LSP, VLV, SOL, SG.
- `secuencias` (tipo, año, ultimo_consecutivo). Tipos: cp, dn.

### Paso 2.1.2 — Models con relaciones
Eloquent models con `$fillable`, scopes, `belongsTo`/`hasMany`. Aplicar `SoftDeletes` en `clientes` y `proyectos`.

### Paso 2.1.3 — Seeder de catálogos iniciales
Sembrar los clientes principales del Status 2026: SEDENA→SDN, IGASAMEX→IGA, PROTEXA→PTX, ESENTIA→FER, ENGIE→ENG, etc. (lista completa del archivo `Status_ofertas_GPT_Services_2026.xlsx`).

## 2.2 Modelo de proyectos

### Paso 2.2.1 — Migración proyectos
```php
Schema::create('proyectos', function (Blueprint $table) {
    $table->id();
    $table->string('tech_reference')->nullable()->unique();
    $table->string('cp_numero')->nullable()->unique();
    $table->string('dn_numero')->nullable()->unique();
    $table->year('año');
    $table->foreignId('cliente_id')->constrained();
    $table->foreignId('sublinea_id')->constrained();
    $table->string('usuario_final')->nullable();
    $table->string('sector')->nullable();
    $table->enum('estado', [
        'en_revision', 'cotizando', 'cotizado', 'presentado',
        'adjudicado_pendiente', 'adjudicado_firmado', 'en_ejecucion',
        'en_cierre', 'cerrado', 'cancelado', 'perdido', 'archivado'
    ])->default('en_revision');
    $table->date('fecha_inicio_planeada')->nullable();
    $table->date('fecha_fin_planeada')->nullable();
    $table->enum('metodo_distribucion_plurianual', ['dias_naturales', 'hitos'])->default('dias_naturales');
    $table->foreignId('director_dn_id')->nullable()->constrained('users');
    $table->foreignId('gerente_proyectos_id')->nullable()->constrained('users');
    $table->foreignId('gerente_operaciones_id')->nullable()->constrained('users');
    $table->foreignId('ingeniero_costos_id')->nullable()->constrained('users');
    $table->foreignId('ingeniero_proyectos_id')->nullable()->constrained('users');
    $table->foreignId('trainee_id')->nullable()->constrained('users');
    $table->text('notas')->nullable();
    $table->softDeletes();
    $table->timestamps();
});
```

### Paso 2.2.2 — Migración proyecto_eventos
Tabla polimórfica de timeline. Tipos: `cp_asignado`, `cp_aprobado`, `cotizacion_emitida`, `tech_reference_generado`, `dn_asignado`, `minuta_firmada`, `kom_interno`, `kom_cliente`, `viaticos_aprobados`, `bitacora_cargada`, `desviacion_reportada`, `carta_finiquito_emitida`, `post_mortem_completado`.

### Paso 2.2.3 — `App\Services\Proyectos\SecuenciasService`
```php
public function asignarCp(int $año): string {
    return DB::transaction(function() use ($año) {
        $sec = Secuencia::where('tipo', 'cp')->where('año', $año)
            ->lockForUpdate()->firstOrCreate(['tipo' => 'cp', 'año' => $año], ['ultimo_consecutivo' => 0]);
        $sec->increment('ultimo_consecutivo');
        return sprintf('CP-%03d/%02d', $sec->ultimo_consecutivo, $año % 100);
    });
}
// análogo para asignarDn
```

### Paso 2.2.4 — `App\Services\Proyectos\TechReferenceService`
Validación regex: `^\d{6}-\d{1,2}-[A-Z]{3}-[A-Z&]{3,4}\s+(\d+x\d+|x)\s+_.{1,80}$`
Generador automático con campos: fecha, consecutivo, alias_cliente, sublinea, dimensiones, descripcion.

## 2.3 Resumen Ejecutivo y flujo de aprobación

### Paso 2.3.1 — Pantalla "Nueva oportunidad"
Formulario Livewire con stepper:
1. Datos básicos (cliente, sublinea, usuario_final, sector).
2. Resumen ejecutivo (alcance, plazo estimado).
3. Asignación de director_dn responsable.
4. Asignación atómica de CP al guardar.

### Paso 2.3.2 — Pantalla `/oportunidades` (Status de Ofertas)
Tabla Livewire con filtros: año, sublinea, cliente, estado, gerente_proyectos asignado. Columnas: CP, Tech Ref, Cliente, Sublinea, Estado, Monto preliminar, Líder, Última actividad.

### Paso 2.3.3 — Aprobación de CP por Comité Comercial
Modal "Aprobar CP" con:
- Botón Aprobar/Rechazar con campo notas.
- Asignación de `gerente_proyectos` que tomará el CP.
- Genera evento `cp_aprobado`.
- Notifica al gerente asignado.

### Paso 2.3.4 — Asignación interna por Gerente de Proyectos
Pantalla `/proyectos/cp/{id}/asignar` donde el gerente asigna `ingeniero_costos` o `ingeniero_proyectos` o `trainee_proyectos` al CP.

## 2.4 Exportación FO-GPT-VTS-01-B

### Paso 2.4.1 — `App\Services\Exports\ResumenEjecutivoExport`
Generador con PhpSpreadsheet que replica exactamente el template `FO-GPT-VTS-01-B`. Botón "Exportar Excel" en cada CP.

## 2.5 Tests del módulo

```php
test('cp asignado atomicamente sin colisiones bajo concurrencia');
test('tech reference rechaza formato inválido');
test('director_dn solo puede crear oportunidades');
test('gerente_proyectos puede asignar cp a su equipo');
test('trainee no puede aprobar cp');
test('export excel resumen ejecutivo coincide con template');
```

**Checkpoint M2:**
- [ ] Crear oportunidad → asignar CP → aprobar → asignar a ingeniero. Flujo completo.
- [ ] 18 oportunidades del Status 2026 cargadas via seeder.
- [ ] Exportar a Excel y comparar con template original.
- [ ] Tests pasando.

---

# Módulo 3 — Cotización (COSS) y Ficha de Proyecto

**Hitos asociados:** Hito 3
**Dependencias:** Módulo 2
**Estimación:** 1 sprint

## 3.1 Solicitudes internas durante CP

### Paso 3.1.1 — Migraciones
- `solicitudes_internas` (tipo enum: requisicion_compras / orden_trabajo_ingenieria, proyecto_id, cp_numero, codigo_formato, estado, solicitante_id, asignado_id, fecha_solicitud, fecha_respuesta_requerida, fecha_respuesta_real).
- `solicitudes_internas_items` (solicitud_id, descripcion, cantidad, unidad, especificacion, observaciones).

### Paso 3.1.2 — Pantallas
- `/proyectos/{id}/solicitudes` — listar y crear.
- Modal de creación con tabs: Compras / Ingeniería.
- Notificación al rol correspondiente (`compras` o `ingenieria_diseño`).

## 3.2 Modelo de cotización con COSS

### Paso 3.2.1 — Migraciones
- `cotizaciones` (proyecto_id, version, costo_directo, factor_indirectos, factor_admin, factor_utilidad, precio_venta_calculado, precio_venta_final, moneda, status, generado_por, fecha_emision, observaciones).
- `cotizacion_partidas` (cotizacion_id, numero_partida, descripcion, cantidad, unidad, costo_unitario, costo_total, observaciones).

### Paso 3.2.2 — `App\Services\Cotizaciones\CalculadoraCoss`
**Crítico**: replicar exacto las fórmulas de `FO-GPT-VTS-01-F`. La validación contra el fixture Texmelucan ($61,717.61 → $96,998.30, margen 41.59%) es bloqueo de deploy.

```php
class CalculadoraCoss {
    public function calcular(array $partidas, array $factores): ResultadoCoss {
        // costo_directo = sum(partidas.costo_total)
        // indirectos = costo_directo * factores['indirectos']
        // admin = (costo_directo + indirectos) * factores['admin']
        // base = costo_directo + indirectos + admin
        // utilidad = base * factores['utilidad']
        // precio_venta = base + utilidad
        // margen_neto = utilidad / precio_venta
        // ... implementación exacta del Excel original
    }
}
```

## 3.3 UI de captura de cotización

### Paso 3.3.1 — Componente Livewire `CotizacionEditor`
- Tabla editable de partidas con cálculo en vivo.
- Sliders/inputs para factores con preview de impacto.
- Sidebar con resumen de totales (costo directo, indirectos, admin, utilidad, precio venta, margen).
- Botón "Guardar como nueva versión".
- Historial de versiones colapsable.

### Paso 3.3.2 — Generación de Tech Reference
Botón "Generar Tech Reference" en fase Ejecución del CP (no antes, según procedimiento). Wizard: fecha, consecutivo, alias_cliente, sublinea, dimensiones, descripción. Validación contra regex.

### Paso 3.3.3 — Generación de PDF de Cotización
`App\Services\Pdf\CotizacionPdfGenerator` con DomPDF replicando template `FO-GPT-VTS-01-E`. Membretado oficial GPT, partidas en tabla, condiciones comerciales (anticipo 50%, contra-entrega 50%), validez 30 días, firmas.

### Paso 3.3.4 — Generación de Ficha de Proyecto
`FichaProyectoExportService` con PhpSpreadsheet replicando `FO-GPT-VTS-01-F`. Activable cuando la cotización pasa a estado `interno_aprobado`.

## 3.4 Tests críticos

```php
test('coss texmelucan calcula precio venta exacto al centavo');
test('coss respeta orden de aplicación de factores');
test('cotizacion crea nueva version preservando anterior');
test('tech reference rechaza formatos invalidos');
test('pdf cotizacion contiene todas las partidas');
test('ficha proyecto excel coincide estructura con template');
```

**Checkpoint M3:**
- [ ] Reproducir cotización Texmelucan al centavo.
- [ ] Generar PDF y validar visualmente contra original.
- [ ] Suite de tests con fixture pasando.
- [ ] Solicitudes internas a Compras e Ingeniería emitidas y respondidas.

---

# Módulo 4 — Adjudicación, Minuta y DN

**Hitos asociados:** Hito 5 (parcial)
**Dependencias:** Módulo 3
**Estimación:** 1 sprint

## 4.1 Flujo de adjudicación

### Paso 4.1.1 — Pantalla `/proyectos/{id}/adjudicar`
Formulario con:
- Upload de orden de compra del cliente (PDF).
- Captura de número de OC, monto adjudicado, fecha.
- Asignación de hitos de pago.
- Botón "Adjudicar y crear DN".

### Paso 4.1.2 — Asignación atómica de DN
Al adjudicar: `SecuenciasService::asignarDn($año)` y se transiciona estado a `adjudicado_firmado`.

## 4.2 Minuta de Entrega CP→DN (D10)

### Paso 4.2.1 — Migraciones
- `minutas_entrega` (proyecto_id 1:1, fecha_reunion, hora_inicio, hora_fin, modalidad enum, orden_del_dia json, acuerdos json, pdf_path, firmado_at, status enum: borrador/firmada).
- `minuta_entrega_participantes` (minuta_id, user_id, rol_en_minuta, firma_pendiente, firmado_at).

### Paso 4.2.2 — Componente Livewire `MinutaStepper`
5 pasos:
1. **Datos básicos** — fecha, hora, modalidad.
2. **Orden del día** — lista editable de puntos.
3. **Acuerdos** — tabla con descripcion + responsable + fecha_compromiso.
4. **Participantes** — multiselect de users involucrados.
5. **Preview & firma** — render de PDF, botones "Guardar borrador" / "Firmar y emitir".

### Paso 4.2.3 — `App\Services\Pdf\MinutaEntregaPdfGenerator`
DomPDF que replica `FO-GPT-PYT-01-A`.

### Paso 4.2.4 — Setting `minuta_entrega_obligatoria`
Si `true`, validation rule en transición `adjudicado_firmado → en_ejecucion` exige minuta firmada existente. Si `false`, solo sugiere.

## 4.3 Activación del proyecto en ejecución

### Paso 4.3.1 — Asignación de equipo al DN
Al pasar a `en_ejecucion`:
- `gerente_proyectos` (heredado del CP).
- `ingeniero_proyectos` (asignado por gerente).
- `trainee_proyectos` (opcional).
- `gerente_operaciones` (puede ser pre-asignado o asignar ahora).

### Paso 4.3.2 — Notificaciones
A todo el equipo asignado: email + in-app. Listar nuevo proyecto en sus respectivos dashboards.

## 4.4 Apertura del Libro de Proyecto

### Paso 4.4.1 — Job `AperturaLibroProyectoJob`
Al pasar a `en_ejecucion`, se dispara automáticamente y crea el `libros_proyecto` + 10 `libro_secciones` (A-J) según sublínea, todas en estado `pendiente`. Detalles en Módulo 6.

## 4.5 Tests

```php
test('adjudicacion sin minuta cuando obligatoria bloquea transicion');
test('adjudicacion sin minuta cuando opcional permite transicion');
test('minuta firmada no puede ser editada');
test('dn asignado dispara notificaciones a equipo');
test('libro_proyecto se crea automaticamente al activar dn');
```

**Checkpoint M4:**
- [ ] Adjudicar Texmelucan, generar minuta firmada, asignar equipo, ver libro de proyecto creado con 10 secciones vacías.

---

# Módulo 5 — KOM, Cronograma, BOM/BOE y Listado de Suministros

**Hitos asociados:** Hito 7
**Dependencias:** Módulo 4
**Estimación:** 1 sprint

## 5.1 Kick Off Meetings

### Paso 5.1.1 — Migraciones
- `kick_off_meetings` (proyecto_id, tipo: kom_interno/kom_cliente, fecha, participantes json, agenda, minuta_pdf_path, cronograma_attached_id nullable).

### Paso 5.1.2 — Pantallas
- Botón "Programar KOM" en ficha de proyecto.
- Tipo seleccionable: interno (entre Proyectos y Operaciones) o cliente.
- Editor de agenda + minuta post-reunión.
- Generación de PDF de minuta KOM.

## 5.2 Cronograma

### Paso 5.2.1 — Migraciones
- `cronogramas` (proyecto_id, version, generado_por, archivo_origen_path nullable, fecha_inicio, fecha_fin).
- `cronograma_actividades` (cronograma_id, codigo, nombre, parent_id nullable, fecha_inicio_planeada, fecha_fin_planeada, fecha_inicio_real nullable, fecha_fin_real nullable, porcentaje_avance, predecesoras json).

### Paso 5.2.2 — Importador desde MS Project / Project Libre
Soportar: `.mpp`, `.xml` (Project XML), `.csv`. Parser simple. Validación visual antes de aceptar.

### Paso 5.2.3 — Visualización Gantt
Componente Alpine + ApexCharts. Render simple, drag opcional para v2.

## 5.3 BOM/BOE

### Paso 5.3.1 — Migraciones
- `bom_boe_items` (proyecto_id, tipo: BOM/BOE, descripcion, cantidad, unidad, status enum, responsable_id, fecha_requerida, observaciones).

Status enum: `en_almacen`, `por_afilar`, `por_fabricar`, `por_comprar`, `en_transito`, `entregado`.

### Paso 5.3.2 — Pantalla `/proyectos/{id}/bom-boe`
Tabla editable Livewire con filtros y bulk actions. Color de fila según status.

### Paso 5.3.3 — Validación cruzada
Si item está en `por_fabricar` debe tener `solicitudes_internas` tipo `orden_trabajo_ingenieria` asociada (warning, no error).

## 5.4 Listado de Suministros (FO-GPT-PYT-01-C)

### Paso 5.4.1 — Migraciones
- `listados_suministros` (proyecto_id 1:1, porcentaje_avance_global computed).
- `listados_suministros_items` (listado_id, descripcion, cantidad, unidad, fecha_requerida, status, porcentaje_avance, etapa).

Etapas según procedimiento:
- 0-25%: definición / cotización / OC emitida.
- 26-50%: revisión ingeniería / fabricación iniciada.
- 51-75%: fabricación / FAT.
- 76-100%: embarque / dossier.

### Paso 5.4.2 — Pantalla `/proyectos/{id}/suministros`
Tabla con barras de progreso por item. Total agregado en header.

### Paso 5.4.3 — Exportación FO-GPT-PYT-01-C
PhpSpreadsheet replicando template original.

**Checkpoint M5:**
- [ ] Importar cronograma de proyecto Texmelucan.
- [ ] Generar minuta KOM interno + cliente.
- [ ] Alimentar BOM/BOE con items reales (LIB 4.1km como referencia).
- [ ] Listado de suministros con avance >75%.

---

# Módulo 6 — Libro de Proyecto / Dossier ISO (D11)

**Hitos asociados:** Hito 6
**Dependencias:** Módulo 4 (apertura automática)
**Estimación:** 1 sprint

## 6.1 Modelo de datos

### Paso 6.1.1 — Migraciones
- `libros_proyecto` (proyecto_id 1:1, fecha_apertura, fecha_cierre_estimado, fecha_cierre_real, porcentaje_avance_global, bloqueado_para_cierre).
- `libro_secciones` (libro_id, codigo enum A-J, nombre, descripcion, porcentaje_avance, estado, responsable_id, observaciones).
- `libro_seccion_checklist` (seccion_id, item_descripcion, completado, evidencia_documento_id nullable, completado_por_id nullable, completado_at nullable).
- `libro_documentos` (seccion_id, nombre, archivo_path, version, mime_type, tamaño, subido_por_id, subido_at).

### Paso 6.1.2 — Seeder de plantillas por sublínea
Crear `LibroPlantillaSeeder` con definiciones por sublínea (HTP, LSP, VLV, SOL, SG):

```php
// Ejemplo HTP - Sección F (Certificados)
[
  'sublinea' => 'HTP',
  'seccion_codigo' => 'F',
  'checklist' => [
    'Certificados de soldadores asignados',
    'Certificados de operadores Hot Tap (T-101, TM-760, TM-1200)',
    'Certificados de equipos en obra',
    'Certificados de materiales (válvulas, bridas, accesorios)',
  ],
],
```

### Paso 6.1.3 — `App\Services\Libro\AperturaLibroService`
Crea libro + 10 secciones + checklist según sublínea del proyecto.

## 6.2 UI del libro de proyecto

### Paso 6.2.1 — Componente Livewire `LibroProyectoAcordeon`
- 10 secciones colapsables.
- Cada sección muestra: título, % avance, estado, responsable, contador de docs.
- Drag&drop para subir archivos a cada sección.
- Checklist editable con marcado de evidencia.
- Cálculo automático de % avance global.

### Paso 6.2.2 — `App\Services\Libro\AvanceCalculator`
- `% avance sección = (items checklist completados / items totales) * 100`.
- `% avance global = avg(% avance de las 10 secciones)`.
- Recalcular en cada update (Livewire dispatch).

### Paso 6.2.3 — Validación de cierre
Setting `bloqueo_cierre_dossier_incompleto`:
- `true` (default): no se puede emitir Carta Finiquito si dossier <100%.
- `false`: warning visible pero permite cerrar.

## 6.3 Generación del PDF consolidado

### Paso 6.3.1 — `App\Services\Libro\DossierConsolidadoGenerator`
Al cierre:
- Portada con datos del proyecto + cliente.
- Índice de las 10 secciones.
- Por cada sección: portadilla + concatenación de PDFs de la sección + páginas con metadatos.
- Hash MD5 del documento final para integridad.
- Browsershot para PDFs complejos con CSS.

## 6.4 Tests

```php
test('apertura libro crea 10 secciones segun sublinea');
test('checklist HTP tiene items correctos en seccion F');
test('avance global se recalcula al marcar item');
test('bloqueo cierre activo previene carta finiquito si dossier incompleto');
test('dossier consolidado contiene todos los pdfs de secciones');
```

**Checkpoint M6:**
- [ ] Abrir libro Texmelucan, alimentar las 10 secciones, llegar a 100%.
- [ ] Generar PDF consolidado.
- [ ] Validar bloqueo de cierre.

---

# Módulo 7 — Ejecución, Bitácora, Reporte Semanal, Viáticos

**Hitos asociados:** Hito 8
**Dependencias:** Módulo 4
**Estimación:** 1 sprint

## 7.1 Bitácora Diaria (FO-GPT-PYT-01-D)

### Paso 7.1.1 — Migración
```php
Schema::create('bitacora_diaria', function (Blueprint $table) {
    $table->id();
    $table->foreignId('proyecto_id')->constrained();
    $table->date('fecha');
    $table->text('relacion_actividades');
    $table->json('personal_gpt'); // [{nombre, rol, horas}]
    $table->json('equipos_en_sitio'); // [{equipo, serie, status}]
    $table->json('proveedores_subcontratistas')->nullable();
    $table->string('vobo_cliente_nombre')->nullable();
    $table->string('vobo_cliente_organizacion')->nullable();
    $table->date('vobo_cliente_fecha')->nullable();
    $table->string('vobo_cliente_firma_path')->nullable();
    $table->foreignId('cargado_por_id')->constrained('users');
    $table->timestamp('firmado_at')->nullable();
    $table->timestamps();
    $table->unique(['proyecto_id', 'fecha']);
});
```

### Paso 7.1.2 — UI captura
Pantalla móvil-friendly. Stepper:
1. Personal del día (autocomplete desde catálogo, horas).
2. Equipos en sitio (autocomplete + status).
3. Relación de actividades (textarea con shortcuts).
4. V°B° del cliente (foto firma o capture).
5. Submit.

### Paso 7.1.3 — Detección automática de desviaciones
Si la bitácora tiene keywords como "retraso", "no llegó material", "falla equipo", "incidente" → notificar a `gerente_proyectos` automáticamente.

## 7.2 Reportes Semanales

### Paso 7.2.1 — Migración
- `reportes_semanales` (proyecto_id, semana_inicio, semana_fin, contenido_html, generado_at, enviado_at, recipients json).

### Paso 7.2.2 — `App\Services\Reportes\GeneradorReporteSemanal`
Cada lunes 8am, job que para cada proyecto activo:
1. Toma bitácoras de la semana anterior.
2. Genera HTML estructurado: resumen ejecutivo + actividades realizadas + actividades planeadas + desviaciones + fotos.
3. Crea registro en `reportes_semanales` (estado borrador).
4. Notifica al `ingeniero_proyectos` para revisar y enviar.

### Paso 7.2.3 — Pantalla revisión y envío
Ingeniero revisa, edita HTML, agrega comentarios, define recipients, envía. Email con PDF adjunto + tracking de apertura.

## 7.3 Viáticos (FO-GPT-SSGG-01-A)

### Paso 7.3.1 — Migraciones
- `solicitudes_viaticos` (proyecto_id, periodo_inicio, periodo_fin, justificacion, status, solicitante_id, aprobador_serv_grales_id, aprobador_direccion_id, aprobado_at, pdf_path).
- `viaticos_personal` (solicitud_id, user_id, dias).
- `viaticos_partidas` (solicitud_id, concepto: hospedaje/alimentos/transporte, monto_estimado, monto_real nullable, observaciones).

### Paso 7.3.2 — Flujo de aprobación
1. `ingeniero_proyectos` crea solicitud.
2. Notifica a `serv_generales` (Sergio Ordaz) — primera aprobación.
3. Notifica a `direccion_general` — segunda aprobación.
4. Genera PDF firmable.
5. Tracking de gastos reales contra estimados al cierre.

### Paso 7.3.3 — Generación PDF FO-GPT-SSGG-01-A
DomPDF replicando template original.

**Checkpoint M7:**
- [ ] Capturar 5 bitácoras diarias de Texmelucan.
- [ ] Generar reporte semanal automático.
- [ ] Solicitar viáticos, aprobar 2 niveles, generar PDF.

---

# Módulo 8 — Cierre del Proyecto: Carta Finiquito y Post-Mortem

**Hitos asociados:** Hito 9
**Dependencias:** Módulos 6, 7
**Estimación:** 0.5 sprint

## 8.1 Carta Finiquito

### Paso 8.1.1 — Migración
- `cartas_finiquito` (proyecto_id, fecha_emision, personal_liberado json, equipos_liberados json, observaciones, pdf_path, firmado_cliente_at, firmado_gpt_at).

### Paso 8.1.2 — Pantalla emisión
- Validación previa: dossier 100% (si setting activo).
- Captura de personal liberado y equipos liberados.
- Generación de PDF (replica `RE-GPT-QHSE-106-D`).
- Workflow de firmas (cliente + GPT).

## 8.2 Post-Mortem Técnico-Operativo

### Paso 8.2.1 — Migración
- `post_mortem` (proyecto_id, fecha_sesion, participantes json, lecciones_aprendidas json, desviaciones_costo, desviaciones_tiempo, desviaciones_calidad, presupuesto_planeado, presupuesto_real, recomendaciones_mejora json, pdf_path).

### Paso 8.2.2 — Pantalla
Editor Markdown con secciones predefinidas. Cálculo automático de delta costo/tiempo. Comparativa visual presupuesto vs real.

### Paso 8.2.3 — KPI ejecutivo
`% proyectos cerrados con post-mortem completado` — visible en dashboard ejecutivo.

## 8.3 Cierre formal del proyecto

### Paso 8.3.1 — Transición a `cerrado`
Validaciones:
- Carta finiquito firmada por ambas partes.
- Dossier 100% (si setting activo).
- Post-mortem completado (si setting activo).

**Checkpoint M8:**
- [ ] Cerrar proyecto Texmelucan completo.
- [ ] Dossier consolidado descargable.
- [ ] Post-mortem con métricas reales.

---

# Módulo 9 — Reporte de Asignación por persona (D12)

**Hitos asociados:** Hito 10
**Dependencias:** Módulos 2, 4
**Estimación:** 0.5 sprint (en paralelo con M8)

## 9.1 Modelo de datos

### Paso 9.1.1 — Migración
```php
Schema::create('asignaciones_personas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->integer('mes');
    $table->year('año');
    $table->integer('cp_asignados')->default(0);
    $table->integer('cp_ejecutados')->default(0);
    $table->integer('cp_remanentes')->default(0);
    $table->integer('cp_residual_anterior')->default(0);
    $table->integer('dn_activos')->default(0);
    $table->integer('dn_stand_by')->default(0);
    $table->integer('dn_cerrados')->default(0);
    $table->integer('dn_cancelados')->default(0);
    $table->integer('total_servicio')->default(0);
    $table->integer('total_suministro')->default(0);
    $table->enum('gerencia_regional', ['GRC', 'GRS', 'GRN', 'DG', 'GPT-IM'])->nullable();
    $table->timestamp('generado_at');
    $table->unique(['user_id', 'mes', 'año']);
});
```

## 9.2 Job programado

### Paso 9.2.1 — `App\Jobs\GenerarSnapshotAsignacionesJob`
- Ejecuta día 1 de cada mes a las 00:30.
- Para cada user activo del Departamento de Proyectos (roles: gerente_proyectos, ingeniero_costos, ingeniero_proyectos, trainee_proyectos, gerente_operaciones).
- Calcula contadores agregados consultando `proyectos` y `proyecto_eventos`.
- Inserta o actualiza `asignaciones_personas` del mes.

Schedule en `app/Console/Kernel.php`:
```php
$schedule->job(new GenerarSnapshotAsignacionesJob())->monthlyOn(1, '00:30');
```

### Paso 9.2.2 — Comando manual
```bash
php artisan asignaciones:snapshot --mes=current
php artisan asignaciones:snapshot --mes=2026-03
```

## 9.3 Pantalla `/proyectos/asignaciones`

### Paso 9.3.1 — Componente Livewire `ReporteAsignacionPersonas`
- KPIs: equipo total, proyectos activos, carga promedio, sobrecarga.
- Heatmap mes×persona con código de color (verde 0-4 / amarillo 5-7 / naranja 8-9 / rojo 10+).
- Filtros: año, gerencia regional, posición.
- Drill-down: clic en persona → vista expandida con datos RH.

### Paso 9.3.2 — Vista expandida individual
- Datos RH: posición, ingreso, antigüedad, formación.
- Asignación del mes en curso (CP + DN desglosados).
- Distribución servicio/suministro.
- Gráfica trimestral de carga.

### Paso 9.3.3 — Exportación FO-GPT-PYT-01-B
Excel con formato exacto del template original.

## 9.4 Vista personal

### Paso 9.4.1 — Pantalla `/perfil/mi-asignacion`
Cada usuario ve solo su tarjeta. Útil para auto-evaluación de carga.

**Checkpoint M9:**
- [ ] Heatmap correcto del equipo de 7 personas para Q1 2026.
- [ ] Excel exportado coincide con template.
- [ ] Cada usuario ve solo su tarjeta en perfil.

---

# Módulo 10 — Notificaciones y Chat (Reverb)

**Hitos asociados:** Hito 11
**Dependencias:** Módulo 1
**Estimación:** 1 sprint

## 10.1 Notificaciones in-app + email

### Paso 10.1.1 — Setup notifications
Usar `Illuminate\Notifications\Notifiable` + tabla `notifications` estándar Laravel. Channels: `database`, `mail`, `broadcast`.

### Paso 10.1.2 — Notificaciones definidas
- `CpAsignadoNotification`
- `CotizacionListaNotification`
- `MinutaPendienteFirmaNotification`
- `KomProgramadoNotification`
- `ViaticosAprobadosNotification`
- `DossierIncompletoAlertaNotification`
- `DesviacionReportadaNotification`
- `CierreMensualGeneradoNotification`

### Paso 10.1.3 — Componente `<x-notification-bell>`
- Badge con contador de no leídas.
- Dropdown con últimas 10.
- Marcar como leído al hacer hover.
- Link al detalle de cada una.

## 10.2 Chat embebido

### Paso 10.2.1 — Migraciones
- `chat_canales` (tipo: proyecto/departamento/direccion/privado, contexto_id nullable, nombre, descripcion, creado_por_id).
- `chat_canal_miembros` (canal_id, user_id, rol_en_canal, joined_at).
- `chat_mensajes` (canal_id, user_id, parent_message_id nullable, contenido, edited_at, attachments json).
- `chat_menciones` (mensaje_id, user_id, leido_at).
- `chat_lecturas` (canal_id, user_id, ultimo_mensaje_leido_id).

### Paso 10.2.2 — Reverb setup
```bash
php artisan install:broadcasting
php artisan reverb:start
```

Configurar `BROADCAST_CONNECTION=reverb`.

### Paso 10.2.3 — Componente Livewire `ChatPanel`
- Lista de canales lateral.
- Vista de mensajes con scroll infinito.
- Input con menciones @user (autocomplete).
- Threading básico.
- Adjuntos (drag&drop).
- Búsqueda histórica.

### Paso 10.2.4 — Auto-creación de canales
Al crear un proyecto, se crea automáticamente:
- Canal `proyecto-{cp_numero}` con todos los asignados.
- Cada departamento tiene su canal permanente.
- Canal `direccion` solo para direccion_general + socios.

**Checkpoint M10:**
- [ ] 5 usuarios chateando en simultáneo.
- [ ] Notificaciones email + in-app + push en tiempo real.
- [ ] Búsqueda histórica funcional.

---

# Módulo 11 — Finanzas (Cuentas + Estados de Cuenta)

**Hitos asociados:** Hito 12
**Dependencias:** Módulo 1
**Estimación:** 1 sprint

## 11.1 Modelo de datos

### Paso 11.1.1 — Migraciones
- `cuentas_bancarias` (banco enum, alias, numero_cuenta_enmascarado, clabe_enmascarada, moneda, activa).
- `estados_cuenta` (cuenta_id, mes, año, archivo_origen_path, parseado_at, total_movimientos).
- `movimientos_bancarios` (estado_cuenta_id, fecha, descripcion, monto, tipo enum, conciliado_con_proyecto_id nullable, conciliado_con_factura nullable).

### Paso 11.1.2 — Parsers por banco
Implementaciones: `BbvaParser`, `BanorteParser`, `BanamexParser`, `SantanderParser`. Soporte CSV + PDF (con OCR fallback).

## 11.2 Conciliación

### Paso 11.2.1 — Pantalla `/finanzas/conciliacion`
Vista split: izquierda movimientos pendientes, derecha proyectos/facturas para asociar. Drag&drop para conciliar. Match automático >80% de coincidencia.

## 11.3 Restricción de acceso

### Paso 11.3.1 — Middleware `EnsureFinanzasAccess`
Solo: `cfo`, `direccion_general`, `socio`, `comite_socios`, `analista_financiero`. Aplicado a todas las rutas `/finanzas/*`.

**Checkpoint M11:**
- [ ] Cargar 1 estado de cuenta real, conciliar 80% automáticamente.

---

# Módulo 12 — Cierres SAT y Gerencial (D1)

**Hitos asociados:** Hito 13
**Dependencias:** Módulo 11
**Estimación:** 1 sprint

## 12.1 Modelo

### Paso 12.1.1 — Migraciones
- `cierres_mensuales` (mes, año, tipo: contable_sat/gerencial_avance, fecha_corte, status, generado_por_id, aprobado_por_id, observaciones).
- `cierres_secciones` (cierre_id, codigo: sat_base/devengado/pipeline_ponderado, total).
- `cierres_lineas` (seccion_id, proyecto_id, monto, porcentaje_aplicado, observaciones).

## 12.2 Generadores

### Paso 12.2.1 — `App\Services\Finanzas\GeneradorCierreSatService`
Toma facturas emitidas del mes (vía estados de cuenta + algún catálogo de facturación). Sección única `sat_base`.

### Paso 12.2.2 — `App\Services\Finanzas\GeneradorCierreGerencialService`
Genera 3 secciones:
1. **SAT base**: idéntico a cierre SAT.
2. **Devengado**: proyectos con OC firmada, prorrateado por días o hitos según `metodo_distribucion_plurianual`.
3. **Pipeline ponderado**: proyectos no firmados × probabilidad de adjudicación.

### Paso 12.2.3 — Reporte PDF
3 secciones claramente separadas. Comparativa SAT vs gerencial con delta. Gráfica de waterfall.

## 12.3 Snapshots históricos

### Paso 12.3.1 — `resultados_financieros`
Cada cierre generado dispara snapshot por proyecto para análisis temporal.

**Checkpoint M12:**
- [ ] Generar cierre gerencial de marzo en <1 minuto con las 3 secciones cuadrando.

---

# Módulo 13 — Vista Ejecutiva

**Hitos asociados:** Hito 14
**Dependencias:** Módulos 9, 12
**Estimación:** 1 sprint

## 13.1 Dashboard ejecutivo

### Paso 13.1.1 — Pantalla `/ejecutivo`
KPIs:
- Pipeline activo (monto + conteo) con filtro con/sin SEDENA.
- Adjudicado YTD (monto + conteo).
- **Hit rate por conteo** (D7) — adjudicados / presentados.
- **Hit rate por monto** (D7) — monto adjudicado / monto ofertado.
- Cartera comprometida del mes.
- Margen promedio.
- % proyectos con dossier completo.
- % proyectos con post-mortem.
- Carga promedio del equipo (D12).

### Paso 13.1.2 — Gráficas
- Concentración por cliente (bar) con alerta SEDENA >50%.
- Pipeline por sublinea (donut).
- Adjudicaciones mensuales (line).
- Hit rate trimestral (line con dos series).

### Paso 13.1.3 — Tabla "Ofertas que requieren atención"
Top 5 oportunidades con score de urgencia (días desde última actividad + valor + probabilidad).

### Paso 13.1.4 — Filtros globales
- Periodo configurable (mes / trimestre / año / custom — D8).
- Cliente.
- Sublinea.
- Con/sin SEDENA.

## 13.2 Reporte ejecutivo PDF

### Paso 13.2.1 — `App\Services\Reportes\ReporteEjecutivoPdfGenerator`
PDF mensual descargable con todos los KPIs + gráficas. Plantilla con membretado para enviar a socios.

**Checkpoint M13:**
- [ ] Socio entra y en <30s identifica las 3 ofertas más urgentes y la concentración por cliente.

---

# Módulo 14 — Hardening y Producción

**Hitos asociados:** Hito 15
**Dependencias:** Todos
**Estimación:** 1 sprint

## 14.1 Testing exhaustivo

### Paso 14.1.1 — Coverage >80% en módulos críticos
Cotización (M3), Finanzas (M11-12), Auth (M1), Libro Proyecto (M6).

### Paso 14.1.2 — Test E2E del flujo Texmelucan
Pest test que ejecuta el flujo completo: crear oportunidad → CP → cotización → adjudicación → minuta → DN → libro → KOM → bitácoras → cierre. Debe pasar en <5 minutos.

## 14.2 Seguridad

### Paso 14.2.1 — Revisión OWASP Top 10
- SQL injection (Eloquent + parametrizado).
- XSS (Blade escape default).
- CSRF (token Laravel).
- Auth bypass (tests por rol).
- Sensitive data exposure (logs sin secrets).
- IDOR (policies por modelo).

### Paso 14.2.2 — Audit log
`finanzas_audit` y similar para acciones críticas: cambios de rol, cambios de cierres, accesos a finanzas.

## 14.3 Performance

### Paso 14.3.1 — Query optimization
- Detectar N+1 con Telescope.
- Eager loading donde aplique.
- Índices compuestos.
- Cache de queries pesadas (heatmap, dashboards).

### Paso 14.3.2 — Frontend optimization
- Lazy loading de imágenes.
- Code splitting.
- Asset versioning.

## 14.4 Documentación

### Paso 14.4.1 — Docs de usuario por rol
Markdown navegable + screenshots.

### Paso 14.4.2 — Docs técnica
- README con setup local.
- ADRs de decisiones arquitectónicas (las 13 D's).
- API docs (si exponen endpoints).

## 14.5 Producción

### Paso 14.5.1 — Deploy script
GitHub Actions → SSH → `php artisan deploy`. Backup pre-deploy. Rollback automático si fallan healthchecks.

### Paso 14.5.2 — Monitoreo
- Sentry para errores.
- UptimeRobot para uptime.
- Laravel Telescope solo en staging.

### Paso 14.5.3 — Backups
- MySQL diario a S3.
- Archivos del libro de proyecto con versionado activo.
- Test de restore semanal.

**Checkpoint M14 (final):**
- [ ] 20 usuarios reales en producción.
- [ ] 0 incidentes en primera semana.
- [ ] Documentación firmada por DG y QHSE.
- [ ] Aprobación final.

---

# Anexo A — Convenciones para el agente

## Naming
- Clases: PascalCase (`CalculadoraCoss`).
- Variables/métodos: camelCase (`asignarCp`).
- Tablas: snake_case plural (`cotizaciones`).
- Archivos Blade: kebab-case (`reporte-asignacion.blade.php`).
- Componentes: PascalCase (`<x-stat-card>`).

## Commits
Formato conventional: `feat:`, `fix:`, `test:`, `docs:`, `refactor:`, `chore:`.
Ejemplo: `feat(cotizacion): implementa CalculadoraCoss con fixture Texmelucan`

## Antes de cada PR
```bash
./vendor/bin/pint
./vendor/bin/phpstan analyse
./vendor/bin/pest
npm run build
```

## Decisiones que requieren consulta al humano
- Cualquier cambio al modelo de datos del plan ejecutable.
- Cualquier nuevo rol o permiso no listado en sección 6.
- Cualquier integración externa nueva.
- Cualquier librería con licencia restrictiva.

---

# Anexo B — Mapeo módulos del plan paso a paso ↔ hitos del plan ejecutable

| Módulo | Hito(s) ejecutable |
|--------|---------------------|
| M0 Fundamentos | H0 |
| M1 Auth + RH + Roles | H0, H1 |
| M2 Catálogos + CP | H2 |
| M3 Cotización + COSS | H3 |
| M4 Adjudicación + Minuta | H5 (parcial) |
| M5 KOM + Cronograma + BOM/BOE + Suministros | H7 |
| M6 Libro de Proyecto | H6 |
| M7 Bitácora + Reporte Semanal + Viáticos | H8 |
| M8 Carta Finiquito + Post-Mortem | H9 |
| M9 Reporte Asignación | H10 |
| M10 Notificaciones + Chat | H11 |
| M11 Finanzas base | H12 |
| M12 Cierres SAT + Gerencial | H13 |
| M13 Vista Ejecutiva | H14 |
| M14 Hardening + Producción | H15 |

Nota: H4 (Status de ofertas + dashboard básico) está distribuido entre M2 y M9. H5 está dividido entre M3 (cotización) y M4 (adjudicación + minuta).

---

**Fin del plan paso a paso v1.0.**
