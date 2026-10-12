# API Appsline · Notas de endpoints

Notas complementarias sobre rutas, reglas de negocio y problemas conocidos. La referencia OpenAPI
actualizada desde las rutas y controladores está en <http://localhost:8000/docs>.

- **Base URL local:** `http://localhost:8000/api`
- **Formato:** JSON en request y response, salvo adjuntos (`multipart/form-data`).
- **Autenticación:** cabecera `Authorization: Bearer {token}` en todo lo que no sea `/api/public/*`.
- **Envolvente:** `{ "success": bool, "data": mixed, "message": string }` en los controladores que
  heredan de `API\BaseController`.

> **Iconos de estado** — ✔ implementado · ⚠ funciona con una trampa conocida · ✖ ruta declarada
> cuyo método del controlador está vacío (responde 200 con cuerpo vacío o revienta con 500).

---

## 1. Inventario de rutas (`routes/api.php`)

### Auth y raíz

| Método | URI | Controlador | Estado |
|---|---|---|---|
| GET | `/api` | closure de bienvenida | ✔ |
| POST | `/api/register` | `API\AuthController@register` | ✔ devuelve `token` **estilo Sanctum** (ver §6) |
| POST | `/api/login` | `API\AuthController@login` | ✔ devuelve `data.token` de Passport |
| POST | `/api/logout` | `API\AuthController@logout` | ✔ revoca el token activo |

### Público (`/api/public`, sin token)

| Método | URI | Controlador | Estado |
|---|---|---|---|
| GET | `/api/public/projects` | `Public\ProjectController@index` | ✔ |
| POST · GET · PUT/PATCH · DELETE | `/api/public/projects`, `/api/public/projects/{project}` | `Public\ProjectController` | ✖ |
| POST | `/api/public/leads` | `Public\LeadController@store` | ⚠ `Lead` no declara `$fillable` → 500 |
| GET · PUT/PATCH · DELETE | `/api/public/leads`, `/api/public/leads/{lead}` | `Public\LeadController` | ✖ |

### Proyectos

| Método | URI | Controlador | Estado |
|---|---|---|---|
| GET | `/api/projects` | `ProjectController@index` | ✔ |
| POST | `/api/projects` | `ProjectController@store` | ✔ |
| GET | `/api/projects/{id}` | `ProjectController@show` | ✔ |
| PUT · PATCH | `/api/projects/{id}` | `ProjectController@update` | ✔ (sólo `name` y `description`) |
| DELETE | `/api/projects/{id}` | `ProjectController@destroy` | ⚠ pasa por `ProjectPolicy`, que no existe → 403 probable |
| POST | `/api/projects/{id}/logo` | closure en `routes/api.php` | ✔ |

### Tareas (`/api/projects/{project_id}/tasks`)

| Método | URI | Controlador | Estado |
|---|---|---|---|
| GET | `/api/projects/{project_id}/tasks` | `ProjectTaskController@index` | ✔ `data.tasks.data[]` |
| POST | `/api/projects/{project_id}/tasks` | `ProjectTaskController@store` | ✔ envía mail si hay `assigned_to` |
| GET | `/api/projects/{project_id}/tasks/{task}` | `ProjectTaskController@show` | ✔ |
| PUT · PATCH | `/api/projects/{project_id}/tasks/{task}` | `ProjectTaskController@update` | ✔ recalcula `actual_hours`/`completed_at` al completar |
| DELETE | `/api/projects/{project_id}/tasks/{task}` | `ProjectTaskController@destroy` | ✖ firma `destroy(ProjectTask $projectTask)` pero el parámetro de ruta es `{task}` |

### Comentarios de tarea

| Método | URI | Controlador | Estado |
|---|---|---|---|
| GET · POST | `/api/projects/{project_id}/tasks/{task_id}/comments` | `ProjectTaskCommentController` | ✔ respuesta **sin** `success` |
| GET · PUT · PATCH · DELETE | `…/comments/{comment}` | `ProjectTaskCommentController` | ✔ |

### Adjuntos de tarea

| Método | URI | Controlador | Estado |
|---|---|---|---|
| GET · POST | `/api/projects/{project_id}/tasks/{task_id}/files` | `ProjectTaskFileController` | ✔ |
| GET | `…/files/{file}` | `ProjectTaskFileController@show` | ✔ |
| PUT · PATCH | `…/files/{file}` | — | ✖ no existe `update()` |
| DELETE | `…/files/{file}` | `ProjectTaskFileController@destroy` | ✔ borra archivo en disco + registro |

