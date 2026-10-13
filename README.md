# API Appsline

Backend de **Laravel 12** para la gestión de proyectos y tareas, con una API JSON
autenticada por token, comentarios, archivos, captación de leads y métricas.
La referencia OpenAPI se genera automáticamente con
[Scramble](https://github.com/dedoc/scramble) desde las rutas y el código de la API.

[![API docs](https://img.shields.io/badge/API%20docs-Scramble-6e56cf)](http://localhost:8000/docs)

## Requisitos

| Herramienta | Versión | Notas |
|---|---|---|
| PHP | ≥ 8.2 | Extensiones `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` |
| Composer | 2.x | Instala Scramble junto con las dependencias de Laravel |
| MySQL/MariaDB | 8.x / 10.4+ | Configura la conexión en `.env` |
| Node.js | ≥ 20 | Sólo para compilar los assets del panel |

## Puesta en marcha

```bash
git clone git@github.com:gangelappsline/api_appsline.git
cd api_appsline
composer install
cp .env.example .env
php artisan key:generate

# Configura DB_DATABASE / DB_USERNAME / DB_PASSWORD y APP_URL en .env
php artisan migrate --seed
php artisan storage:link
php artisan passport:keys
php artisan serve
```

La aplicación queda disponible en `http://localhost:8000`. Para los correos de
asignación de tareas, inicia también la cola con `php artisan queue:work`.

## Documentación de la API

Con la aplicación en marcha, abre <http://localhost:8000/docs>. Scramble genera la
referencia y la especificación OpenAPI al vuelo a partir de las rutas de
[`routes/api.php`](routes/api.php), validaciones, controladores y recursos; no se
mantiene un YAML/JSON de contrato separado.

| URL | Uso |
|---|---|
| `/docs` | Referencia interactiva, incluyendo *Try it out* |
| `/docs/openapi.json` | Especificación OpenAPI para Postman, Insomnia o generación de clientes |

También puedes exportar un JSON con `php artisan scramble:export --path=storage/app/openapi.json`
y analizar el documento con `php artisan scramble:analyze`.

El documento cubre las rutas bajo `/api`. La base `/api` se configura en
[`config/scramble.php`](config/scramble.php), y las rutas protegidas por `auth:api`
se describen con autenticación HTTP Bearer. Las rutas públicas permanecen sin
autenticación en el esquema.

Para probar operaciones protegidas desde la referencia, ejecuta `POST /api/login`,
copia `data.token` de la respuesta y úsalo en la opción **Authorize**. La API espera
un access token de Passport. Ten en cuenta que `POST /api/register` actualmente
crea un token Sanctum, que no autentica contra el guard `auth:api`.

El acceso a la documentación se controla con `DOCS_ENABLED` y `DOCS_ENVIRONMENTS`
en `.env`; por defecto se habilita en `local`, `development`, `testing` y `staging`,
pero no en producción. La misma regla protege la UI y el JSON OpenAPI. Si cambias
variables de entorno con la configuración cacheada, ejecuta
`php artisan config:clear`.
Para habilitarla en producción, configura explícitamente `DOCS_ENVIRONMENTS=production`
o `DOCS_ENVIRONMENTS=*` (cualquier entorno).

> Scramble publica por defecto en `/docs/api` y `/docs/api.json`. Este proyecto los
> remapea a `/docs` y `/docs/openapi.json` desde
> [`app/Providers/AppServiceProvider.php`](app/Providers/AppServiceProvider.php),
> así que esas son las únicas URLs válidas aquí.

La especificación se actualiza al modificar las rutas, reglas de validación,
controladores o recursos. Usa descripciones PHPDoc en los controladores para
explicar el propósito y comportamiento que no se puede inferir del código. Las
notas de comportamiento y limitaciones actuales están en [docs/API.md](docs/API.md).

### Si `/docs` "no da resultados"

Ejecuta primero el diagnóstico, que revisa paquete, rutas, permisos, cachés y genera
el documento de verdad:

```bash
php artisan docs:check            # informe con el comando que arregla cada fallo
php artisan docs:check --json     # para CI
php artisan docs:check --skip-generation
```

| Síntoma | Causa habitual | Arreglo |
|---|---|---|
| 404 en `/docs` y `/docs/openapi.json` | `DOCS_ENABLED=false`, o `APP_ENV` fuera de `DOCS_ENVIRONMENTS` (p. ej. `production`) | Ajusta `.env` y `php artisan config:clear`. El motivo exacto queda en `storage/logs/laravel.log` y, con `APP_DEBUG=true`, también en el cuerpo de la respuesta 404 |
| 404 aunque `.env` esté bien | `bootstrap/cache/config.php` cacheado antes de añadir `config/docs.php` | `php artisan optimize:clear` (o `config:clear`) |
| 404 en `/docs/api` | Es la URL por defecto de Scramble; aquí está remapeada | Usa `/docs` |
| Página en blanco (HTML llega, no se pinta) | La UI carga Stoplight Elements desde `unpkg.com`; sin salida a internet no hay JS/CSS | Da salida al CDN o publica la vista `scramble::docs` y sirve los assets en local |
| `php artisan optimize` o `route:cache` revienta con *Another route has already been assigned name* | Dos rutas con el mismo nombre (antes pasaba con `/api/projects` y `/api/public/projects`) | Ya corregido con `->names('public.projects')` y `->names('public.leads')`; si añades recursos, dales nombres únicos |
| 500 al abrir `/docs` | Scramble relanza cualquier error de análisis de una ruta concreta | Con `APP_DEBUG=true` la respuesta y el log indican la ruta; localízala con `php artisan scramble:analyze` |
| El documento existe pero está vacío | `scramble.api_path` no coincide con el prefijo real de `routes/api.php` | Revisa `api_path` en `config/scramble.php` |
| El documento no refleja los últimos cambios | Documento cacheado con `scramble:cache` | `php artisan scramble:clear` |

## Autenticación de la API

- El guard `api` de [`config/auth.php`](config/auth.php) usa Passport; las rutas
  protegidas están agrupadas con `auth:api`.
- `POST /api/login` devuelve `data.token` (Passport) y `data.user`.
- `POST /api/logout` revoca el token de la petición.
- `POST /api/register` usa actualmente Sanctum para crear su token; ese token no
  funciona con el guard Passport de la API.

## Correos de tareas

La API envía correos automáticos cuando se asigna una tarea y recordatorios
programados (pendientes, vencimientos del día, vencidas y reporte semanal del
viernes). Puesta en marcha (cron, colas, `.env`) y comandos en
[docs/EMAIL_NOTIFICATIONS.md](docs/EMAIL_NOTIFICATIONS.md); probar con
`php artisan tasks:notify-due-today --dry-run`.

## Desarrollo

```bash
composer test
./vendor/bin/pint --test
php artisan route:list --path=api
php artisan docs:check
php artisan scramble:analyze
```

Los tests usan SQLite en memoria (`phpunit.xml`), así que no requieren la base de
datos MySQL local.

## Notas conocidas

La referencia de Scramble refleja las rutas declaradas; algunas operaciones pueden
no estar implementadas o tener problemas de negocio. Las observaciones verificadas
se mantienen en [docs/API.md](docs/API.md), incluidas la diferencia de tokens entre
Passport y Sanctum y ciertas rutas de recursos incompletas.

## Licencia

MIT — ver [LICENSE](LICENSE).
