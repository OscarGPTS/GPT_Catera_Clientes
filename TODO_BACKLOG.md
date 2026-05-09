# TODO Backlog · GPT Services Platform

Tareas pendientes para ejecutar después. Cada una incluye contexto, archivos involucrados, criterio de aceptación y notas técnicas para retomarla con cero contexto.

Status global a la fecha: **Pest 263/263 passed (1 skipped Texmelucan), 860 aserciones**. Pint clean, Vite build OK, MySQL refrescada con seed completo.

---

## 1. Validación Texmelucan del CalculadoraCoss (M3)

**Por qué:** El procedimiento `PRO-GPT-PYT-01` usa el formato Excel `FO-GPT-VTS-01-F` como fuente de verdad para COSS. El plan ejecutable cita un caso de validación: costo directo $61,717.61 → precio venta $96,998.30, margen 41.59%. El test correspondiente está marcado como `skipped` en [tests/Unit/Cotizaciones/CalculadoraCossTest.php] hasta que el archivo Excel original esté disponible.

**Archivos involucrados:**
- `app/Services/Cotizaciones/CalculadoraCoss.php` — fórmulas actuales (línea ~20 tiene el TODO)
- `tests/Unit/Cotizaciones/CalculadoraCossTest.php` — test marcado skip
- `instructions/PLAN_EJECUTABLE_GPT_SERVICES.md` § 5.12

**Criterio de aceptación:**
- [ ] Cargar el Excel real `FO-GPT-VTS-01-F` con caso Texmelucan resuelto.
- [ ] Identificar los factores reales (`indirectos`, `admin`, `utilidad`) que producen `$96,998.30` desde `$61,717.61`.
- [ ] Si las fórmulas del Excel difieren de las actuales, ajustar `CalculadoraCoss::calcular()` y documentar cualquier diferencia.
- [ ] Quitar el `->skip()` del test y verificar que pasa al centavo (tolerancia ≤ $0.01) y margen ≤ 0.0001.
- [ ] Convertir el caso Texmelucan en **fixture obligatoria** del seeder de cotizaciones.

**Notas técnicas:** la fórmula actual asume cascada `costo_directo → +indirectos → +admin → base → +utilidad → precio_venta`. Si el Excel usa otra cascada (e.g. utilidad sobre venta en lugar de sobre base), el ajuste puede romper otros tests; correr `pest --filter=CalculadoraCoss` y `pest --filter=Cotizacion` después.

---

## 2. Parsers de estados de cuenta para Banamex, Santander y HSBC (M11b)

**Por qué:** Hoy el `EstadoCuentaService` solo soporta BBVA y Banorte. La interfaz `EstadoCuentaParser` ya está lista; sólo hay que agregar implementaciones por banco. Línea 69 de [EstadoCuentaService](app/Services/Finanzas/EstadoCuentaService.php) lanza `RuntimeException` con mensaje "TODO M11b" cuando la cuenta es de otro banco.

**Archivos involucrados:**
- `app/Services/Finanzas/Parsers/EstadoCuentaParser.php` (interfaz, ya existe)
- `app/Services/Finanzas/Parsers/BbvaParser.php` (referencia)
- `app/Services/Finanzas/Parsers/BanorteParser.php` (referencia)
- `app/Services/Finanzas/EstadoCuentaService.php` — método `parserPara()`

**Criterio de aceptación por banco:**
- [ ] **BanamexParser** — exportación CSV/TXT de Banamex Empresarial. Manejo de columnas `FECHA OPERACION`, `CONCEPTO`, `RETIROS`, `DEPOSITOS`, `SALDO`. Fechas formato `dd/mm/yyyy`.
- [ ] **SantanderParser** — formato CSV/Excel. Header en la línea 5-7 (Santander mete metadata arriba). Columna única `IMPORTE` con signo (negativo = egreso).
- [ ] **HsbcParser** — formato TSV o CSV. Considerar `Date`, `Description`, `Debit`, `Credit`, `Balance`. Fechas `dd-mmm-yy` (e.g. `01-May-26`).

**Para cada uno:**
1. Conseguir un archivo de muestra real del banco.
2. Implementar `parse(string $filePath): array` siguiendo el shape `{fecha, descripcion, monto, tipo}`.
3. Registrar en `EstadoCuentaService::parserPara()`.
4. Test Pest con fixture inline en `tests/Feature/Finanzas/{Banco}ParserTest.php` (mínimo 4 tests: parseo básico, separadores alternativos, header faltante = excepción, montos cero ignorados).

