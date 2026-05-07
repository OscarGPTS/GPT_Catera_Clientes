# Screenshots de referencia

Esta carpeta debe contener capturas de los mockups que generamos visualmente en sesiones previas. Sirven a Claude Design como **ground truth visual** para que las pantallas que genere coincidan estilísticamente con lo que ya validaste.

## Cómo poblar esta carpeta

Vuelve al chat anterior donde generamos los mockups visuales y descarga estas 6 pantallas:

| Nombre sugerido del archivo | Mockup de referencia |
|-----------------------------|----------------------|
| `ref_01_login.png` | Login multi-proveedor (split layout naranja/blanco) |
| `ref_02_dashboard_ejecutivo.png` | Dashboard ejecutivo de socios con KPIs y heatmap SEDENA |
| `ref_03_ficha_proyecto_texmelucan.png` | Ficha del proyecto Texmelucan con todas sus secciones |
| `ref_04_cierre_gerencial_finanzas.png` | Cierre mensual SAT vs Gerencial con 3 secciones |
| `ref_05_chat_notificaciones.png` | Chat embebido + bandeja de notificaciones |
| `ref_06_reporte_asignacion_heatmap.png` | Reporte de asignación con heatmap por persona |

## Cómo capturar

1. Abre el chat anterior en Claude.
2. Haz scroll hasta el mockup que quieras.
3. Captura de pantalla (Cmd+Shift+4 en Mac, Win+Shift+S en Windows, o usa la herramienta de captura del navegador).
4. Recorta solo el área del mockup (sin la UI del chat).
5. Guarda con el nombre sugerido en esta carpeta.

## Por qué importa

Claude Design recibe estos screenshots junto con el `DESIGN_SYSTEM.md` y los prompts de cada pantalla. Cuando le pides una pantalla nueva, usa los screenshots como referencia para mantener consistencia visual: paleta naranja/rojo aplicada correctamente, density similar, jerarquía tipográfica, patrones de cards y tablas.

Sin estos screenshots, Claude Design solo tiene el design system escrito, lo cual funciona pero genera más variabilidad estilística entre pantallas.

## Si no tienes los screenshots

No es bloqueante. Puedes empezar con el design system + prompts, y el resultado será 80% consistente. Después agregas los screenshots e itera las pantallas que se vean fuera de línea.
