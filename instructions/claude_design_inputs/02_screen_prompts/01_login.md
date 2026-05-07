# Pantalla 01 — Login multi-proveedor

**Ruta:** `/login`
**Módulo:** M0/M1 Auth
**Hito:** H0
**Layout:** Full-screen sin sidebar/topbar

---

## Rol del usuario
Cualquier persona que necesita entrar al sistema: empleado interno (Auth0 corporativo), externo pre-registrado (email/password), externo con cuenta personal (Google/Microsoft/Apple).

## Objetivo
Autenticarse al sistema. Tres caminos diferentes según el tipo de usuario.

## Componentes principales

**Layout split 50/50:**
- **Izquierda (50%):** fondo `slate-900` con elementos de marca. Logo "GPT Services" grande arriba. Tagline "Plataforma integral de licitaciones, finanzas y operación". Footer con texto pequeño "Tech Energy Control S.A. de C.V." y certificaciones ISO (badges 9001, 14001, 45001).
- **Derecha (50%):** fondo blanco con formulario centrado, max-width 400px.

**Formulario:**
1. Título "Iniciar sesión" (text-2xl font-medium).
2. Subtítulo "Selecciona tu método de acceso" (text-sm slate-500).
3. **Botón principal Auth0** ancho completo, fondo `gpt-600` blanco: "Continuar con cuenta corporativa" + icono escudo. Subtitle bajo el botón: "Para empleados @gptservices.com y @satechenergy.com".
4. Divider con texto "o" centrado entre `border-slate-200`.
5. **Formulario email/password** colapsado por default, expandible con link "Acceso para usuarios externos pre-registrados".
6. Otra pequeña sección con 3 botones de iconos (Google, Microsoft, Apple) horizontales, cada uno cuadrado con su logo.
7. Footer con link "¿Problemas para acceder? Contacta soporte" → `mailto:soporte@gptservices.com`.

## Datos de ejemplo
N/A (pantalla previa al login).

## Estados
- **Default:** formulario completo visible, sin errores.
- **Email/password expandido:** muestra inputs de email + password + botón "Entrar".
- **Loading:** botón Auth0 con spinner, deshabilitado.
- **Error:** mensaje rojo bajo el botón fallido. Ej: "Cuenta no autorizada. Tu email no está registrado en el sistema."
- **Throttled:** "Demasiados intentos. Intenta en 15 minutos."

## Responsive
- **Mobile (<768px):** desaparece el panel izquierdo, formulario centrado a 100% con max-width 400px y padding lateral 16px. Logo GPT pequeño en la parte superior.
- **Tablet (768-1024px):** mantiene split pero panel izquierdo se reduce a 30%.
- **Desktop (≥1024px):** split 50/50 completo.

## Edge cases
- Si el usuario llega con `?error=unauthorized` (callback fallido de Auth0), mostrar el error visible en la parte superior del formulario.
- Si el usuario tiene una sesión activa, redirigir directamente a `/dashboard`.
- Si el dominio del email no está en `auth_dominios_corporativos`, el botón Auth0 muestra warning suave: "Este email no parece ser corporativo. ¿Quizás quieres usar otro método?".

## Referencias visuales
- Layout split estilo: claude.ai/login, Linear, Vercel.
- Tono: profesional industrial, NO playful.
- Mantener mucha respiración (whitespace) en panel izquierdo.

## Notas técnicas
- Form actions: `POST /auth/auth0/redirect`, `POST /auth/email/login`, `POST /auth/socialite/{provider}/redirect`.
- CSRF token Laravel debe estar presente.
- Inputs deben tener `autocomplete="email"` y `autocomplete="current-password"`.