### Dashboard

| Método | URI | Controlador | Estado |
|---|---|---|---|
| GET | `/api/dashboard?project_id=1` | `DashboardController@index` | ✔ cacheado 60 s |
| POST · PUT/PATCH · DELETE | `/api/dashboard`, `/api/dashboard/{dashboard}` | — | ✖ `apiResource` las declara, el controlador no las tiene |

`DashboardController` define además `tasksCompletedByDay()` y `avgCompletionByProject()`,
**sin ruta asignada**. Para usarlas:

```php
// routes/api.php, dentro del grupo auth:api
Route::get('/dashboard/tasks-by-day', [DashboardController::class, 'tasksCompletedByDay']);
Route::get('/dashboard/avg-by-project', [DashboardController::class, 'avgCompletionByProject']);
```

### Usuarios y posts

| Método | URI | Controlador | Estado |
|---|---|---|---|
| GET | `/api/users` | `UserController@index` | ✔ |
| POST | `/api/users` | `UserController@store` | ⚠ valida `photo` pero lee `avatar` |
| GET · PUT · PATCH · DELETE | `/api/users/{user}` | `UserController` | ✔ |
| GET | `/api/posts` | `PostController@index` | ✔ sólo los del usuario autenticado |
| POST | `/api/posts` | `PostController@store` | ✔ responde 201 sin envolvente |
| GET | `/api/posts/{id}` | `PostController@show` | ⚠ `find()`: 200 con `data: null` si no existe |
| PUT · PATCH · DELETE | `/api/posts/{id}` | `PostController@update` / `@destroy` | ✖ |

> `Route::apiResource('/users', …)` está declarado **dos veces** en `routes/api.php`
> (líneas 21 y 55). Es inocuo —la segunda declaración sobrescribe los nombres de ruta—
> pero genera ruido en `php artisan route:list`.

---

## 2. Autenticación paso a paso

```bash
BASE=http://localhost:8000/api

# 1) registrar (el token que devuelve NO sirve para el guard `api`: ver §6)
curl -s -X POST "$BASE/register" -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"name":"Ada Lovelace","email":"ada@example.com","password":"s3cret0pass"}'

# 2) login -> data.token
TOKEN=$(curl -s -X POST "$BASE/login" -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"email":"ada@example.com","password":"s3cret0pass"}' | jq -r .data.token)

# 3) comprobar
curl -s "$BASE/projects" -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'

# 4) cerrar sesión (revoca el token usado)
curl -s -X POST "$BASE/logout" -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json'
```

En la referencia de Scramble, autoriza las peticiones con el token `data.token` de Passport.

---

## 3. Flujo completo: proyecto → tarea → comentario → adjunto

```bash
BASE=http://localhost:8000/api
H=(-H "Authorization: Bearer $TOKEN" -H 'Accept: application/json')

# Proyecto
PROJECT=$(curl -s -X POST "$BASE/projects" "${H[@]}" -H 'Content-Type: application/json' \
  -d '{"name":"Portal corporativo","description":"Rediseño","budget":12000.50,"client_name":"ACME"}' \
  | jq -r .data.id)

# Logo (multipart, ≤ 2 MB)
curl -s -X POST "$BASE/projects/$PROJECT/logo" "${H[@]}" -F logo=@logo.png

# Tareas del tablero
curl -s -X POST "$BASE/projects/$PROJECT/tasks" "${H[@]}" -H 'Content-Type: application/json' \
  -d '{"title":"Maquetar home","status":"pending","priority":"high","due_date":"2026-11-30","assigned_to":3}'

TASK=$(curl -s "$BASE/projects/$PROJECT/tasks" "${H[@]}" | jq -r '.data.tasks.data[0].id')

# Mover a la columna "in_progress" y luego cerrar (el servidor fija actual_hours y completed_at)
curl -s -X PATCH "$BASE/projects/$PROJECT/tasks/$TASK" "${H[@]}" -H 'Content-Type: application/json' \
  -d '{"status":"in_progress"}'
curl -s -X PATCH "$BASE/projects/$PROJECT/tasks/$TASK" "${H[@]}" -H 'Content-Type: application/json' \
  -d '{"status":"completed"}' | jq '.data | {status, actual_hours, completed_at}'

# Conversación
curl -s -X POST "$BASE/projects/$PROJECT/tasks/$TASK/comments" "${H[@]}" \
  -H 'Content-Type: application/json' -d '{"comment":"Subido a staging, falta QA."}'
curl -s "$BASE/projects/$PROJECT/tasks/$TASK/comments" "${H[@]}" | jq '.data[0]'

# Adjuntos (varios a la vez, ≤ 20 MB cada uno)
curl -s -X POST "$BASE/projects/$PROJECT/tasks/$TASK/files" "${H[@]}" \
  -F "files[]=@boceto.png" -F "files[]=@memoria.pdf" | jq '.data[] | {file_name, url}'

# Dashboard
curl -s "$BASE/dashboard?project_id=$PROJECT" "${H[@]}" | jq '.data.summary'
```

