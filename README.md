# API Appsline

Backend de **Laravel 12** para la gestión de proyectos y tareas: API JSON con
autenticación por token, tablero de tareas con estados, comentarios, adjuntos,
captación de leads y métricas de dashboard. Incluye una **documentación OpenAPI/Swagger
interactiva** servida por el propio proyecto (sin paquetes extra).

[![OpenAPI](https://img.shields.io/badge/OpenAPI-3.0.3-dc127b)](openapi/openapi.yaml)
[![Swagger UI](https://img.shields.io/badge/docs-Swagger%20UI-85ea2d)](http://localhost:8000/docs)

---

## Índice

- [Requisitos](#requisitos)
- [Puesta en marcha](#puesta-en-marcha)
- [Documentación de la API (Swagger)](#documentación-de-la-api-swagger) ← *cómo correr la doc*
- [Autenticación de la API](#autenticación-de-la-api)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Desarrollo: tests y estilo](#desarrollo-tests-y-estilo)
- [Referencia de endpoints](docs/API.md) · [Especificación OpenAPI](openapi/openapi.yaml)

---

## Requisitos

| Herramienta | Versión | Notas |
|---|---|---|
| PHP | ≥ 8.2 | con `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` |
| Composer | 2.x | instala las **dev-dependencies** (hace falta `symfony/yaml` para regenerar la doc) |
| MySQL/MariaDB | 8.x / 10.4+ | `DB_DATABASE=api_appsline` en `.env` |
| Node.js | ≥ 20 | sólo para Vite (`npm run dev`); la documentación no lo necesita |

---

## Puesta en marcha

```bash
git clone git@github.com:gangelappsline/api_appsline.git
cd api_appsline

composer install                # incluye require-dev
cp .env.example .env
php artisan key:generate

# Ajusta en .env: DB_DATABASE / DB_USERNAME / DB_PASSWORD, APP_URL
php artisan migrate --seed

php artisan storage:link        # imprescindible para /storage (logos y adjuntos)
php artisan passport:keys         # genera storage/oauth-{private,public}.key
                                  # (firma los access tokens del guard `api`)

php artisan serve               # http://localhost:8000
```

Cola de trabajos (opcional, para el mail de asignación de tareas):

```bash
php artisan queue:work          # MAIL_MAILER=log escribe en storage/logs/laravel.log
```

Stack alternativo con **Laravel Sail**: `./vendor/bin/sail up` y `sail artisan migrate`.

---

## Documentación de la API (Swagger)

### Correrla

La documentación no es un servicio aparte: la sirve la propia app desde
`routes/web.php`. Con el servidor de arriba en marcha:

```bash
php artisan serve               # 1) arranca la app (no hay que compilar nada para la doc)
open http://localhost:8000/docs # 2) Swagger UI, interactiva
```

| URL | Qué es |
|---|---|
| `/docs` | **Swagger UI**: contrato + botón *Try it out* |
| `/docs/redoc` | ReDoc: la misma spec en formato lectura (una página) |
| `/docs/openapi.json` | Spec en JSON → la URL que se importa en Postman/Insomnia/codegen |
| `/docs/openapi.yaml` | Spec en YAML, tal cual el fichero fuente |

Si abres `/docs` y ves un 404, la doc está apagada para tu entorno: revisa `DOCS_ENABLED=true` y que
`DOCS_ENVIRONMENTS` incluya tu `APP_ENV` (ver [Configuración](#configuración)).

### Autorizar peticiones desde la UI

1. **Auth → POST /api/login → Try it out** con `{"email":"…","password":"…"}`.
2. Copia `data.token` de la respuesta.
3. Pulsa **Authorize** (arriba a la derecha) y pégalo **sin** el prefijo `Bearer`.
4. Listo: *Try it out* enviará `Authorization: Bearer …` en todos los endpoints.

El token se guarda en `localStorage` (`persistAuthorization`), así que sobrevive a recargas.
Las peticiones salen del navegador contra `servers[0]` (`http://localhost:8000/api`), por lo
que no hay CORS que configurar si la app está en ese origen.

### Editar la documentación

La **fuente de verdad es `openapi/openapi.yaml`** (YAML a mano: diffs legibles y comentarios).
El JSON `openapi/openapi.json` es un **artefacto generado y versionado** que se sirve cuando no
hay parser de YAML disponible (p. ej. producción con `--no-dev`).

```bash
# 1) edita el YAML ...
# 2) valida y regenera el export
php artisan docs:generate

# 3) commitea AMBOS ficheros
git add openapi/openapi.yaml openapi/openapi.json
git commit -m "docs(openapi): añade PATCH /projects/{id}/tasks/{task}"
```

En local **basta con recargar el navegador** tras editar el YAML: la clave de caché incluye el
hash del fichero (`docs.cache`), así que un cambio de contenido la invalida al instante, y en
`APP_DEBUG=true` la respuesta HTTP viaja con `Cache-Control: no-store`. El export JSON sólo es
obligatorio para que producción (`composer install --no-dev`, sin parser de YAML) vea los cambios.

`php artisan docs:generate` acepta:

| Opción | Para qué |
|---|---|
| `--check` | Valida **sin escribir** y sale con código `1` si hay problemas o el export está desactualizado. Pensado para CI. |
| `--coverage` | Tabla con **todas las rutas reales** de `routes/api.php` y marca las no documentadas (y los paths de la spec sin ruta). |
| `--public` | Copia JSON y YAML a `public/docs/` para servirlos como fichero estático (menos parseo, caché/CDN). |
| `--force` | Escribe aunque haya problemas estructurales. |
| `--output=path` | Volcado adicional del JSON (p. ej. `--output=../frontend/src/api/openapi.json`). |

Qué comprueba la validación estructural (`problems()` en `App\Support\OpenApi`): versión
OpenAPI, `info.title/version`, `servers`, operaciones sin `responses` o sin `operationId`,
`operationId` duplicados, tags no declarados, `$ref` sin resolver y `securitySchemes` ausentes.

Ejemplos:

```bash
php artisan docs:generate --coverage      # ¿se quedó la doc atrás respecto a las rutas?
php artisan docs:generate --check         # puerta de CI
php artisan docs:generate --public        # artefactos en public/docs (gitignored)
curl -s localhost:8000/docs/openapi.json | jq '.paths | keys | length'
```

### Configuración

Fichero: [`config/docs.php`](config/docs.php). Variables de entorno disponibles:

| Variable | Por defecto | Efecto |
|---|---|---|
| `DOCS_ENABLED` | `true` | `false` ⇒ todas las rutas `/docs*` responden 404 |
| `DOCS_ENVIRONMENTS` | `local,development,testing,staging` | lista blanca de `APP_ENV`; **producción queda fuera** |
| `DOCS_SPEC_PATH` | `openapi/openapi.yaml` | fichero fuente |
| `DOCS_PREFER_EXPORT` | `false` | `true` ⇒ sirve siempre `openapi/openapi.json` (simula producción) |
| `DOCS_CACHE` | `true` | cachea el parseo (clave = hash del YAML) |
| `DOCS_CACHE_TTL` | `3600` | segundos; `0` desactiva la caché |
| `DOCS_UI_ASSETS` | `cdn` | `cdn` usa jsDelivr; `local` usa `public/vendor/swagger-ui` |
| `DOCS_UI_EXPANSION` | `list` | `none` \| `list` \| `full` al abrir la UI |
| `DOCS_TITLE` | `Appsline API · Documentación` | `<title>` y cabecera de la UI |
| `DOCS_FAIL_ON_PROBLEMS` | `false` | `true` ⇒ `/docs` lanza excepción si la spec tiene problemas |

Para publicar la doc en producción de forma explícita:

```dotenv
# .env de producción
DOCS_ENABLED=true
DOCS_ENVIRONMENTS=production
```
```bash
php artisan docs:generate --public   # y servir public/docs/openapi.json
```

### Modo offline (`DOCS_UI_ASSETS=local`)

Por defecto la UI carga sus assets desde jsDelivr, así que el repo no arrastra ficheros de
terceros. En un entorno sin salida a internet, cópialos una vez a `public/vendor/swagger-ui`
(ignorados por git):

```bash
npm run docs:vendor            # instala swagger-ui-dist y copia los assets a public/vendor
DOCS_UI_ASSETS=local php artisan serve
```

Equivalente a:

```bash
npm i --no-save swagger-ui-dist
mkdir -p public/vendor/swagger-ui
cp node_modules/swagger-ui-dist/swagger-ui.css \
   node_modules/swagger-ui-dist/swagger-ui-bundle.js \
   node_modules/swagger-ui-dist/swagger-ui-standalone-preset.js \
   public/vendor/swagger-ui/
```

> ReDoc en modo `local` necesita además `redoc.standalone.js` en ese mismo directorio; si no lo
> copias, usa sólo `/docs` (Swagger UI), que es la documentación de referencia del proyecto.

### Integraciones

```bash
# Postman / Insomnia: importa la URL de la spec
curl -s localhost:8000/docs/openapi.json -o openapi.json

# Cliente TypeScript a partir de la spec
npx --yes openapi-typescript openapi/openapi.json -o frontend/src/api/schema.d.ts
```

### Cómo está montada (y por qué sin dependencias)

| Fichero | Rol |
|---|---|
| `openapi/openapi.yaml` | fuente de verdad del contrato |
| `openapi/openapi.json` | export generado (`x-source-sha1` permite detectar si está obsoleto) |
| `config/docs.php` | rutas, entornos permitidos, caché, assets, export |
| `app/Support/OpenApi.php` | carga (YAML → JSON), normaliza, valida, estima cobertura, escribe el export |
| `app/Http/Controllers/DocsController.php` | `/docs`, `/docs/redoc`, `/docs/openapi.json`, `/docs/openapi.yaml` |
| `app/Console/Commands/DocsGenerate.php` | `php artisan docs:generate` |
| `resources/views/docs/swagger.blade.php` · `redoc.blade.php` | las dos UIs |
| `docs/API.md` | inventario completo de rutas (incluidas las no implementadas) y curl de ejemplo |

No se añadió ningún paquete de Composer: la spec se mantiene a mano y el único parser usado es
`symfony/yaml`, que ya llega como dependencia de desarrollo de Laravel. En `--no-dev` el controlador
sirve directamente el JSON exportado, que no necesita parser.

---

## Autenticación de la API

- Guard `api` en `config/auth.php` ⇒ **`driver: passport`**, middleware `auth:api` en el grupo
  protegido de `routes/api.php`.
- `POST /api/login` devuelve `data.token` (access token de Passport) y `data.user`.
- `POST /api/register` devuelve `token` usando la API de **Sanctum**
  (`createToken()->plainTextToken`): ese token **no** autentica contra el guard `api`.
  Para registrar + usar la API hoy: registra con el formulario web (`/register`) o con
  `User::create(...)` en tinker, y entra por `POST /api/login`.
- `POST /api/logout` elimina el token empleado en la llamada
  (`$request->user()->currentAccessToken()->delete()`).

```bash
php artisan passport:client --password   # si automatizas el login por grant_type=password
```

Detalle de endpoints, códigos de estado y reglas de negocio: [docs/API.md](docs/API.md).

---

## Estructura del proyecto

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── API/                 # AuthController, Project, ProjectTask(+Comments/Files),
│   │   │   └── Public/          #   Dashboard, User, Post + variantes públicas
│   │   ├── Auth/                # scaffolding de laravel/ui (login/registro web)
│   │   ├── Controller.php
│   │   └── DocsController.php   # <- /docs
│   ├── Resources/               # ProjectResource, ProjectTaskResource, UserResource…
│   └── Middleware/
├── Livewire/                    # Dashboard, ProjectBoard, TaskDetail (front del panel)
├── Mail/TaskAssigned.php
├── Models/                      # User, Project, ProjectTask, ProjectTaskComment,
│                                # ProjectTaskFile, Lead, Post
├── Policies/PostPolicy.php
├── Support/OpenApi.php          # <- carga/validación/export de la spec
└── Traits/ApiResponse.php

bootstrap/app.php                # withRouting: web + api + commands + /up
config/docs.php                  # <- configuración de la documentación
database/{migrations,seeders,factories}
docs/API.md                      # <- referencia de endpoints y curl
openapi/openapi.yaml|.json       # <- contrato de la API
resources/views/
├── docs/{swagger,redoc}.blade.php
├── layouts/app.blade.php        # Bootstrap 5 + Livewire
└── livewire/…
routes/{api,web,console}.php
tests/                           # Pest
```

Flujo de una petición autenticada:

```
POST /api/login ──► AuthController@login ──► Passport access token
GET  /api/projects/{id}/tasks
  └─ middleware auth:api ─► ProjectTaskController@index
        └─ ProjectTaskResource::collection ─► sendResponse()
              { "success": true, "data": { "tasks": { "data": [ … ] } }, "message": "Tasks" }
```

---

## Desarrollo: tests y estilo

```bash
composer test                       # = php artisan config:clear && php artisan test
./vendor/bin/pest --filter=Example   # Pest 3 (tests/Feature, tests/Unit)
./vendor/bin/pint                    # formato PSR-12
./vendor/bin/pint --test             # comprobación en CI
php artisan route:list --path=api    # inventario de rutas
php artisan docs:generate --check    # la doc no se queda atrás
```

`phpunit.xml` usa `DB_CONNECTION=sqlite` + `:memory:`, así que los tests no tocan tu MySQL.

Entorno con Sail:

```bash
cp .env.example .env && sail up -d
sail artisan key:generate && sail artisan migrate
sail npm install && sail npm run dev
```

---

## Notas conocidas

La doc refleja el **comportamiento real** del código, incluidos los endpoints declarados en
`routes/api.php` cuyo método de controlador está vacío y algún fallo conocido (mass assignment en
`Lead`, `route model binding` roto en el `DELETE` de tareas, columnas usadas por los resources que
no aparecen en las migraciones, dualidad Passport/Sanctum). El detalle, con referencias al código,
está en [docs/API.md § Notas y deriva detectada](docs/API.md).

---

## Licencia

MIT — ver [LICENSE](LICENSE) del framework Laravel.
