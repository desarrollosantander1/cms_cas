# API Gateway — Symfony 7.4

Gateway de enrutamiento/proxy hacia microservicios, construido con **Symfony 7.4** (LTS, requiere PHP ≥ 8.3).

## ¿Qué hace?

Recibe cualquier petición HTTP, la compara contra una tabla de rutas configurable (`config/packages/gateway.yaml`) y la reenvía al microservicio interno correspondiente, transmitiendo la respuesta de vuelta al cliente (streaming, sin bufferizar todo el cuerpo en memoria).

```
Cliente → /api/users/42 → Gateway → http://users-service/42
Cliente → /api/orders    → Gateway → http://orders-service/
```

## Instalación

```bash
# 1. Crear el proyecto (o usar esta carpeta ya generada)
composer install

# 2. Copiar y ajustar variables de entorno
cp .env .env.local
# Edita .env.local con las URLs reales de tus microservicios

# 3. Levantar el servidor de desarrollo
symfony server:start
# o, sin Symfony CLI:
php -S 127.0.0.1:8000 -t public
```

## Configurar las rutas

Edita `config/packages/gateway.yaml`:

```yaml
parameters:
    gateway.routes:
        - { prefix: '/api/users',    target: '%env(USERS_SERVICE_URL)%',    strip_prefix: true }
        - { prefix: '/api/orders',   target: '%env(ORDERS_SERVICE_URL)%',   strip_prefix: true }
```

- `prefix`: ruta pública que expone el gateway.
- `target`: URL base del microservicio interno (normalmente un `env var`).
- `strip_prefix: true`: elimina el prefijo antes de reenviar (`/api/users/42` → `/42`). Ponlo en `false` si el microservicio espera recibir el prefijo tal cual.

Las rutas se evalúan en orden: la primera coincidencia gana. No hace falta tocar código PHP para añadir un microservicio nuevo — solo esta tabla.

## Componentes clave

| Archivo | Responsabilidad |
|---|---|
| `src/Service/RouteResolver.php` | Decide a qué microservicio va cada path entrante |
| `src/Service/ProxyService.php` | Ejecuta la petición HTTP saliente y transmite la respuesta (streaming) |
| `src/Controller/GatewayController.php` | Punto de entrada único para todas las peticiones |
| `config/packages/gateway.yaml` | Tabla de enrutamiento |
| `config/routes.yaml` | Registra el catch-all `/{path}` |

## Comportamiento

- **Todos los métodos HTTP** (GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD).
- **Streaming real**: la respuesta del microservicio se transmite chunk a chunk, no se carga entera en memoria.
- **Cabeceras salto a salto** (`Connection`, `Host`, `Content-Length`, etc.) se filtran según RFC 7230; el resto se reenvían.
- **Cabeceras de proxy estándar** añadidas automáticamente: `X-Forwarded-For`, `X-Forwarded-Host`, `X-Forwarded-Proto`, `X-Request-Id`.
- **Timeout configurable** vía `GATEWAY_HTTP_TIMEOUT` (segundos).
- Si el microservicio no responde: el gateway devuelve `502 Bad Gateway` en JSON en lugar de caerse.
- `GET /health` responde `{"status":"ok"}` sin pasar por el proxy (útil para *health checks* de balanceadores/k8s).

## Probar con Docker Compose

Incluye un `docker-compose.yml` de ejemplo con tres instancias de `httpbin` simulando microservicios:

```bash
docker compose up --build
curl http://localhost:8080/api/users/get
curl http://localhost:8080/health
```

## Próximos pasos sugeridos

Este gateway cubre enrutamiento/proxy puro. Si más adelante necesitas:
- **Autenticación (JWT/OAuth2)** → añadir `lexik/jwt-authentication-bundle` o un `EventSubscriber` en `kernel.request` que valide el token antes de reenviar.
- **Rate limiting** → el paquete `symfony/rate-limiter` ya está en `composer.json`; solo falta configurarlo en `config/packages/rate_limiter.yaml` y aplicarlo en el controlador.
- **Logging/monitoreo avanzado** → ya está Monolog configurado; se puede añadir un `EventSubscriber` que mida latencia por microservicio y la exponga como métricas (Prometheus, etc.).

Dímelo si quieres que añada alguna de estas piezas.