**Notas técnicas:** Santander y HSBC suelen exportar `.xls` binario nativo, no CSV. Si necesitas leer `.xls` directamente, usa `PhpOffice\PhpSpreadsheet` (ya instalado). Patrón:

```php
use PhpOffice\PhpSpreadsheet\IOFactory;
$reader = IOFactory::createReaderForFile($filePath);
$sheet = $reader->load($filePath)->getActiveSheet();
foreach ($sheet->getRowIterator(2) as $row) { ... }
```

---

## 3. Importador de archivos `.mpp` binario de MS Project (M5b ext)

**Por qué:** El formato `.mpp` es binario propietario de Microsoft. PHP no lo lee nativamente. Hoy `CronogramaImporterService::parserPara()` rechaza `.mpp` con mensaje útil sugiriendo "Save As → XML". Para clientes que insisten en subir el `.mpp` directo, hay que integrar [mpxj](https://github.com/joniles/mpxj) (Java/.NET).

**Archivos involucrados:**
- `app/Services/Proyectos/MsProject/CronogramaImporterService.php` (línea ~16 tiene el TODO)
- Crear `app/Services/Proyectos/MsProject/MpxjMppParser.php` que implementa `MsProjectParser`

**Opciones de integración (elegir una):**

### Opción A — mpxj-cli en proceso externo
1. Instalar Java 17+ en el servidor.
2. Descargar `mpxj-cli.jar` (https://github.com/joniles/mpxj-cli).
3. En `MpxjMppParser`:
   ```php
   $tmpJson = tempnam(sys_get_temp_dir(), 'mpp').'.json';
   $cmd = sprintf('java -jar mpxj-cli.jar --json %s %s 2>&1', escapeshellarg($filePath), escapeshellarg($tmpJson));
   exec($cmd, $output, $exitCode);
   if ($exitCode !== 0) throw new RuntimeException(...);
   $data = json_decode(file_get_contents($tmpJson), true);
   // mapear $data['tasks'] al shape común
   ```
4. Tests con fixture `.mpp` real (puedes descargar uno público de samples/).

### Opción B — micro-servicio Node con mpxj-js
- Más fácil de containerizar pero agrega un servicio extra.

### Opción C — solo aceptar XML (recomendación actual)
- Mantener `.mpp` rechazado. Es la opción que tenemos ahora.

**Criterio de aceptación:**
- [ ] Configuración `MPXJ_CLI_PATH` en `.env` (opcional; si no está, sigue rechazando).
- [ ] Parser implementado y registrado.
- [ ] Test que verifica el parseo de un `.mpp` de muestra contra resultados conocidos.
- [ ] Manejo de error cuando Java no está disponible (mensaje claro al usuario).

---

## 4. Reverb websockets — chat y notificaciones realtime (M10/M9 ext)

**Por qué:** Hoy `ChatPanel` usa `wire:poll.5s` y `NotificationBell` usa `wire:poll.30s`. Funciona pero no es realtime. El listener `#[On('echo:chat,MensajeEnviado')]` ya está conectado en `ChatPanel`, sólo faltan las credenciales y los broadcast events.

**Archivos involucrados:**
- `app/Livewire/Chat/ChatPanel.php` (listener Echo ya está)
- `app/Livewire/Notifications/NotificationBell.php`
- `config/broadcasting.php`
- `.env` — `BROADCAST_CONNECTION=reverb`, `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_HOST`, `REVERB_PORT`
- `resources/js/echo.js` (puede no existir)
- Crear `app/Events/MensajeEnviado.php` y otros eventos broadcast

**Criterio de aceptación:**
- [ ] Configurar `php artisan reverb:install` y arrancar `php artisan reverb:start` en proceso supervisado.
- [ ] Crear evento `MensajeEnviado` que `ShouldBroadcast` en canal privado `chat.{canalId}` con autorización por miembro.
- [ ] Disparar el evento desde `ChatService::enviarMensaje()`.
- [ ] Crear evento `NotificacionRecibida` que broadcast a `App.Models.User.{userId}`.
- [ ] Configurar Echo client en `resources/js/echo.js` con `Pusher`-compatible setup apuntando a Reverb.
- [ ] Reemplazar `wire:poll.5s` por `wire:poll.30s` (fallback) cuando Reverb esté funcionando.
- [ ] Tests con `Event::fake()` y `Event::assertDispatched()`.

**Notas técnicas:** Reverb requiere PHP 8.2+ (ya tenemos 8.2.12) y un proceso permanente. En producción, usar `supervisord` o `php artisan reverb:restart` con healthcheck. Para SSL, configurar `REVERB_HOST` con tu dominio y usar Caddy/Nginx como reverse proxy. La autorización del canal privado va en `routes/channels.php`:

```php
Broadcast::channel('chat.{canalId}', function ($user, $canalId) {
    return ChatCanal::find($canalId)?->miembros()->where('user_id', $user->id)->exists();
});
```

---

## 5. Mail channel para notificaciones críticas (M9 ext)

**Por qué:** Hoy todas las notificaciones usan únicamente el channel `database`. La única `Notification` que tiene `toMail()` listo es [CpAsignadoNotification](app/Notifications/CpAsignadoNotification.php), pero ya no se dispara (la sustituyó `CpAprobadoNotification`). Algunas notificaciones merecen también email (no todas):

**Notificaciones que SÍ deben mandar email:**
- `CpAprobadoNotification` — al GP recién asignado (acción urgente)
- `OcFirmadaNotification` — a GP/GO (debe levantar minuta)
- `ViaticosAprobadosNotification` — al solicitante (autorización para viajar)
- `PostMortemRequeridoNotification` — al GP/DG (D12 bloqueante)

**Notificaciones que pueden quedarse en database:**
- `CotizacionEmitidaNotification` — informativa
- `MinutaFirmadaNotification` — informativa
- `ReporteSemanalGeneradoNotification` — informativa (el reporte se manda aparte por mailer)

**Archivos involucrados:**
- 4 clases en `app/Notifications/` que necesitan agregar `toMail()` al método `via()`
- `config/mail.php` y `.env` — configurar `MAIL_MAILER`, `MAIL_HOST`, etc. (SMTP de GPT Services)
- `resources/views/emails/` — plantillas Markdown del mail
- Cola de jobs (recomendado para no bloquear request): `QUEUE_CONNECTION=database` o `redis`, queue worker

**Criterio de aceptación:**
- [ ] Configurar credenciales SMTP reales en `.env`.
- [ ] Para cada notificación crítica: agregar `'mail'` en `via()` y método `toMail()` con `MailMessage` con CTA al recurso.
- [ ] Hacer las clases `implements ShouldQueue` para no bloquear request.
- [ ] Test con `Notification::fake()` y `Notification::assertSentTo($user, X::class, function ($n) { return $n->toMail()->subject === 'esperado'; })`.
- [ ] Configurar `php artisan queue:work` en supervisord.

---

## 6. Conciliación masiva (M11 ext)

**Por qué:** Hoy `ConciliacionService::conciliar()` opera 1-a-1: el usuario abre un movimiento bancario, ve sugerencias y elige una. Para meses con 100+ movimientos esto es tedioso. Falta el "auto-concilia todo lo que tenga score ≥ 100" en bulk.

**Archivos involucrados:**
- `app/Services/Finanzas/ConciliacionService.php` — agregar `conciliarBulk(EstadoCuenta $estado, int $userId, int $scoreMinimo = 100)`
- `app/Http/Controllers/Finanzas/EstadosCuentaController.php` — agregar action `conciliarBulk`
- `resources/views/finanzas/estados/show.blade.php` — botón "Auto-conciliar (score ≥ 100)"

**Criterio de aceptación:**
- [ ] Service itera movimientos no conciliados, calcula sugerencias, concilia automáticamente los que tengan score ≥ umbral.
- [ ] Devuelve resumen `{conciliados: N, omitidos: M, errores: []}`.
- [ ] Cada conciliación dispara `FinanzasAuditService::registrar('movimiento_conciliado_bulk', ...)`.
- [ ] Endpoint POST con confirmación, limita a movimientos del estado actual.
- [ ] Test con fixture de varios movimientos (algunos con CP exacto en descripción → score 100, otros sin match → score 0).

**Notas técnicas:** Cuidar transacciones — si falla a la mitad, no dejar conciliados a medias. Usar `DB::transaction()`.

---

## 7. Auth0 + OAuth (Google/Microsoft/Apple) en producción (M1 ext)

**Por qué:** El stack de auth0 + Socialite ya está implementado en código y funciona end-to-end con mocks. En producción hay que registrar credenciales reales y exponer rutas que hoy están condicionadas por env.

**Archivos involucrados:**
- `app/Http/Controllers/Auth/Auth0Controller.php` (línea ~15 tiene el TODO)
- `app/Http/Controllers/Auth/SocialiteController.php` (línea ~15 tiene el TODO)
- `config/auth0.php` — `registerGuards`, `registerMiddleware`, `registerRoutes` están en `false` por defecto
- `config/services.php` — Google/Microsoft/Apple
- `.env` — `AUTH0_DOMAIN`, `AUTH0_CLIENT_ID`, `AUTH0_CLIENT_SECRET`, `GOOGLE_CLIENT_ID`, etc.

**Criterio de aceptación:**
- [ ] Crear cuenta Auth0 tenant productivo y registrar la aplicación con callback `https://app.gptservices.com/auth/auth0/callback`.
- [ ] Activar `AUTH0_REGISTER_GUARDS=true` en `.env`.
- [ ] Configurar Google Workspace OAuth con scopes `openid email profile`.
- [ ] Configurar Microsoft Azure AD OAuth con tenant compartido o single-tenant según política.
- [ ] Configurar Apple Sign In si aplica (requiere developer.apple.com).
- [ ] Probar flujo end-to-end con un usuario real de cada proveedor.
- [ ] Validar que el `AuthOrchestrator` (ya implementado) provisiona usuarios desde RH mock cuando sea necesario.
- [ ] Tests existentes en `tests/Feature/Auth/` deben seguir pasando.

**Notas técnicas:** El `AuthOrchestrator` ya implementa el flujo de 6 pasos: encuentra `AuthProvider` → encuentra `User` → crea `AuthProvider` faltante → provisiona vía RH si email corporativo → revisa allowlist si es externo → throws si no autorizado. Solo es cuestión de tener credenciales reales.

---

## 8. Reemplazar `RhClientMock` por `RhClientHttp` real (M1 ext)

**Por qué:** Hoy el `AuthOrchestrator` resuelve datos del empleado vía `RhClientMock` con 12 fixtures hardcodeadas. La clase `RhClientHttp` ya está implementada con cache 30min y circuit breaker, pero apunta a un endpoint que aún no existe.

**Archivos involucrados:**
- `app/Services/Rh/RhClientHttp.php` (línea ~16 TODO M1)
- `app/Services/Rh/RhClientMock.php` — solo para pruebas
- `config/gpt.php` — `rh.url`, `rh.token`, `rh.use_mock`
- `app/Providers/AppServiceProvider.php` — registrar implementación según `rh.use_mock`

**Criterio de aceptación:**
- [ ] Equipo de RH publica endpoint `GET /api/empleados/{email}` que devuelve `{nombre, puesto, departamento, employee_id, status, foto_url}`.
- [ ] Validar contrato real vs el `RhEmpleadoDto` que espera el `AuthOrchestrator`.
- [ ] Configurar `RH_USE_MOCK=false` en `.env` de staging y producción.
- [ ] Validar circuit breaker: tras 3 fallos consecutivos, falla rápido por 5 min.
- [ ] Validar cache 30min: hits no consumen cuota del API de RH.
- [ ] Tests con `Http::fake()` para flujos de éxito, fallo, timeout, payload malformado.

---

## 9. Probabilidades de pipeline configurables (M12 ext)

**Por qué:** En `GeneradorCierreGerencialService::seccionPipelinePonderado()` (línea ~130) hay una tabla hardcoded con `cotizando=20%`, `cotizado=30%`, `presentado=50%`, `adjudicado_pendiente=85%`. Estas probabilidades deberían venir de `system_settings` para que un CFO las ajuste sin tocar código.

**Archivos involucrados:**
- `app/Services/Finanzas/GeneradorCierreGerencialService.php`
- `database/seeders/SystemSettingsSeeder.php`
- (posiblemente) UI admin para editar settings

**Criterio de aceptación:**
- [ ] Agregar setting `pipeline_probabilidades` tipo `array` con default `{cotizando: 0.20, cotizado: 0.30, presentado: 0.50, adjudicado_pendiente: 0.85}`.
- [ ] `GeneradorCierreGerencialService` lee el setting en lugar de la constante.
- [ ] UI en `/admin/settings` (TODO también) para editarlas con validación 0 ≤ x ≤ 1.
- [ ] Test que verifica que cambiar el setting cambia los totales del cierre gerencial.

---

## 10. Prorrateo por hitos (M12.2)

**Por qué:** El `ProrrateadorPlurianual` (línea ~11) implementa prorrateo por días naturales (`metodo_distribucion_plurianual = 'dias_naturales'`). Hay un segundo método `'hitos'` declarado en la migración pero no implementado: el monto se distribuye en hitos de pago (anticipo 30%, avance 50%, cierre 20%, etc.).

**Archivos involucrados:**
- `app/Services/Finanzas/ProrrateadorPlurianual.php`
- Crear migración `create_proyecto_hitos_table` con `proyecto_id`, `nombre`, `fecha_planeada`, `porcentaje`, `cumplido_at`
- Crear `app/Models/ProyectoHito.php`
- UI para definir hitos por proyecto (en oportunidad show)

**Criterio de aceptación:**
- [ ] Tabla `proyecto_hitos` migrada.
- [ ] CRUD básico de hitos en oportunidad show (cuando estado ≥ adjudicado_firmado).
- [ ] Validación: suma de porcentajes = 100%.
- [ ] `ProrrateadorPlurianual::porcentajeEnPeriodo()` para método 'hitos': suma porcentajes de hitos `cumplido_at` dentro del período.
- [ ] Test del cierre gerencial con un proyecto en modo hitos.

---

## 11. Mailer real para reporte semanal (M7 ext / M10 ext)

**Por qué:** Hoy `ReporteSemanalController::enviar()` (línea ~87) marca el reporte como `enviado_at` pero NO manda el email real al cliente. Solo deja TODO en la respuesta. La vista [reportes/show.blade.php](resources/views/reportes/show.blade.php) (línea 40) lo confirma.

**Archivos involucrados:**
- `app/Http/Controllers/Ejecucion/ReporteSemanalController.php`
- Crear `app/Mail/ReporteSemanalMail.php` (Mailable)
- `resources/views/emails/reporte_semanal.blade.php` — plantilla con `$reporte->contenido_html` embedido y PDF adjunto

**Criterio de aceptación:**
- [ ] Crear `Mailable` que adjunte el PDF generado por `ReporteSemanalPdfGenerator`.
- [ ] El controller llama `Mail::to($recipients)->send(new ReporteSemanalMail($reporte))` antes de marcar `enviado_at`.
- [ ] `implements ShouldQueue` para no bloquear request.
- [ ] Test con `Mail::fake()` y `Mail::assertSent(ReporteSemanalMail::class)`.
- [ ] Validar que solo emails válidos reciben el correo (filtro ya existe en el controller).

---

## 12. Vista ejecutiva — métricas adicionales y filtros (M13.1)

**Por qué:** [resources/views/ejecutivo/index.blade.php](resources/views/ejecutivo/index.blade.php) (línea 96) tiene TODO M13.1 inline: agregar % dossier completo, % post-mortem, carga del equipo (cuántos proyectos por GP), filtros adicionales por sublínea / con-sin SEDENA / período custom.

**Archivos involucrados:**
- `app/Services/Ejecutivo/KpisEjecutivoService.php` — agregar nuevos KPIs
- `resources/views/ejecutivo/index.blade.php` — agregar filtros y cards
- `app/Services/Ejecutivo/ResumenEjecutivoExcelExporter.php` — incluir nuevos KPIs en hoja 1
- `app/Services/Ejecutivo/ReporteEjecutivoPdfGenerator.php` — idem

**Criterio de aceptación:**
- [ ] **% dossier completo del año**: promedio del `porcentaje_avance_global` del libro de proyectos en ejecución.
- [ ] **% post-mortem**: cuántos proyectos cerrados tienen post-mortem registrado / total cerrados.
- [ ] **Carga del equipo**: tabla `gerente | proyectos en pipeline | proyectos en ejecución` con barra de saturación.
- [ ] **Filtro por sublínea**: dropdown con las 5 sublíneas + "Todas".
- [ ] **Filtro con/sin SEDENA**: para excluir el cliente cuando se quiera ver pipeline civil.
- [ ] **Filtro periodo custom**: rango de fechas en lugar del año fijo.
- [ ] Tests Pest verifican que los filtros producen subconjuntos consistentes.

---

## 13. CRUD de System Settings desde UI (M13.2)

**Por qué:** Hay 5 settings críticos sembrados (`minuta_entrega_obligatoria`, `bloqueo_cierre_dossier_incompleto`, `bloqueo_cierre_post_mortem_pendiente`, `auth_dominios_corporativos`, `concentracion_cliente_alerta_umbral`) y otro pendiente (`pipeline_probabilidades` del item #9). Hoy se editan vía `php artisan tinker` o seeder. Los administradores necesitan UI.

**Archivos involucrados:**
- Crear `app/Http/Controllers/Admin/SettingsController.php`
- `app/Models/SystemSetting.php` (ya existe)
- Crear `resources/views/admin/settings/index.blade.php`
- Ruta protegida por `role:super_admin|direccion_general`

**Criterio de aceptación:**
- [ ] Listar settings agrupados por `group` (proyectos / libro_proyecto / auth / ejecutivo / cierres).
- [ ] Editor inline tipado: boolean → toggle, integer → number, array → tags input, string → text.
- [ ] Validación según `type`.
- [ ] Audit en `finanzas_audit` (o tabla separada `system_settings_audit`) para cambios sensibles.
- [ ] Setting con `locked=true` no puede editarse desde UI (forzar tinker).
- [ ] Tests de autorización + persistencia.

---

## 14. Importador masivo de cotizaciones desde Excel (M3 ext, low priority)

**Por qué:** Algunos clientes piden importar la BOM desde Excel en lugar de capturar partida por partida. PhpSpreadsheet ya está disponible.

**Criterio de aceptación:**
- [ ] Endpoint `POST /oportunidades/{p}/cotizaciones/{c}/importar-excel`.
- [ ] Plantilla de Excel descargable como referencia.
- [ ] Parser tolerante a header en distintas filas, columnas adicionales ignoradas.
- [ ] Reemplaza partidas existentes (con confirmación) o las agrega (default).
- [ ] Tests con fixture inline.

---

## Tareas menores (one-liners)

| TODO | Archivo | Esfuerzo |
|---|---|---|
| Avatar real en sidebar (hoy es inicial) | `resources/views/components/sidebar.blade.php` | 30min |
| Página `/perfil/mi-asignacion` con horas históricas | `app/Http/Controllers/Asignaciones/AsignacionesController::mia` | 2h |
| Carta finiquito con upload de PDF firmado escaneado | `CartaFiniquitoService` + view | 3h |
| Editor visual de cronograma drag-and-drop | `cronogramas/show.blade.php` | 1d |
| Dashboard inicial con widgets configurables | `DashboardController` + Livewire | 2d |
| Búsqueda global (Ctrl+K) | nueva | 4h |
| Modo oscuro | `resources/views/components/layouts/app.blade.php` | 4h |
| Two-factor auth para CFO/super_admin | `Spatie\TwoFactor` o similar | 1d |

---

## Convenciones para retomar el trabajo

1. **Antes de empezar cualquier tarea:**
   - `php artisan migrate:fresh --seed` para tener un estado limpio.
   - `./vendor/bin/pest` debe estar al 100% verde antes de tocar nada (excepto el test Texmelucan skipped).
2. **Mientras trabajas:**
   - Crea el test ANTES (TDD) o al menos en el mismo commit que el código.
   - Si tocas un service que ya existe, agrega un test en lugar de modificar uno existente.
3. **Antes de cerrar:**
   - `./vendor/bin/pint` (autofix).
   - `./vendor/bin/pest` (debe seguir verde).
   - `npm run build` (verificar que Vite compila).
   - `php artisan migrate:fresh --seed` (verificar que el seeder sigue funcionando end-to-end).
4. **Convenciones del repo:**
   - Decisiones D1-D13 del plan ejecutable son inmutables sin update del plan.
   - Servicios en `app/Services/{Modulo}/`, controllers en `app/Http/Controllers/{Modulo}/`.
   - Tests por flujo en `tests/Feature/{Modulo}/{Cosa}FlowTest.php`.
   - Eventos de proyecto siempre vía `$proyecto->recordEvent('tipo', $userId, ...)` (no `ProyectoEvento::create()` directo).
   - Audit financiero siempre vía `FinanzasAuditService::registrar(...)`.
