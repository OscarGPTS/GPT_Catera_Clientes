# Pantalla 20 — Chat embebido + Bandeja de notificaciones

**Ruta:** `/chat` (embebido en topbar) + `/notificaciones`
**Módulo:** M10 Comunicación
**Hito:** H11
**Layout:** Sidebar + topbar + main, panel deslizable desde derecha

---

## Rol del usuario
Todos los usuarios del sistema. Reemplazo del WhatsApp operativo (decisión D3). Triple canal: email + panel notificaciones in-app + chat anclado a proyecto/departamento/dirección.

## Objetivo
Comunicación operativa anclada al proyecto donde pertenece, con búsqueda histórica, menciones, threading, adjuntos.

## Componentes principales

### Componente A — Bandeja de notificaciones (topbar dropdown)
Trigger: clic en icono campanita del topbar.

Panel desplegable 380px:
- Header: "Notificaciones" + contador "12 nuevas" + botón "Marcar todas como leídas".
- Tabs: "Todas (28)" | "Sin leer (12)" | "@Menciones (3)".
- Lista de notificaciones con:
  - Icono de tipo (cotización, viáticos, minuta, dossier, etc.).
  - Título corto.
  - Subtítulo (de qué proyecto, quién originó).
  - Tiempo relativo.
  - Dot azul si no leída.
- Footer: link "Ver todas en /notificaciones".

### Componente B — Página completa de notificaciones (`/notificaciones`)
Vista expandida con filtros:
- Por tipo (cotización / viáticos / minuta / dossier / etc.).
- Por proyecto.
- Por estado (leída / sin leer).
- Por fecha.

Lista cronológica con cards más grandes (preview del contenido) y acciones inline.

### Componente C — Chat panel (drawer derecho desde topbar)
Trigger: clic en icono burbuja de chat.

Drawer 480px desde derecha:
- **Sidebar interno (izquierda 160px):**
  - Search "Buscar en chats..."
  - Sección "Tus canales":
    - Proyecto DN-018/26 (5 sin leer) — badge.
    - Departamento Proyectos (2 sin leer).
    - Dirección General.
    - Privados con personas específicas.
- **Vista de mensajes (derecha 320px):**
  - Header del canal: nombre + miembros (avatares) + botón configuración.
  - Mensajes con scroll infinito hacia arriba para historial.
  - Cada mensaje:
    - Avatar + nombre + timestamp.
    - Contenido (texto, formato Markdown ligero, menciones @user resaltadas).
    - Adjuntos como cards inline.
    - Threading: replies indented bajo el mensaje original.
    - Acciones hover: reply, react, edit (si tuyo), delete (si tuyo o admin).
  - Indicadores: "Sergio está escribiendo...", "Visto por 3 personas".
- **Input de mensaje (footer):**
  - Textarea con autoresize.
  - Botón paperclip (adjuntos).
  - Mención @user con autocomplete.
  - Atajo / para emojis.
  - Botón enviar (gpt-600).

## Datos de ejemplo
**Notificaciones recientes:**
- "Cotización CP-024/26 lista para revisión" · Erick Morales · hace 30min · sin leer.
- "Sergio te mencionó en chat de DN-018/26" · hace 1h · sin leer.
- "Viáticos VTC-2025-018 aprobados por Dirección" · hace 2h · leída.
- "Dossier DN-018/26 al 92% — falta sección G" · hace 3h · sin leer.

**Chat DN-018/26 últimos mensajes:**
- [Sergio Ordaz · 14:32] "Llegamos a Texmelucan, todo OK. Mañana iniciamos preparación."
- [Sergio Ordaz · 14:33] [adjunto: foto-sitio.jpg]
- [Fernando Basave · 14:45] "Perfecto Sergio. @JesusBecerra confirmar que el T-1200 está calibrado."
- [Jesús Becerra · 14:47] "Confirmado, calibración hecha ayer."
- [Roberto Trainee · 16:10] "¿Subimos la bitácora con las fotos del día?"

## Estados
- **Sin notificaciones:** ilustración + "Estás al día".
- **Sin canales:** "Aún no tienes canales activos. Se crean automáticamente al unirte a proyectos."
- **Mensaje no enviado (offline):** badge "Pendiente de enviar" en el mensaje + retry.
- **Cargando historial:** skeleton de 5 mensajes.

## Responsive
- **Mobile:** notificaciones y chat ocupan 100% de pantalla (modal full-screen).
- **Tablet:** drawer 50% del ancho.
- **Desktop:** drawer 480px.

## Edge cases
- Auto-creación de canales: cuando se crea un proyecto, se crea canal `proyecto-{cp}` con todos los asignados como miembros.
- Notificación push si la PWA está instalada.
- Búsqueda histórica con highlight de matches.
- Si un mensaje contiene mención `@usuario`, ese usuario recibe notificación adicional.

## Referencias visuales
- Bandeja notificaciones: estilo Linear inbox o GitHub notifications.
- Chat: estilo Slack thread o Discord channel pero más compacto.

## Notas técnicas
- Real-time con Laravel Reverb (websockets nativos).
- Persistence en `chat_mensajes` con `parent_message_id` para threading.
- `chat_lecturas` tracking para badges de no leídas.
- Search con LIKE inicial, índice fulltext para v2.