---

## 4. Códigos de estado que devuelve el proyecto

| Código | Cuándo |
|---|---|
| 200 | Todo lo que pasa por `BaseController::sendResponse()` (incluso los `store`, que **no** devuelven 201) |
| 201 | `POST /api/register` y `POST /api/posts` (response manual en el controlador) |
| 204 | `DELETE` de proyectos, usuarios, comentarios y adjuntos (`response()->noContent()`) |
| 401 | Token ausente/expirado en `auth:api` → `{ "message": "Unauthenticated." }` |
| 403 | `authorize()` en una policy (proyectos) |
| 404 | `findOrFail` / `firstOrFail` / `abort_if` en la verificación de padre |
| 422 | `$request->validate()` → `{ "message": "…", "errors": { "campo": ["…"] } }` |
| 500 | `POST /api/public/leads` (mass assignment), `DELETE` de tareas (route binding roto) |

---

## 5. Reglas de negocio que conviene conocer

- **`POST /projects/{p}/tasks` exige `status = "pending"`** (regla `in:pending`). El resto de
  estados sólo se alcanzan con `PATCH`.
- **Cierre de tarea automático:** al pasar a `completed`, `ProjectTaskController@update`
  calcula `actual_hours = (now − created_at)` en horas (2 decimales) y fija `completed_at`.
- **Mail de asignación:** si `assigned_to` apunta a un usuario con email, se encola
  `App\Mail\TaskAssigned` → requiere `php artisan queue:work` (con `QUEUE_CONNECTION=database`)
  o cambia a `->send()` para envío síncrono. Con `MAIL_MAILER=log` el mensaje va a
  `storage/logs/laravel.log`.
- **Archivos:** se guardan en `storage/app/public/...` y las URLs se construyen contra el disk
  `public`, por lo que **hace falta `php artisan storage:link`** para que `url`/`logo` sean accesibles.
- **Dashboard cacheado:** `Cache::remember` con clave `dashboard_overview_v1[_project_N]` y 60 s
  de TTL. Para ver datos frescos al instante: `php artisan cache:clear`.
- **`created_by`** de una tarea se fija con `auth()->id()`; `user_id` del proyecto, igual.

---

## 6. Notas y deriva detectada

Cosas que la documentación no puede arreglar pero que conviene tener por escrito
(los enlaces llevan al sitio exacto del código):

1. **Guard de API ambiguo.** `config/auth.php` define `'api' => ['driver' => 'passport']`,
   `User` usa `Laravel\Passport\HasApiTokens`, y `AuthController@register` pide
   `createToken('api-token')->plainTextToken` (API de **Sanctum**). Ese token no autentica
   contra `auth:api`, así que sólo el `login` (Passport) es utilitable. Decide un proveedor y
   alinea `register` + `composer.json` (hoy Sanctum y Passport conviven).
2. **`POST /api/public/leads` roto.** `App\Models\Lead` (y `Post`) no declaran `$fillable` con
   `$request->all()` → `MassAssignmentException`.
3. **`DELETE /api/projects/{p}/tasks/{t}` roto.** `ProjectTaskController::destroy(ProjectTask $projectTask)`
   no coincide con el parámetro `{task}` de la ruta.
4. **Columnas usadas por los resources que no están en las migraciones:** `users.role`,
   `users.photo`, `projects.logo`, `projects.client_email`, `projects.client_phone`,
   `project_tasks.order`, y los nombres `project_task_comments.task_id` /
   `created_by` (las migraciones crean `task_id` / `user_id`). O faltan migraciones en el repo
   o hay que alinear los resources.
5. **`apiResource` sobre `dashboard`** publica verbos que el controlador no implementa.
   Quedarse en `Route::get('/dashboard', …)` + `->only([])` evita superficies vacías.
6. **`UserController@store`** valida el campo `photo` y lee `$request->file('avatar')`: el avatar
   nunca se guarda por esa ruta (`update()` sí usa `avatar`).
