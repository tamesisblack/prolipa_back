# Documentación WebSockets - Sistema de Chat

## Descripción General
El sistema implementa un chat en tiempo real utilizando WebSockets a través de Laravel WebSockets y el driver Pusher (autoalojado, no Pusher.com). Los mensajes son procesados por un servicio de IA (Google Gemini) antes de ser transmitidos.

## Estructura y Archivos Clave
- **Evento:** `App\Events\MessageSent`
- **Controlador:** `App\Http\Controllers\AiMessageController`
- **Servicio:** `App\Services\AiService`
- **Rutas:** Definidas en `routes/api.php`
- **Configuración WebSocket/Canales:** `config/websockets.php`, `config/broadcasting.php`, `routes/channels.php`
- **Variables de entorno:** `.env`

## Flujo de Funcionamiento
1. **Envío de mensaje**
   - Endpoint: `POST /api/send-message`
   - Body: `{ "messages": [ { "role": "...", "content": "..." }, ... ], "chatId": "..." }`
   - El controlador `AiMessageController` recibe la petición y dispara el evento `MessageSent`.
2. **Procesamiento de mensaje**
   - El evento `MessageSent` utiliza `AiService` para enviar el mensaje a la API de Gemini y obtiene la respuesta.
   - La respuesta de la IA se transmite al canal `chat.{chatId}`.
3. **Broadcasting**
   - Utiliza el sistema de broadcasting de Laravel con el driver Pusher, configurado para WebSockets locales.

## Variables y Configuración Importante

### config/websockets.php
- `dashboard.port`: Puerto del dashboard de WebSockets (por defecto 6001).
- `apps`: Lista de aplicaciones permitidas para conectarse al servidor WebSocket. Cada app requiere:
  - `id`, `name`, `key`, `secret`, `path`, `capacity`, `enable_client_messages`, `enable_statistics`.

### config/broadcasting.php
- `default`: Debe ser `pusher` para usar WebSockets.
- `connections.pusher`: Configuración del driver Pusher:
  - `key`, `secret`, `app_id`, `options` (`host`, `port`, `scheme`, `useTLS`).

### routes/channels.php
Define la autorización para los canales. Ejemplo para chat:
```php
Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    // Implementa tu lógica de autorización
    return true;
});
```

## Variables de Entorno Relevantes
- `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`: Credenciales de la app WebSocket.
- `PUSHER_HOST`, `PUSHER_PORT`, `PUSHER_SCHEME`: Configuración de red del servidor WebSocket.
- `LARAVEL_WEBSOCKETS_PORT`, `LARAVEL_WEBSOCKETS_HOST`: Puerto y host del servidor WebSocket.
- `BROADCAST_DRIVER`: Debe ser `pusher`.
- `AI_API_KEY`: API key de Google Gemini.

### Ejemplo de configuración local
```env
PUSHER_APP_ID=local
PUSHER_APP_KEY=local
PUSHER_APP_SECRET=local
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
LARAVEL_WEBSOCKETS_PORT=6001
LARAVEL_WEBSOCKETS_HOST=127.0.0.1
BROADCAST_DRIVER=pusher
AI_API_KEY=tu_clave_api_de_google_gemini
```

## Comandos de Ejecución del WebSocket

### Desarrollo Local
```bash
php artisan websockets:serve
```
Esto inicia el servidor WebSocket en el puerto configurado (por defecto 6001).

### Producción con Supervisor
Ejemplo de configuración para Supervisor:
```
[program:websockets]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/a/tu/proyecto/artisan websockets:serve
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/ruta/a/tu/proyecto/storage/logs/websockets.log
```

## Endpoints Principales
- **POST /api/send-message**
  - `messages`: Array de objetos `{role, content}`
  - `chatId`: Identificador del chat

## Resumen de Seguridad
- No usar credenciales "local" en producción.
- Usar HTTPS/WSS en producción.

___
